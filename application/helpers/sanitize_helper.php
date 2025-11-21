<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('e')) {
    function e($str)
    {
        return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('sanitize_body')) {
    function sanitize_body($html)
    {
        $html = (string)$html;
        $allowed = '<p><br><strong><em><b><i><u><ul><ol><li><a>';
        $clean = strip_tags($html, $allowed);

        $clean = preg_replace_callback(
            '#<a\b[^>]*>#i',
            function ($m) {
                $tag = $m[0];

                if (preg_match('#href\s*=\s*([\'"])(.*?)\1#i', $tag, $hrefMatch)) {
                    $href = $hrefMatch[2];
                } else {
                    $href = '#';
                }

                if (!preg_match('#^(https?:)?//#i', $href) && !preg_match('#^https?://#i', $href)) {
                    $href = '#';
                }

                $title = '';
                if (preg_match('#title\s*=\s*([\'"])(.*?)\1#i', $tag, $titleMatch)) {
                    $title = $titleMatch[2];
                }

                $target = '';
                if (preg_match('#target\s*=\s*([\'"])(.*?)\1#i', $tag, $targetMatch)) {
                    $target = $targetMatch[2];
                }

                $attrs = ' href="'.e($href).'"';
                if ($title !== '') {
                    $attrs .= ' title="'.e($title).'"';
                }
                if ($target !== '') {
                    $attrs .= ' target="'.e($target).'"';
                }
                $attrs .= ' rel="noopener noreferrer"';
                return '<a'.$attrs.'>';
            },
            $clean
        );

        return $clean;
    }
}
