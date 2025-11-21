<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<?php $this->load->view('partials/header', ['title' => 'Posts', 'page_title' => 'Posts', 'active' => 'posts']); ?>

<div id="pagePosts">
  <div class="card">
    <div class="muted mb-1">Manage posts</div>

    <div id="dashError" class="error mb-2"></div>

    <div class="row mb-2">
      <div class="col-6">
        <label class="muted">Search by Title</label>
        <input id="searchTitle" class="input" placeholder="Title contains...">
      </div>
      <div class="col-6">
        <label class="muted">Filter by Author</label>
        <input id="searchAuthor" class="input" placeholder="Author name contains...">
      </div>
    </div>

    <div class="mb-2 hstack">
      <button class="btn btn-outline" id="btnSearch" type="button">Search</button>
      <a class="btn" href="<?php echo site_url('posts/create'); ?>" id="btnCreate">Create Post</a>
    </div>

    <div class="table-wrap">
    <table class="table display" id="postsTable">
      <thead>
        <tr>
          <th class="w-40">Title</th>
          <th class="w-20">Author</th>
          <th class="w-20">Created</th>
          <th class="w-10">Status</th>
          <th class="w-10">Actions</th>
        </tr>
      </thead>
      <tbody id="postsTbody"></tbody>
    </table>
    </div>

    <div class="mt-2" id="dashPagination"></div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
