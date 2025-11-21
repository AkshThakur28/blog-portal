<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$title = isset($title) ? $title : 'Admin';
$page_title = isset($page_title) ? $page_title : $title;
$active = isset($active) ? $active : '';
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo e($title); ?> - Blog Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="<?php echo base_url('assets/css/style.css'); ?>">
  <link rel="stylesheet" href="https://cdn.datatables.net/2.0.7/css/dataTables.dataTables.min.css">
</head>
<body>
  <div class="admin-shell">
    <div class="layout">
      <?php $this->load->view('partials/sidebar', ['active' => $active]); ?>
      <main class="main">
        <div class="topbar">
          <h1 class="page-title"><?php echo e($page_title); ?></h1>
          <div class="top-actions">
            <button class="btn btn-outline" id="btnLogout">Logout</button>
          </div>
        </div>
