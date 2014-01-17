<?php // custom template for product single view
get_header();
?>
	<div id="content">
					<h1 class="post-title"><?php the_title(); ?></h1>
		<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
				<div class="product-photo"><?php mp_product_image(true, 'single', null); ?></div>
					<div class="product-details">
				<div class="content-box">
					<?php the_content(); ?>
				</div>
				<?php echo framemarket_product_meta(); ?>
			</div>
				<?php endwhile; else: ?>
					<p><?php _e( 'Sorry, no posts matched your criteria.', 'framemarket' ) ?></p>
				<?php endif; ?>
		<?php comments_template( '', true ); ?>
	</div>
<?php get_sidebar(); ?>
<?php get_footer() ?>