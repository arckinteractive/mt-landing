<?php

get_header(); ?>
<div id="content">
			<?php if($bp_existed == 'true') : ?>
			<?php do_action( 'bp_before_blog_home' ) ?>
			<?php endif; ?>
	<?php if ( class_exists( 'MarketPress' ) ) {
		?>
		<?php  if ( is_multisite() ) {?>
			<?php
				$options = get_option('framemarket_theme_options');
				$searchbar = isset($options['searchinput']) ? $options['searchinput'] : '';
				if ($searchbar == "Global Product"){
					?>
							<div id="mp-product-grid">
						<h1 class="post-title"><?php printf( __( 'Marketplace Search Results for: %s', 'framemarket'), '<span>' . get_search_query() . '</span>' ); ?></h1>
									<?php
									      $args = array();
									      $args['echo'] = false;
									      //check for paging
									      if (get_query_var('paged'))
									        $args['page'] = intval(get_query_var('paged'));
										  $args['s'] = get_query_var('s');

									      $content = framemarket_mp_list_global_products( $args );

										echo $content;
									?>
								<div class="clear"></div>
							</div>
				<?php }  else if ($searchbar == "Local Product") {?>
					<h1 class="post-title"><?php printf( __( 'Search Results for: %s', 'framemarket'), '<span>' . get_search_query() . '</span>' ); ?></h1>
						<div id="mp-product-grid">
							<?php
							framemarket_grid_mp_list_products();
							?>
							<div class="clear"></div>
						</div>
				<?php } else { get_template_part( 'content'); } ?>
	<?php } else if ($searchbar == "Local Product") {?>
		<h1 class="post-title"><?php printf( __( 'Search Results for: %s', 'framemarket'), '<span>' . get_search_query() . '</span>' ); ?></h1>
			<div id="mp-product-grid">
				<?php
				framemarket_grid_mp_list_products();
				?>
				<div class="clear"></div>
			</div>
		<?php
			} else { get_template_part( 'content'); }
		} else { get_template_part( 'content'); } ?>
			<?php if($bp_existed == 'true') : ?>
			<?php do_action( 'bp_after_blog_home' ) ?>
			<?php endif; ?>
</div>
<?php get_sidebar(); ?>
<?php get_footer() ?>
