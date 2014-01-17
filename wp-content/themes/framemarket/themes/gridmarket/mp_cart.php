<?php
// custom template for shopping cart
get_header();
?>
<div id="content">
					<h1 class="post-title"><?php _e( 'Checkout', 'framemarket' ) ?></h1>
		<?php mp_show_cart('checkout'); ?>
		<div class="clear"></div>
</div>
	<?php get_sidebar(); ?>
<?php get_footer(); ?>