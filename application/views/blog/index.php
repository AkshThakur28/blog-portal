<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Blog</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Crect width='16' height='16' rx='3' fill='%232563eb'/%3E%3Ctext x='8' y='11' font-size='10' text-anchor='middle' fill='white' font-family='Arial'%3EB%3C/text%3E%3C/svg%3E">
  <link rel="stylesheet" href="<?php echo base_url('assets/css/style.css'); ?>">
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Blog</h1>
      <div>
        <a id="authLink" class="btn btn-outline" href="<?php echo site_url('login'); ?>" data-dash="<?php echo site_url('dashboard'); ?>" data-login="<?php echo site_url('login'); ?>">Admin Login</a>
      </div>
    </div>

    <div class="card">
      <?php if (!empty($rows)): ?>
        <?php foreach ($rows as $p): ?>
          <div class="row mb-2 row-align-start">
            <div class="col-6">
              <h3 class="h3-tight">
                <a href="<?php echo site_url('post/'.e($p['slug'])); ?>" target="_blank"><?php echo e($p['title']); ?></a>
              </h3>
              <div class="muted">By <?php echo e($p['author']); ?> • <?php echo date('M d, Y', strtotime($p['created_at'])); ?></div>
            </div>
            <div class="col-6">
              <?php if (!empty($p['cover_media_url'])): ?>
                <?php if ($p['media_type'] === 'video'): ?>
                  <video src="<?php echo e($p['cover_media_url']); ?>" class="media-thumb-video" controls></video>
                <?php else: ?>
                  <img src="<?php echo e($p['cover_media_url']); ?>" alt="" class="media-thumb">
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
          <hr class="hr">
        <?php endforeach; ?>

        <?php
          $total_pages = (int) ceil($total / max(1, $per_page));
          $curr = (int) $page;
        ?>
        <?php if ($total_pages > 1): ?>
          <div class="mt-2">
            <?php if ($curr > 1): ?>
              <a class="btn btn-outline" href="<?php echo site_url('?page='.($curr-1)); ?>">&laquo; Prev</a>
            <?php endif; ?>
            <span class="muted" style="margin:0 10px">Page <?php echo $curr; ?> of <?php echo $total_pages; ?></span>
            <?php if ($curr < $total_pages): ?>
              <a class="btn btn-outline" href="<?php echo site_url('?page='.($curr+1)); ?>">Next &raquo;</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <p>No posts yet.</p>
      <?php endif; ?>
      <div class="notice mt-2">Media attribution: Images/Videos via Pixabay.</div>
    </div>
  </div>
  <script>
    (function(){
      function isValidToken(t){
        try{
          var p=t.split('.')[1]; if(!p) return false;
          p=p.replace(/-/g,'+').replace(/_/g,'/'); var o=JSON.parse(atob(p));
          var now=Math.floor(Date.now()/1000); return o && o.exp && o.exp>now;
        }catch(e){return false;}
      }
      var link=document.getElementById('authLink');
      if(link){
        var t=''; try{t=localStorage.getItem('jwt_token')||'';}catch(_){}
        if(isValidToken(t)){
          link.textContent='Dashboard';
          link.href=link.getAttribute('data-dash')||link.href;
        }else{
          link.textContent='Admin Login';
          link.href=link.getAttribute('data-login')||link.href;
        }
      }
    })();
  </script>
</body>
</html>
