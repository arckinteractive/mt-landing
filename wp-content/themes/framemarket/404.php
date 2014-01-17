<?php get_header(); ?>
<div id="content">
			<?php if($bp_existed == 'true') : ?>
				<?php do_action( 'bp_before_404' ) ?>
			<?php endif; ?>
			<div class="post error404 not-found">
				<h1 class="post-title"><?php _e( 'Not Found', 'framemarket'); ?></h1>
				<div class="post-content">
					<p><?php _e( 'Sorry we could not find that page.', 'framemarket'); ?></p>
						<?php get_search_form(); ?>
				</div>
						<?php if($bp_existed == 'true') : ?>
							<?php do_action( 'bp_404' ) ?>
						<?php endif; ?>
			</div>
					<?php if($bp_existed == 'true') : ?>
						<?php do_action( 'bp_after_404' ) ?>
					<?php endif; ?>
</div>
	<?php get_sidebar(); ?>
<?php get_footer(); ?>