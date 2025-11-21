<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
        <script>
          window.API = {
            posts: "<?php echo site_url('api/posts'); ?>",
            pixabay: "<?php echo site_url('api/pixabay/search'); ?>",
            login: "<?php echo site_url('login'); ?>",
            dashboard: "<?php echo site_url('dashboard'); ?>",
            postsPage: "<?php echo site_url('posts'); ?>",
            createPost: "<?php echo site_url('posts/create'); ?>",
            editPostBase: "<?php echo site_url('posts/edit/'); ?>",
            viewPostBase: "<?php echo site_url('post/'); ?>",
            perPage: <?php echo (int) env('PAGINATION_PER_PAGE', 10); ?>
          };
        </script>
        <!-- jQuery added to satisfy DataTables build that expects jQuery (prevents "jQuery is not defined") -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
        <script src="https://cdn.datatables.net/2.0.7/js/dataTables.min.js"></script>
        <script src="<?php echo base_url('assets/js/app.js'); ?>"></script>
      </main>
    </div>
  </div>
</body>
</html>
