<?php if(have_posts()) : ?><?php while(have_posts()) : the_post(); ?>

<article>
    <time class="meta"><?php the_time('F j Y') ?></time>
    <h2><a href="<?php echo get_permalink(); ?>"><?php the_title(); ?></a></h2>

    <?php the_content(''); ?>
    
    <!--<p class="more"><a href="<?php echo get_permalink(); ?>" class="simple small button secondary">Read post</a> &nbsp; <?php comments_popup_link('Post a Comment', '1 Comment', '% Comments') ?>. <?php if( has_tag() ) { the_tags('Tagged in: ',', '); } ?></p>-->
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