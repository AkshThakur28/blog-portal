<?php
/**
 * Lightweight .env loader for CodeIgniter 3.
 * - Reads project-root/.env
 * - Populates $_ENV and getenv() via putenv()
 * - Provides env($key, $default) helper
 */

if (!function_exists('env')) {
    function env($key, $default = null)
    {
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        $v = getenv($key);
        return $v !== false ? $v : $default;
    }
}

$rootEnv = realpath(__DIR__ . '/../../.env');
if ($rootEnv && is_readable($rootEnv)) {
    $lines = file($rootEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $name = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));

        // Strip surrounding quotes if present
        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
            $value = substr($value, 1, -1);
        }

        $_ENV[$name] = $value;
        // Also expose via getenv()
        if (function_exists('putenv')) {
            putenv($name . '=' . $value);
        }
    }
}
