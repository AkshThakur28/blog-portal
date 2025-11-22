<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model', 'users');
        $this->load->model('Refresh_token_model', 'refreshTokens');
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

        $refreshTtl = (int) env('REFRESH_TTL', 1209600);
        if (!isset($this->refreshTokens)) {
            $this->load->model('Refresh_token_model', 'refreshTokens');
        }
        $rt = $this->refreshTokens->create_for_user((int)$user['id'], $refreshTtl);
        if ($rt && isset($rt['token'])) {
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
            setcookie('refresh_token', $rt['token'], array(
                'expires'  => strtotime($rt['expires_at']),
                'path'     => '/',
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ));
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



    public function refresh()
    {
        $this->output->set_content_type('application/json');

        $cookie = isset($_COOKIE['refresh_token']) ? (string) $_COOKIE['refresh_token'] : '';
        if ($cookie === '') {
            return $this->_unauthorized('Missing refresh token');
        }

        if (!isset($this->refreshTokens)) {
            $this->load->model('Refresh_token_model', 'refreshTokens');
        }

        $row = $this->refreshTokens->find_valid_row($cookie);
        if (!$row) {
            return $this->_unauthorized('Invalid or expired refresh token');
        }

        $rot = $this->refreshTokens->rotate($cookie, (int) env('REFRESH_TTL', 1209600));
        if (!$rot) {
            return $this->_unauthorized('Invalid or expired refresh token');
        }

        $userId = (int) $row['user_id'];
        $user   = $this->users->get_by_id($userId);
        if (!$user) {
            return $this->_unauthorized('User not found');
        }

        $claims = array(
            'sub'  => $userId,
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

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
        setcookie('refresh_token', $rot['token'], array(
            'expires'  => strtotime($rot['expires_at']),
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ));

        echo json_encode(array('token' => $token), JSON_UNESCAPED_SLASHES);
    }

    public function logout()
    {
        $this->output->set_content_type('application/json');

        $cookie = isset($_COOKIE['refresh_token']) ? (string) $_COOKIE['refresh_token'] : '';
        if ($cookie !== '') {
            if (!isset($this->refreshTokens)) {
                $this->load->model('Refresh_token_model', 'refreshTokens');
            }
            $this->refreshTokens->revoke_by_token($cookie);
        }

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
        setcookie('refresh_token', '', array(
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ));

        echo json_encode(array('logged_out' => true), JSON_UNESCAPED_SLASHES);
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
