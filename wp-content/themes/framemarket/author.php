<?php get_header(); ?>
<div id="content">
			<?php
				if ( have_posts() )
					the_post();
			?>
							<h1 class="post-title author"><?php printf( __( 'Author Archives: %s', 'framemarket'), "<span class='vcard'><a class='url fn n' href='" . get_author_posts_url( get_the_author_meta( 'ID' ) ) . "' title='" . esc_attr( get_the_author() ) . "' rel='me'>" . get_the_author() . "</a></span>" ); ?></h1>
			<?php
			if ( get_the_author_meta( 'description' ) ) : ?>
								<div id="author-info">
									<div id="author-avatar">
										<?php echo get_avatar( get_the_author_meta( 'user_email' ), apply_filters( 'market_author_bio_avatar_size', 60 ) ); ?>
									</div>
									<div id="author-description">
										<h2><?php printf( __( 'About %s', 'framemarket'), get_the_author() ); ?></h2>
										<?php the_author_meta( 'description' ); ?>
									</div>
								</div>
			<?php endif; ?>

			<?php
				rewind_posts();
				get_template_part( 'content');
			?>
</div>
	<?php get_sidebar(); ?>
<?php get_footer() ?>