<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<aside class="sidebar">
  <div class="sidebar-inner">
    <div class="brand">
      <a href="<?php echo site_url('dashboard'); ?>" class="brand-link">
        <span class="brand-icon" aria-hidden="true">📝</span>
        <span class="brand-name">Blog Admin</span>
      </a>
    </div>

    <nav class="nav">
      <div class="nav-section">Main</div>
      <a href="<?php echo site_url('dashboard'); ?>" class="nav-link <?php echo isset($active) && $active === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>

      <div class="menu-group">
        <button class="menu-toggle" id="postsToggle">
          Posts
          <span class="caret"></span>
        </button>
        <div class="submenu" id="postsMenu">
          <a href="<?php echo site_url('posts'); ?>" class="<?php echo isset($active) && $active === 'posts' ? 'active' : ''; ?>">All Posts</a>
          <a href="<?php echo site_url('posts/create'); ?>" id="linkCreate">Create Post</a>
        </div>
      </div>

      <div class="nav-section">Website</div>
      <a href="<?php echo site_url(); ?>" target="_blank" class="nav-link">View Blog</a>
    </nav>
  </div>
</aside>
