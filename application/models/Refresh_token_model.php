<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Refresh_token_model extends CI_Model
{
    private $table = 'refresh_tokens';

    public function __construct()
    {
        parent::__construct();
    }

    private function now()
    {
        return date('Y-m-d H:i:s');
    }

    private function hash_token($token)
    {
        $pepper = (string) env('APP_KEY', '');
        return hash('sha256', $token . '|' . $pepper);
    }

    public function create_for_user($userId, $ttlSeconds = 1209600)
    {
        try {
            $raw = random_bytes(32);
        } catch (Throwable $e) {
            $raw = openssl_random_pseudo_bytes(32);
        } catch (Exception $e) {
            $raw = openssl_random_pseudo_bytes(32);
        }

        $token = rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
        $hash = $this->hash_token($token);
        $now  = $this->now();
        $exp  = date('Y-m-d H:i:s', time() + (int) $ttlSeconds);

        $sql = "INSERT INTO {$this->table} (user_id, token_hash, expires_at, revoked, created_at)
                VALUES (?, ?, ?, 0, ?)";
        $ok = $this->db->query($sql, array((int)$userId, $hash, $exp, $now));
        if (!$ok) {
            return false;
        }
        return array('token' => $token, 'expires_at' => $exp);
    }

    public function find_valid_row($token)
    {
        $hash = $this->hash_token($token);
        $sql = "SELECT id, user_id, token_hash, expires_at, revoked, created_at
                FROM {$this->table}
                WHERE token_hash = ?
                  AND revoked = 0
                  AND expires_at > NOW()
                LIMIT 1";
        return $this->db->query($sql, array($hash))->row_array();
    }

    public function revoke_by_token($token)
    {
        $hash = $this->hash_token($token);
        $sql = "UPDATE {$this->table} SET revoked = 1 WHERE token_hash = ?";
        return $this->db->query($sql, array($hash));
    }

    public function revoke_id($id)
    {
        $sql = "UPDATE {$this->table} SET revoked = 1 WHERE id = ?";
        return $this->db->query($sql, array((int)$id));
    }

    public function rotate($oldToken, $ttlSeconds = 1209600)
    {
        $row = $this->find_valid_row($oldToken);
        if (!$row) return false;

        $this->revoke_id((int)$row['id']);
        return $this->create_for_user((int)$row['user_id'], $ttlSeconds);
    }
}
