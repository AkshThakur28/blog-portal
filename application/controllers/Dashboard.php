<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(array('url'));
        $this->load->database();
    }

    public function index()
    {
        $activePosts = (int) $this->db->query("SELECT COUNT(*) AS cnt FROM posts WHERE status = 'active'")->row_array()['cnt'];
        $totalUsers  = (int) $this->db->query("SELECT COUNT(*) AS cnt FROM users")->row_array()['cnt'];

        $data = array(
            'counts' => array(
                'posts_active' => $activePosts,
                'users'        => $totalUsers,
            )
        );

        $this->load->view('dashboard/index', $data);
    }

    public function posts()
    {
        $this->load->view('dashboard/posts');
    }

    public function create()
    {
        $this->load->view('dashboard/form', array('mode' => 'create', 'postId' => null));
    }

    public function edit($id = null)
    {
        $id = $id ? (int)$id : null;
        if (!$id) {
            redirect('dashboard');
            return;
        }
        $this->load->view('dashboard/form', array('mode' => 'edit', 'postId' => $id));
    }
}
