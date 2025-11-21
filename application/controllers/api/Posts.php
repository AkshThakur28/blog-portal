<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Posts extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(array('jwt', 'sanitize'));
        $this->load->model('Post_model', 'posts');
        $this->output->set_content_type('application/json');
    }

    public function index()
    {
        require_jwt(['admin','user']);

        $page = max(1, (int)$this->input->get('page'));
        $perPage = (int)$this->input->get('per_page');
        if ($perPage <= 0) {
            $perPage = (int) env('PAGINATION_PER_PAGE', 10);
        }
        $offset = ($page - 1) * $perPage;

        $filters = array(
            'title'  => trim((string)$this->input->get('title')),
            'author' => trim((string)$this->input->get('author')),
        );

        $data = $this->posts->list_paginated($perPage, $offset, $filters);

        echo json_encode(array(
            'page'     => $page,
            'per_page' => $perPage,
            'total'    => $data['total'],
            'rows'     => $data['rows']
        ));
    }

    public function show($id)
    {
        require_jwt(['admin','user']);
        $id = (int)$id;
        if ($id <= 0) {
            return $this->_bad_request('Invalid id');
        }
        $row = $this->posts->get_by_id($id);
        if (!$row) {
            $this->output->set_status_header(404);
            echo json_encode(array('error' => 'Not found'));
            return;
        }
        echo json_encode($row);
    }

    public function create()
    {
        $payload = require_jwt(['admin','user']);
        $userId = (int) $payload['sub'];

        $input = $this->input->post();
        if (empty($input)) {
            $raw = $this->input->raw_input_stream;
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $input = $json;
            }
        }

        $title = isset($input['title']) ? trim((string)$input['title']) : '';
        $body  = isset($input['body']) ? (string)$input['body'] : '';
        $cover = isset($input['cover_media_url']) ? trim((string)$input['cover_media_url']) : null;
        $mtype = isset($input['media_type']) ? trim((string)$input['media_type']) : null;

        if ($title === '' || $body === '') {
            return $this->_bad_request('Title and body are required');
        }
        if ($mtype && !in_array($mtype, array('image','video'), true)) {
            return $this->_bad_request('Invalid media_type');
        }

        $slug = $this->posts->generate_unique_slug($title);
        $cleanBody = sanitize_body($body);

        $id = $this->posts->create(array(
            'user_id' => $userId,
            'title'   => $title,
            'slug'    => $slug,
            'body'    => $cleanBody,
            'cover_media_url' => $cover,
            'media_type'      => $mtype
        ));

        if (!$id) {
            return $this->_server_error('Failed to create post');
        }

        echo json_encode(array('id' => (int)$id, 'slug' => $slug));
    }

    public function update($id)
    {
        $payload = require_jwt(['admin','user']);
        $role = $payload['role'];
        $userId = (int)$payload['sub'];
        $id = (int)$id;

        if ($role !== 'admin' && !$this->posts->is_owned_by($id, $userId)) {
            return $this->_forbidden('You can only edit your own posts');
        }

        $raw = $this->input->raw_input_stream;
        $input = array();
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $input = $json;
        } else {
            parse_str($raw, $input);
        }

        $data = array();
        if (isset($input['title'])) {
            $title = trim((string)$input['title']);
            if ($title === '') {
                return $this->_bad_request('Title cannot be empty');
            }
            $data['title'] = $title;
        }
        if (isset($input['body'])) {
            $data['body'] = sanitize_body((string)$input['body']);
        }
        if (array_key_exists('cover_media_url', $input)) {
            $data['cover_media_url'] = $input['cover_media_url'] !== '' ? (string)$input['cover_media_url'] : null;
        }
        if (array_key_exists('media_type', $input)) {
            $mtype = $input['media_type'];
            if ($mtype !== null && $mtype !== '' && !in_array($mtype, array('image','video'), true)) {
                return $this->_bad_request('Invalid media_type');
            }
            $data['media_type'] = $mtype !== '' ? $mtype : null;
        }

        if (!$this->posts->update($id, $data)) {
            return $this->_server_error('Failed to update post');
        }

        echo json_encode(array('updated' => true));
    }

    public function delete($id)
    {
        $payload = require_jwt(['admin','user']);
        $role = $payload['role'];
        $userId = (int)$payload['sub'];
        $id = (int)$id;

        if ($role !== 'admin' && !$this->posts->is_owned_by($id, $userId)) {
            return $this->_forbidden('You can only delete your own posts');
        }

        if (!$this->posts->soft_delete($id)) {
            return $this->_server_error('Failed to delete post');
        }

        echo json_encode(array('deleted' => true));
    }

    public function restore($id)
    {
        $payload = require_jwt(['admin','user']);
        $role = $payload['role'];
        $userId = (int)$payload['sub'];
        $id = (int)$id;

        if ($role !== 'admin' && !$this->posts->is_owned_by($id, $userId)) {
            return $this->_forbidden('You can only restore your own posts');
        }

        if (!$this->posts->restore($id)) {
            return $this->_server_error('Failed to restore post');
        }

        echo json_encode(array('restored' => true));
    }

    public function hard_delete($id)
    {
        $payload = require_jwt(['admin','user']);
        $role = $payload['role'];
        $id = (int)$id;

        if ($role !== 'admin') {
            return $this->_forbidden('Only admin can permanently delete posts');
        }

        if (!$this->posts->hard_delete($id)) {
            return $this->_server_error('Failed to permanently delete post');
        }

        echo json_encode(array('hard_deleted' => true));
    }

    private function _bad_request($message)
    {
        $this->output->set_status_header(400);
        echo json_encode(array('error' => $message));
    }

    private function _forbidden($message)
    {
        $this->output->set_status_header(403);
        echo json_encode(array('error' => $message));
    }

    private function _server_error($message)
    {
        $this->output->set_status_header(500);
        echo json_encode(array('error' => $message));
    }
}
