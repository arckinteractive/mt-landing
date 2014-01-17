<?php
global $grid_home;
$grid_home = true;

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
				$showpicker = isset($options['showinput']) ? $options['showinput'] : '';
				if ($showpicker == "Global"){
					?>
							<div id="mp-product-grid">
						<h1 class="post-title"><?php echo framemarket_page_title_output(); ?></h1>
						<?php echo mp_products_filter(); ?>
									<?php
									      $args = array();
									      $args['echo'] = false;
									      //check for paging
									      $args['page'] = isset($_REQUEST['page'])?$_REQUEST['page']:1;

									      $content = framemarket_mp_list_global_products( $args );

										echo $content;
									?>
								<div class="clear"></div>
							</div>
				<?php }  else {?>
					<h1 class="post-title"><?php _e( 'Our products', 'framemarket' ) ?></h1>
						<?php echo mp_products_filter(); ?>
						<div id="mp-product-grid">
							<?php
							$page = isset($_REQUEST['page'])?$_REQUEST['page']:1;
							framemarket_grid_mp_list_products(true, null, $page);
							?>
							<div class="clear"></div>
						</div>
				<?php } ?>
	<?php } else {?>
		<h1 class="post-title"><?php _e( 'Our products', 'framemarket' ) ?></h1>
			<?php echo mp_products_filter(); ?>
			<div id="mp-product-grid">
				<?php
				$page = isset($_REQUEST['page'])?$_REQUEST['page']:1;
				framemarket_grid_mp_list_products(true, null, $page);
				?>
				<div class="clear"></div>
			</div>
		<?php
	} } else { get_template_part( 'content'); } ?>
			<?php if($bp_existed == 'true') : ?>
			<?php do_action( 'bp_after_blog_home' ) ?>
			<?php endif; ?>
</div>
<?php get_sidebar(); ?>
<?php get_footer() ?>
