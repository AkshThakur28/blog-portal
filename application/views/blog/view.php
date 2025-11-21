<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo e($post['title']); ?> - Blog</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Crect width='16' height='16' rx='3' fill='%232563eb'/%3E%3Ctext x='8' y='11' font-size='10' text-anchor='middle' fill='white' font-family='Arial'%3EB%3C/text%3E%3C/svg%3E">
  <link rel="stylesheet" href="<?php echo base_url('assets/css/style.css'); ?>">
</head>
<body>
  <div class="container">
    <div class="header">
      <h1 class="no-margin"><?php echo e($post['title']); ?></h1>
      <div>
        <a class="btn btn-outline" href="<?php echo site_url(); ?>">All Posts</a>
        <a id="authLink" class="btn btn-outline" href="<?php echo site_url('login'); ?>" data-dash="<?php echo site_url('dashboard'); ?>" data-login="<?php echo site_url('login'); ?>">Admin Login</a>
      </div>
    </div>

    <div class="card">
      <div class="muted">By <?php echo e($post['author']); ?> • <?php echo date('M d, Y', strtotime($post['created_at'])); ?></div>

      <?php if (!empty($post['cover_media_url'])): ?>
        <div class="mt-2 mb-2">
          <?php if ($post['media_type'] === 'video'): ?>
            <video src="<?php echo e($post['cover_media_url']); ?>" class="media-video" controls></video>
          <?php else: ?>
            <img src="<?php echo e($post['cover_media_url']); ?>" alt="" class="media-img">
          <?php endif; ?>
          <div class="notice mt-1">Media via Pixabay</div>
        </div>
      <?php endif; ?>

      <div class="mt-2">
        <?php echo $post['body']; ?>
      </div>
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
