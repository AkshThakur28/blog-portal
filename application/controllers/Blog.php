<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Blog extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Post_model', 'posts');
        $this->load->helper(array('url', 'sanitize'));
    }

    public function index()
    {
        $page = max(1, (int)$this->input->get('page'));
        $perPage = (int) env('PAGINATION_PER_PAGE', 10);
        $offset = ($page - 1) * $perPage;

        $data = $this->posts->public_list($perPage, $offset);

        $viewData = array(
            'rows'      => $data['rows'],
            'page'      => $page,
            'per_page'  => $perPage,
            'total'     => $data['total']
        );

        $this->load->view('blog/index', $viewData);
    }

    public function view($slug = '')
    {
        if ($slug === '') {
            show_404();
            return;
        }

        $post = $this->posts->get_by_slug($slug);
        if (!$post) {
            show_404();
            return;
        }

        $this->load->view('blog/view', array('post' => $post));
    }
}
