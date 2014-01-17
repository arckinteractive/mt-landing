<?php get_header(); ?>
<div id="content">
	<?php if($bp_existed == 'true') : ?>
	<?php do_action( 'bp_before_blog_search' ) ?>
	<?php endif; ?>
			<?php if ( have_posts() ) : ?>
											<h1 class="post-title"><?php printf( __( 'Search Results for: %s', 'framemarket'), '<span>' . get_search_query() . '</span>' ); ?></h1>
							<?php
							 get_template_part( 'content');
							?>
			<?php else : ?>
							<div id="post-0" class="post no-results not-found">
								<h2 class="post-title"><?php _e( 'Nothing Found', 'framemarket'); ?></h2>
								<div class="post-content">
									<p><?php _e( 'Sorry, but nothing matched your search criteria. Please try again with some different keywords.', 'framemarket'); ?></p>
									<?php get_search_form(); ?>
									</div>
									</div>
									<?php endif; ?>
									<?php if($bp_existed == 'true') : ?>
									<?php do_action( 'bp_after_blog_search' ) ?>
									<?php endif; ?>
</div>
	<?php get_sidebar(); ?>
<?php get_footer() ?>