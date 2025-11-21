<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model
{
    private $table = 'users';

    public function __construct()
    {
        parent::__construct();
    }

    public function find_by_email($email)
    {
        $sql = "SELECT id, name, email, password_hash, role, created_at
                FROM {$this->table}
                WHERE email = ?
                LIMIT 1";
        $query = $this->db->query($sql, array($email));
        return $query->row_array();
    }

    public function get_by_id($id)
    {
        $sql = "SELECT id, name, email, role, created_at
                FROM {$this->table}
                WHERE id = ?
                LIMIT 1";
        $query = $this->db->query($sql, array($id));
        return $query->row_array();
    }

    public function create($name, $email, $password, $role = 'user')
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $now = date('Y-m-d H:i:s');
        $sql = "INSERT INTO {$this->table} (name, email, password_hash, role, created_at)
                VALUES (?, ?, ?, ?, ?)";
        $ok = $this->db->query($sql, array($name, $email, $hash, $role, $now));
        if (!$ok) {
            return false;
        }
        return $this->db->insert_id();
    }

    public function verify_password($email, $password)
    {
        $user = $this->find_by_email($email);
        if (!$user) {
            return false;
        }
        if (!isset($user['password_hash']) || $user['password_hash'] === '') {
            return false;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }
        return $user;
    }
}
