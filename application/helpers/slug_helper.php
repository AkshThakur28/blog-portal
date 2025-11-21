<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Slug helper: generate URL-safe slugs and basic utilities.
 */

if (!function_exists('slugify')) {
    /**
     * Convert a string into a URL-safe slug.
     * - Lowercase
     * - Replace non-alphanum with hyphens
     * - Collapse multiple hyphens
     * - Trim hyphens
     */
    function slugify($text)
    {
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        $text = trim($text, '-');
        return $text !== '' ? $text : 'post';
    }
}

if (!function_exists('append_slug_suffix')) {
    /**
     * Append numeric suffix to a slug e.g. "my-post", "my-post-1", "my-post-2"
     */
    function append_slug_suffix($base, $index)
    {
        return $index > 0 ? $base . '-' . $index : $base;
    }
}
