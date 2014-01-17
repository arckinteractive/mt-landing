 		<?php include (get_template_directory() . '/buddypress/buddypress-globals.php'); ?>
<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

				<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
					<h1 class="post-title"><?php the_title(); ?></h1>

					<div class="post-meta">
						<?php framemarket_postedon(); ?>
					</div>
					<div class="post-content">
						<?php the_content(); ?>
						<?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'framemarket'), 'after' => '</div>' ) ); ?>
					</div>

<?php if ( get_the_author_meta( 'description' ) ) :  ?>
					<div id="author-info">
						<div id="author-avatar">
							<?php echo get_avatar( get_the_author_meta( 'user_email' ), apply_filters( 'market_author_bio_avatar_size', 60 ) ); ?>
						</div>
						<div id="author-description">
							<h2><?php printf( esc_attr__( 'About %s', 'framemarket'), get_the_author() ); ?></h2>
							<?php the_author_meta( 'description' ); ?>
							<div id="author-link">
								<a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ); ?>">
									<?php printf( __( 'View all posts by %s <span class="meta-nav">&rarr;</span>', 'framemarket'), get_the_author() ); ?>
								</a>
							</div>
						</div>
					</div>
<?php endif; ?>

					<div class="post-info">
						<?php framemarket_postedin(); ?>
						<?php edit_post_link( __( 'Edit', 'framemarket'), '<span class="edit-link">', '</span>' ); ?>
					</div>
				</div>

				<div id="navigation-bottom" class="navigation">
					<div class="nav-next"><?php next_post_link('%link &rarr;'); ?></div>
					<div class="nav-previous"><?php previous_post_link('&larr; %link'); ?></div>
				</div>

				<?php comments_template( '', true ); ?>

<?php endwhile;  ?>