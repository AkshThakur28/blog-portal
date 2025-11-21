<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH.'third_party/JWT/JWT.php';
require_once APPPATH.'third_party/JWT/Key.php';
require_once APPPATH.'third_party/JWT/JWTExceptionWithPayloadInterface.php';
require_once APPPATH.'third_party/JWT/BeforeValidException.php';
require_once APPPATH.'third_party/JWT/ExpiredException.php';
require_once APPPATH.'third_party/JWT/SignatureInvalidException.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!function_exists('jwt_secret')) {
    function jwt_secret()
    {
        return env('JWT_SECRET', '');
    }
}

if (!function_exists('jwt_encode')) {
    function jwt_encode(array $claims, int $ttlSeconds = 3600)
    {
        $now = time();
        if (!isset($claims['iat'])) $claims['iat'] = $now;
        if (!isset($claims['exp'])) $claims['exp'] = $now + $ttlSeconds;
        return JWT::encode($claims, jwt_secret(), 'HS256');
    }
}

if (!function_exists('jwt_decode_token')) {
    function jwt_decode_token($token)
    {
        return (array) JWT::decode($token, new Key(jwt_secret(), 'HS256'));
    }
}

if (!function_exists('get_auth_bearer_token')) {
    function get_auth_bearer_token()
    {
        $candidates = array();
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) $candidates[] = $_SERVER['HTTP_AUTHORIZATION'];
        if (!empty($_SERVER['Authorization'])) $candidates[] = $_SERVER['Authorization'];
        if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) $candidates[] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        if (!empty($_SERVER['HTTP_X_AUTH_TOKEN'])) $candidates[] = $_SERVER['HTTP_X_AUTH_TOKEN'];
        if (!empty($_SERVER['X_AUTH_TOKEN'])) $candidates[] = $_SERVER['X_AUTH_TOKEN'];

        if (function_exists('getallheaders')) {
            $all = getallheaders();
            if (is_array($all)) {
                foreach ($all as $k => $v) {
                    if (strcasecmp($k, 'Authorization') === 0) $candidates[] = $v;
                    if (strcasecmp($k, 'X-Auth-Token') === 0) $candidates[] = $v;
                }
            }
        } elseif (function_exists('apache_request_headers')) {
            $all = apache_request_headers();
            if (is_array($all)) {
                foreach ($all as $k => $v) {
                    if (strcasecmp($k, 'Authorization') === 0) $candidates[] = $v;
                    if (strcasecmp($k, 'X-Auth-Token') === 0) $candidates[] = $v;
                }
            }
        }

        foreach ($candidates as $header) {
            if ($header && preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
                return $m[1];
            }
        }

        foreach ($candidates as $header) {
            if (is_string($header)) {
                $h = trim($header);
                if ($h !== '' && stripos($h, 'Bearer ') !== 0) {
                    if (strpos($h, ' ') === false) {
                        return $h;
                    }
                }
            }
        }

        return null;
    }
}

if (!function_exists('require_jwt')) {
    function require_jwt(array $roles = [])
    {
        $ci =& get_instance();

        $token = get_auth_bearer_token();
        if (!$token) {
            _jwt_unauthorized('Missing Authorization Bearer token');
        }

        try {
            $payload = jwt_decode_token($token);
        } catch (Throwable $e) {
            _jwt_unauthorized('Invalid or expired token');
        } catch (Exception $e) {
            _jwt_unauthorized('Invalid or expired token');
        }

        $ci->jwt_payload = $payload;

        if (!empty($roles)) {
            $role = isset($payload['role']) ? $payload['role'] : null;
            if (!$role || !in_array($role, $roles, true)) {
                _jwt_forbidden('Insufficient role');
            }
        }

        return $payload;
    }
}

if (!function_exists('current_user_id')) {
    function current_user_id()
    {
        $ci =& get_instance();
        return isset($ci->jwt_payload['sub']) ? (int) $ci->jwt_payload['sub'] : null;
    }
}

if (!function_exists('current_user_role')) {
    function current_user_role()
    {
        $ci =& get_instance();
        return isset($ci->jwt_payload['role']) ? $ci->jwt_payload['role'] : null;
    }
}

if (!function_exists('_jwt_unauthorized')) {
    function _jwt_unauthorized($message = 'Unauthorized')
    {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message], JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('_jwt_forbidden')) {
    function _jwt_forbidden($message = 'Forbidden')
    {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message], JSON_UNESCAPED_SLASHES);
        exit;
    }
}
