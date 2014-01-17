<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
				<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
					<?php if ( is_front_page() ) { ?>
						<h1 class="post-title"><?php the_title(); ?></h1>
					<?php } else { ?>
						<h1 class="post-title"><?php the_title(); ?></h1>
					<?php } ?>
					<div class="post-content">
						<?php the_content(); ?>

						<div class="clear"></div>
						<?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'framemarket'), 'after' => '</div>' ) ); ?>
						<?php edit_post_link( __( 'Edit', 'framemarket'), '<span class="edit-link">', '</span>' ); ?>
					</div>
				</div>
				<?php comments_template( '', true ); ?>
<?php endwhile; ?>