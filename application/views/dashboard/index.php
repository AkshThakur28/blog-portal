<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<?php $this->load->view('partials/header', ['title' => 'Dashboard', 'page_title' => 'Dashboard', 'active' => 'dashboard']); ?>

<div id="dashboardPage">
  <div class="card">
    <h2 class="greet-title no-margin">Welcome back, <span id="greetName">Admin</span></h2>
  </div>

  <div class="stats mt-2">
    <a class="stat-card" href="<?php echo site_url('posts'); ?>" aria-label="View active posts">
      <div class="stat-label">Active Posts</div>
      <div class="stat-value"><?php echo isset($counts['posts_active']) ? (int)$counts['posts_active'] : 0; ?></div>
    </a>
    <a class="stat-card" href="<?php echo site_url('dashboard'); ?>" aria-label="Users">
      <div class="stat-label">Users</div>
      <div class="stat-value"><?php echo isset($counts['users']) ? (int)$counts['users'] : 0; ?></div>
    </a>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
