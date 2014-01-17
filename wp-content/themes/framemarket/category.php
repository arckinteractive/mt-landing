<?php get_header(); ?>
<div id="content">
	<?php if($bp_existed == 'true') : ?>
		<?php do_action( 'bp_before_archive' ) ?>
	<?php endif; ?>
									<h1 class="post-title"><?php
						printf( __( 'Category Archives: %s', 'framemarket'), '<span>' . single_cat_title( '', false ) . '</span>' );
					?></h1>
					<?php
						$category_description = category_description();
						if ( ! empty( $category_description ) )
							echo '<div class="archive-meta">' . $category_description . '</div>';

					get_template_part( 'content');
					?>
						<?php if($bp_existed == 'true') : ?>
							<?php do_action( 'bp_after_archive' ) ?>
						<?php endif; ?>
				</div>
			<?php get_sidebar(); ?>
<?php get_footer() ?>