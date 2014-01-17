<?php get_header(); ?>

<div class="page-main">

  <div class="row">
      <div class="large-8 small-12 columns">
        <div class="page-content">
          <!-- Start Template Part: Post Loop -->
            <?php get_template_part( 'includes/post' ); ?>
            <!-- End Template Part: Post Loop -->

            <div class="comments-template">
              <?php comments_template(); ?>
            </div>
        </div>
      </div>

      <div class="large-4 small-12 columns sidebar">
          <?php get_template_part( 'includes/sidebar' ); ?>
      </div>
  </div>
</div>

<?php get_footer(); ?>