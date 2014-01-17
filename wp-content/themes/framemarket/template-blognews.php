<?php
/*
Template Name: blog news
*/
?>

<?php get_header(); ?>
<div id="content">
			<?php if($bp_existed == 'true') : ?>
				<?php do_action( 'bp_before_blog_home' ) ?>
			<?php endif; ?>
				<?php
					rewind_posts();
					$page = (get_query_var('paged')) ? get_query_var('paged') : 1;
					$posts_per_page = get_option('posts_per_page');
					query_posts("cat=&showposts=$posts_per_page&paged=$page");
					?>

					<?php
					while ( have_posts() ) : the_post();?>


				<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
							<?php if($bp_existed == 'true') : ?>
									<?php do_action( 'bp_before_blog_post' ) ?>
							<?php endif; ?>
					<h1 class="post-title"><a href="<?php the_permalink(); ?>" title="<?php printf( esc_attr__( 'Permalink to %s', 'framemarket' ), the_title_attribute( 'echo=0' ) ); ?>" rel="bookmark"><?php the_title(); ?></a></h1>

					<div class="post-meta">
						<?php framemarket_postedon(); ?>
					</div>
						<div class="entry-summary">
							<?php the_excerpt(); ?>
						</div>
							<div class="post-info">
								<?php if ( count( get_the_category() ) ) : ?>
									<span class="cat-links">
										<?php printf( __( '<span class="%1$s">Posted in</span> %2$s', 'framemarket' ), 'post-info-prep post-info-prep-cat-links', get_the_category_list( ', ' ) ); ?>
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
								<span class="comments-link"><?php comments_popup_link( __( 'Leave a comment', 'framemarket' ), __( '1 Comment', 'framemarket' ), __( '% Comments', 'framemarket') ); ?></span>
								<?php edit_post_link( __( 'Edit', 'framemarket'), '<span class="meta-sep">|</span> <span class="edit-link">', '</span>' ); ?>
								<?php if($bp_existed == 'true') : ?>
										<?php do_action( 'bp_after_blog_post' ) ?>
								<?php endif; ?>
							</div>
						</div>
			<?php endwhile; ?>
			<?php if (  $wp_query->max_num_pages > 1 ) : ?>
							<div id="navigation-bottom" class="navigation">
								<div class="nav-previous"><?php next_posts_link( __( '<span class="meta-nav">&larr;</span> Older posts', 'framemarket') ); ?></div>
								<div class="nav-next"><?php previous_posts_link( __( 'Newer posts <span class="meta-nav">&rarr;</span>', 'framemarket' ) ); ?></div>
							</div>
			<?php endif; ?>
</div>
	<?php get_sidebar(); ?>
	<div class="clear"></div>
<?php get_footer() ?>