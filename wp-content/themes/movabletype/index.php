<?php get_header(); ?>

<div class="page-main">

  <div class="row">
    <div class="large-8 large-centered12 columns">
      <div class="page-content">
        
        <?php if(have_posts()) : ?><?php while(have_posts()) : the_post(); ?>

          <article>
            <h2><a href="<?php echo get_permalink(); ?>"><?php the_title(); ?></a></h2>
            <time class="meta"><?php the_time('F j Y') ?></time>
            <?php the_content(); ?>
          </article>

          <hr />

          <?php endwhile; ?>

           <div class="row">
             <div class="twelve columns">
               <div class="navigation">
                 <?php posts_nav_link(); ?>
               </div>
             </div>
           </div>

        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<?php get_footer(); ?>