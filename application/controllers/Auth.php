<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model', 'users');
        $this->load->helper(array('jwt'));
        $this->output->set_content_type('application/json');
    }

    public function index()
    {
        $this->output->set_content_type('text/html');
        $this->load->view('auth/login');
    }

    public function login()
    {
        if (strtoupper($this->input->method()) !== 'POST') {
            return $this->_bad_request('Invalid method');
        }

        $email = trim((string)$this->input->post('email', true));
        $password = (string)$this->input->post('password');

        if ($email === '' || $password === '') {
            return $this->_bad_request('Email and password are required');
        }

        $user = $this->users->verify_password($email, $password);
        if (!$user) {
            return $this->_unauthorized('Invalid credentials');
        }

        $claims = array(
            'sub'  => (int)$user['id'],
            'role' => $user['role'],
        );
        $ttl = (int) env('JWT_TTL', 3600);
        try {
            $token = jwt_encode($claims, $ttl);
        } catch (Throwable $e) {
            return $this->_server_error('Failed to issue token');
        } catch (Exception $e) {
            return $this->_server_error('Failed to issue token');
        }

        echo json_encode(array(
            'token' => $token,
            'user'  => array(
                'id'    => (int)$user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role']
            )
        ), JSON_UNESCAPED_SLASHES);
    }

    public function set_token()
    {
        $this->output->set_content_type('text/html');
        $token = (string) $this->input->get('token', true);
        $name = (string) $this->input->get('name', true);
        if ($name === '') { $name = 'User'; }
        if ($token === '') {
            echo '<!doctype html><meta charset="utf-8"><p>Missing token</p>';
            return;
        }
        $dash = site_url('dashboard');
        $html = '<!doctype html><meta charset="utf-8"><title>Setting token...</title>'
              . '<script>try{localStorage.setItem("jwt_token",' . json_encode($token) . ');localStorage.setItem("user_name",' . json_encode($name) . ');}catch(e){}location.href=' . json_encode($dash) . ';</script>'
              . '<p>Redirecting...</p>';
        echo $html;
    }

    public function dev_login()
    {
        $this->output->set_content_type('text/html');

        $email = (string) $this->input->get('email', true);
        $password = (string) $this->input->get('password');

        if ($email === '') { $email = 'admin@example.com'; }
        if ($password === '') { $password = 'Admin@123'; }

        $user = $this->users->verify_password($email, $password);
        if (!$user) {
            echo '<!doctype html><meta charset="utf-8"><p>Invalid credentials</p>';
            return;
        }

        $claims = array(
            'sub'  => (int)$user['id'],
            'role' => $user['role'],
        );

        $ttl = (int) env('JWT_TTL', 3600);
        try {
            $token = jwt_encode($claims, $ttl);
        } catch (Throwable $e) {
            echo '<!doctype html><meta charset="utf-8"><p>Failed to issue token</p>';
            return;
        } catch (Exception $e) {
            echo '<!doctype html><meta charset="utf-8"><p>Failed to issue token</p>';
            return;
        }

        $dash = site_url('dashboard');
        $name = $user['name'];

        $html = '<!doctype html><meta charset="utf-8"><title>Logging in...</title>'
              . '<script>try{localStorage.setItem("jwt_token",' . json_encode($token) . ');localStorage.setItem("user_name",' . json_encode($name) . ');}catch(e){}location.href=' . json_encode($dash) . ';</script>'
              . '<p>Redirecting to dashboard...</p>';
        echo $html;
    }

    private function _bad_request($message)
    {
        $this->output->set_status_header(400);
        echo json_encode(array('error' => $message));
    }

    private function _unauthorized($message)
    {
        $this->output->set_status_header(401);
        echo json_encode(array('error' => $message));
    }

    private function _server_error($message)
    {
        $this->output->set_status_header(500);
        echo json_encode(array('error' => $message));
    }
}
