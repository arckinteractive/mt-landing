 		<?php include (get_template_directory() . '/buddypress/buddypress-globals.php'); ?>
<?php if ( ! have_posts() ) : ?>
	<?php if($bp_existed == 'true') : ?>
	<?php do_action( 'bp_before_blog_post' ) ?>
	<?php endif; ?>
	<div id="post-0" class="post error404 not-found">
		<h1 class="post-title"><?php _e( 'Not Found', 'framemarket'); ?></h1>
		<div class="post-content">
			<p><?php _e( 'Apologies, but no results were found for the requested archive. Perhaps searching will help find a related post.', 'framemarket'); ?></p>
			<?php get_search_form(); ?>
		</div>
	</div>
<?php endif; ?>

<?php while ( have_posts() ) : the_post(); ?>
	<?php if ( ( function_exists( 'get_post_format' ) && 'gallery' == get_post_format( $post->ID ) ) || in_category( _x( 'gallery', 'gallery category slug', 'framemarket') ) ) : ?>
		<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<h2 class="post-title"><a href="<?php the_permalink(); ?>" title="<?php printf( esc_attr__( 'Permalink to %s', 'framemarket'), the_title_attribute( 'echo=0' ) ); ?>" rel="bookmark"><?php the_title(); ?></a></h2>

			<div class="post-meta">
				<?php framemarket_postedon(); ?>
			</div>

			<div class="post-content">
<?php if ( post_password_required() ) : ?>
				<?php the_content(); ?>
<?php else : ?>
				<?php
					$images = get_children( array( 'post_parent' => $post->ID, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'orderby' => 'menu_order', 'order' => 'ASC', 'numberposts' => 999 ) );
					if ( $images ) :
						$total_images = count( $images );
						$image = array_shift( $images );
						$image_img_tag = wp_get_attachment_image( $image->ID, 'thumbnail' );
				?>
						<div class="gallery-thumb">
							<a class="size-thumbnail" href="<?php the_permalink(); ?>"><?php echo $image_img_tag; ?></a>
						</div>
						<p><em><?php printf( _n( 'This gallery contains <a %1$s>%2$s photo</a>.', 'This gallery contains <a %1$s>%2$s photos</a>.', $total_images, 'framemarket'),
								'href="' . get_permalink() . '" title="' . sprintf( esc_attr__( 'Permalink to %s', 'framemarket'), the_title_attribute( 'echo=0' ) ) . '" rel="bookmark"',
								number_format_i18n( $total_images )
							); ?></em></p>
				<?php endif; ?>
						<?php the_excerpt(); ?>
<?php endif; ?>
			</div>

			<div class="post-info">
			<?php if ( function_exists( 'get_post_format' ) && 'gallery' == get_post_format( $post->ID ) ) : ?>
				<a href="<?php echo get_post_format_link( 'gallery' ); ?>" title="<?php esc_attr_e( 'View Galleries', 'framemarket'); ?>"><?php _e( 'More Galleries', 'framemarket'); ?></a>
				<span class="meta-sep">|</span>
			<?php elseif ( in_category( _x( 'gallery', 'gallery category slug', 'framemarket') ) ) : ?>
				<a href="<?php echo get_term_link( _x( 'gallery', 'gallery category slug', 'framemarket'), 'category' ); ?>" title="<?php esc_attr_e( 'View posts in the Gallery category', 'framemarket'); ?>"><?php _e( 'More Galleries', 'framemarket'); ?></a>
				<span class="meta-sep">|</span>
			<?php endif; ?>
				<span class="comments-link"><?php comments_popup_link( __( 'Leave a comment', 'framemarket'), __( '1 Comment', 'framemarket'), __( '% Comments', 'framemarket') ); ?></span>
				<?php edit_post_link( __( 'Edit', 'framemarket'), '<span class="meta-sep">|</span> <span class="edit-link">', '</span>' ); ?>
			</div>
		</div>

	<?php elseif ( ( function_exists( 'get_post_format' ) && 'aside' == get_post_format( $post->ID ) ) || in_category( _x( 'asides', 'asides category slug', 'framemarket') )  ) : ?>
		<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		<?php if ( is_archive() || is_search() ) :  ?>
			<div class="entry-summary">
				<?php the_excerpt(); ?>
			</div>
		<?php else : ?>
			<div class="post-content">
				<?php the_content( __( 'Continue reading <span class="meta-nav">&rarr;</span>', 'framemarket') ); ?>
			</div>
		<?php endif; ?>

			<div class="post-info">
				<?php framemarket_postedon(); ?>
				<span class="meta-sep">|</span>
				<span class="comments-link"><?php comments_popup_link( __( 'Leave a comment', 'framemarket'), __( '1 Comment', 'framemarket'), __( '% Comments', 'framemarket') ); ?></span>
				<?php edit_post_link( __( 'Edit', 'framemarket'), '<span class="meta-sep">|</span> <span class="edit-link">', '</span>' ); ?>
			</div>
		</div>

	<?php else : ?>
		<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<h2 class="post-title"><a href="<?php the_permalink(); ?>" title="<?php printf( esc_attr__( 'Permalink to %s', 'framemarket'), the_title_attribute( 'echo=0' ) ); ?>" rel="bookmark"><?php the_title(); ?></a></h2>

			<div class="post-meta">
				<?php framemarket_postedon(); ?>
			</div>

	<?php if ( is_archive() || is_search() ) : ?>
			<div class="entry-summary">
				<?php the_excerpt(); ?>
			</div>
	<?php else : ?>
			<div class="post-content">
				<?php the_content( __( 'Continue reading <span class="meta-nav">&rarr;</span>', 'framemarket' ) ); ?>
				<?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'framemarket' ), 'after' => '</div>' ) ); ?>
			</div>
	<?php endif; ?>

			<div class="post-info">
				<?php if ( count( get_the_category() ) ) : ?>
					<span class="cat-links">
						<?php printf( __( '<span class="%1$s">Posted in</span> %2$s', 'framemarket'), 'post-info-prep post-info-prep-cat-links', get_the_category_list( ', ' ) ); ?>
					</span>
					<span class="meta-sep">|</span>
				<?php endif; ?>
				<?php
					$tags_list = get_the_tag_list( '', ', ' );
					if ( $tags_list ):
				?>
					<span class="tag-links">
						<?php printf( __( '<span class="%1$s">Tagged</span> %2$s', 'framemarket'), 'post-info-prep post-info-prep-tag-links', $tags_list ); ?>
					</span>
					<span class="meta-sep">|</span>
				<?php endif; ?>
				<span class="comments-link"><?php comments_popup_link( __( 'Leave a comment', 'framemarket'), __( '1 Comment', 'framemarket'), __( '% Comments', 'framemarket') ); ?></span>
				<?php edit_post_link( __( 'Edit', 'framemarket'), '<span class="meta-sep">|</span> <span class="edit-link">', '</span>' ); ?>
			</div>
		</div>
		<?php if($bp_existed == 'true') : ?>
		<?php do_action( 'bp_after_blog_post' ) ?>
		<?php endif; ?>
		<?php comments_template( '', true ); ?>

	<?php endif;  ?>

<?php endwhile;  ?>

<?php if (  $wp_query->max_num_pages > 1 ) : ?>
				<div id="navigation-bottom" class="navigation">
					<div class="nav-previous"><?php next_posts_link( __( '<span class="meta-nav">&larr;</span> Older posts', 'framemarket') ); ?></div>
					<div class="nav-next"><?php previous_posts_link( __( 'Newer posts <span class="meta-nav">&rarr;</span>', 'framemarket' ) ); ?></div>
				</div>
<?php endif; ?>