<?php if(have_posts()) : ?><?php while(have_posts()) : the_post(); ?>

<article>
    <span class="byline"><time class="meta"><?php the_time('F j Y') ?></time> by Judd Kessler</span>
    <h1><?php the_title(); ?></h1>
    
    <?php the_content(); ?>
    
    <p class="more"><?php if( has_tag() ) { the_tags('Tagged in: ',', '); } ?></p>
</article>

<?php endwhile; ?>
<?php endif; ?>
