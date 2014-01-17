<?php get_header(); ?>
<div id="content">
			<?php if($bp_existed == 'true') : ?>
				<?php do_action( 'bp_before_archive' ) ?>
			<?php endif; ?>
			<?php
				if ( have_posts() )
					the_post();
			?>
						<h1 class="post-title">
			<?php if ( is_day() ) : ?>
							<?php printf( __( 'Daily Archives: <span>%s</span>', 'framemarket'), get_the_date() ); ?>
			<?php elseif ( is_month() ) : ?>
							<?php printf( __( 'Monthly Archives: <span>%s</span>', 'framemarket'), get_the_date( 'F Y' ) ); ?>
			<?php elseif ( is_year() ) : ?>
							<?php printf( __( 'Yearly Archives: <span>%s</span>', 'framemarket'), get_the_date( 'Y' ) ); ?>
			<?php else : ?>
							<?php _e( 'Blog Archives', 'framemarket'); ?>
			<?php endif; ?>
						</h1>
			<?php
				rewind_posts();
				get_template_part( 'content');
			?>
			<?php if($bp_existed == 'true') : ?>
				<?php do_action( 'bp_after_archive' ) ?>
			<?php endif; ?>
</div>
	<?php get_sidebar(); ?>
<?php get_footer(); ?>