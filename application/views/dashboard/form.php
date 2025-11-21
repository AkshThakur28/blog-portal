<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$mode = isset($mode) ? $mode : 'create';
$postId = isset($postId) ? (int)$postId : null;
$titleText = ($mode === 'edit') ? 'Edit Post' : 'Create Post';
?>
<?php $this->load->view('partials/header', ['title' => $titleText, 'page_title' => $titleText, 'active' => 'posts']); ?>

<div class="card">
  <div class="mb-2 flex-bar">
    <div class="muted">Use the form below to <?php echo $mode === 'edit' ? 'update this' : 'create a new'; ?> post.</div>
    <a class="btn btn-outline" href="<?php echo site_url('posts'); ?>">Back to Posts</a>
  </div>

  <form id="postForm" data-mode="<?php echo e($mode); ?>" data-id="<?php echo $postId ? (int)$postId : ''; ?>" onsubmit="return false;">
    <div class="row">
      <div class="col-6">
        <label class="muted">Title</label>
        <input id="title" class="input" placeholder="Post title">
      </div>
      <div class="col-6">
        <label class="muted">Media Type</label>
        <select id="media_type" class="input">
          <option value="">None</option>
          <option value="image">Image</option>
          <option value="video">Video</option>
        </select>
      </div>
    </div>

    <div class="mt-2">
      <label class="muted">Body</label>
      <textarea id="body" rows="10" class="input" placeholder="Write content..."></textarea>
      <div class="muted mt-1">Allowed formatting: p, br, strong, em, b, i, u, ul, ol, li, a</div>
    </div>

    <div class="row mt-2">
      <div class="col-6">
        <label class="muted">Cover Media URL</label>
        <input id="cover_media_url" class="input" placeholder="Paste URL or use Pixabay search">
      </div>
      <div class="col-6 btn-row">
        <button id="btnPixabay" class="btn" type="button">Search Pixabay</button>
        <button id="btnSave" class="btn" type="button"><?php echo $mode === 'edit' ? 'Save Changes' : 'Create Post'; ?></button>
      </div>
    </div>

    <div id="coverPreview" class="mt-2"></div>
    <div class="notice mt-1">If using media, attribution will appear on the public post: “Media via Pixabay”.</div>
  </form>
</div>

<?php $this->load->view('partials/footer'); ?>
