<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pixabay extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('jwt');
        $this->output->set_content_type('application/json');
    }

    public function search()
    {
        require_jwt(['admin','user']);

        $q = trim((string)$this->input->get('q'));
        $type = strtolower((string)$this->input->get('type'));
        $page = (int)$this->input->get('page');

        if ($q === '') {
            return $this->_bad_request('Query q is required');
        }
        if ($type !== 'image' && $type !== 'video') {
            $type = 'image';
        }
        if ($page <= 0) {
            $page = 1;
        }

        $key = env('PIXABAY_API_KEY', '');
        if ($key === '') {
            return $this->_server_error('Pixabay API key not configured');
        }

        $endpoint = $type === 'video' ? 'https://pixabay.com/api/videos/' : 'https://pixabay.com/api/';
        $params = http_build_query(array(
            'key'        => $key,
            'q'          => $q,
            'page'       => $page,
            'per_page'   => 20,
            'safesearch' => 'true'
        ));
        $url = $endpoint . '?' . $params;

        $resp = $this->_curl_get_json($url);
        if ($resp === null) {
            return $this->_server_error('Failed to contact Pixabay');
        }

        $out = array(
            'total'     => isset($resp['total']) ? (int)$resp['total'] : 0,
            'totalHits' => isset($resp['totalHits']) ? (int)$resp['totalHits'] : 0,
            'hits'      => array()
        );

        if (!empty($resp['hits']) && is_array($resp['hits'])) {
            foreach ($resp['hits'] as $h) {
                if ($type === 'video') {
                    $thumb = isset($h['videos']['tiny']['url']) ? $h['videos']['tiny']['url'] : null;
                    $u = isset($h['videos']['medium']['url']) ? $h['videos']['medium']['url'] : $thumb;
                    $out['hits'][] = array(
                        'preview' => $thumb,
                        'url'     => $u,
                        'type'    => 'video',
                        'tags'    => isset($h['tags']) ? $h['tags'] : ''
                    );
                } else {
                    $thumb = isset($h['previewURL']) ? $h['previewURL'] : (isset($h['webformatURL']) ? $h['webformatURL'] : null);
                    $u = isset($h['largeImageURL']) ? $h['largeImageURL'] : (isset($h['webformatURL']) ? $h['webformatURL'] : $thumb);
                    $out['hits'][] = array(
                        'preview' => $thumb,
                        'url'     => $u,
                        'type'    => 'image',
                        'tags'    => isset($h['tags']) ? $h['tags'] : ''
                    );
                }
            }
        }

        echo json_encode($out, JSON_UNESCAPED_SLASHES);
    }

    private function _curl_get_json($url)
    {
        if (!function_exists('curl_init')) {
            $raw = @file_get_contents($url);
            if ($raw === false) return null;
            $data = json_decode($raw, true);
            return is_array($data) ? $data : null;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ));
        $raw = curl_exec($ch);
        curl_close($ch);
        if ($raw === false) return null;

        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    private function _bad_request($message)
    {
        $this->output->set_status_header(400);
        echo json_encode(array('error' => $message));
    }

    private function _server_error($message)
    {
        $this->output->set_status_header(500);
        echo json_encode(array('error' => $message));
    }
}
