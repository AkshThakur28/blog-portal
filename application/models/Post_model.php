<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Post_model extends CI_Model
{
    private $table = 'posts';

    public function __construct()
    {
        parent::__construct();
    }

    public function list_paginated($limit = 10, $offset = 0, $filters = [])
    {
        $params = [];
        $where = "p.status IN ('active','deleted')";

        if (!empty($filters['title'])) {
            $where .= " AND p.title LIKE ?";
            $params[] = '%'.$filters['title'].'%';
        }
        if (!empty($filters['author'])) {
            $where .= " AND u.name LIKE ?";
            $params[] = '%'.$filters['author'].'%';
        }

        $sql = "SELECT p.id, p.title, p.slug, p.user_id, u.name AS author, p.created_at, p.updated_at, p.status
                FROM {$this->table} p
                JOIN users u ON u.id = p.user_id
                WHERE {$where}
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $rows = $this->db->query($sql, $params)->result_array();

        // count total for pagination
        $count_sql = "SELECT COUNT(*) AS cnt
                      FROM {$this->table} p
                      JOIN users u ON u.id = p.user_id
                      WHERE {$where}";
        $count = $this->db->query($count_sql, array_slice($params, 0, count($params) - 2))->row_array();

        return ['rows' => $rows, 'total' => (int)$count['cnt']];
    }

    public function public_list($limit = 10, $offset = 0)
    {
        $sql = "SELECT p.id, p.title, p.slug, p.user_id, u.name AS author, p.created_at, p.cover_media_url, p.media_type
                FROM {$this->table} p
                JOIN users u ON u.id = p.user_id
                WHERE p.status = 'active'
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?";
        $rows = $this->db->query($sql, array((int)$limit, (int)$offset))->result_array();

        $count = $this->db->query("SELECT COUNT(*) AS cnt FROM {$this->table} WHERE status = 'active'")->row_array();

        return ['rows' => $rows, 'total' => (int)$count['cnt']];
    }

    public function get_by_id($id)
    {
        $sql = "SELECT p.*, u.name AS author
                FROM {$this->table} p
                JOIN users u ON u.id = p.user_id
                WHERE p.id = ?
                LIMIT 1";
        return $this->db->query($sql, array((int)$id))->row_array();
    }

    public function get_by_slug($slug)
    {
        $sql = "SELECT p.*, u.name AS author
                FROM {$this->table} p
                JOIN users u ON u.id = p.user_id
                WHERE p.slug = ? AND p.status = 'active'
                LIMIT 1";
        return $this->db->query($sql, array($slug))->row_array();
    }

    public function create(array $data)
    {
        $now = date('Y-m-d H:i:s');
        $sql = "INSERT INTO {$this->table} (user_id, title, slug, body, cover_media_url, media_type, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, 'active', ?, ?)";
        $ok = $this->db->query($sql, array(
            (int)$data['user_id'],
            $data['title'],
            $data['slug'],
            $data['body'],
            isset($data['cover_media_url']) ? $data['cover_media_url'] : null,
            isset($data['media_type']) ? $data['media_type'] : null,
            $now, $now
        ));
        if (!$ok) {
            return false;
        }
        return $this->db->insert_id();
    }

    public function update($id, array $data)
    {
        $now = date('Y-m-d H:i:s');
        $fields = [];
        $params = [];

        foreach (['title','slug','body','cover_media_url','media_type'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "{$f} = ?";
                $params[] = $data[$f];
            }
        }
        $fields[] = "updated_at = ?";
        $params[] = $now;

        if (empty($fields)) {
            return false;
        }

        $params[] = (int)$id;
        $sql = "UPDATE {$this->table} SET ".implode(', ', $fields)." WHERE id = ?";
        return $this->db->query($sql, $params);
    }

    public function soft_delete($id)
    {
        $now = date('Y-m-d H:i:s');
        $sql = "UPDATE {$this->table} SET status = 'deleted', updated_at = ? WHERE id = ?";
        return $this->db->query($sql, array($now, (int)$id));
    }

    public function restore($id)
    {
        $now = date('Y-m-d H:i:s');
        $sql = "UPDATE {$this->table} SET status = 'active', updated_at = ? WHERE id = ?";
        return $this->db->query($sql, array($now, (int)$id));
    }

    public function hard_delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        return $this->db->query($sql, array((int)$id));
    }

    public function is_slug_taken($slug)
    {
        $row = $this->db->query("SELECT COUNT(*) AS cnt FROM {$this->table} WHERE slug = ?", array($slug))->row_array();
        return ((int)$row['cnt'] > 0);
    }

    public function generate_unique_slug($title)
    {
        $this->load->helper('slug');

        $base = slugify($title);
        $slug = $base;
        $i = 0;
        while ($this->is_slug_taken($slug)) {
            $i++;
            $slug = $base . '-' . $i;
            if ($i > 1000) { // safety
                break;
            }
        }
        return $slug;
    }

    public function is_owned_by($postId, $userId)
    {
        $row = $this->db->query("SELECT COUNT(*) AS cnt FROM {$this->table} WHERE id = ? AND user_id = ?", array((int)$postId, (int)$userId))->row_array();
        return ((int)$row['cnt'] > 0);
    }
}
