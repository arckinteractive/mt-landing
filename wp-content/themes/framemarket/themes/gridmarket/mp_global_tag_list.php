<?php // custom template for global tag list
get_header();
?>
<div id="content">
	<?php if ( class_exists( 'MarketPress' ) ) {
		?>
			<h1 class="post-title"><?php echo framemarket_page_title_output(); ?></h1>
			<div id="mp-product-grid">
					<?php
					if ( $slug = get_query_var('global_taxonomy') ) {
					      $args = array();
					      $args['echo'] = false;
					      $args['tag'] = $slug;

					      //check for paging
					      if (get_query_var('paged'))
					        $args['page'] = intval(get_query_var('paged'));

					      $content = framemarket_mp_list_global_products( $args );

					    } else { //no category set, so show list
					      $content .= mp_global_categories_list( array( 'echo' => false ) );
					    }
						echo $content;
					?>
				<div class="clear"></div>
			</div>
		<?php
	}
	?>
</div>
	<?php get_sidebar(); ?>
<?php get_footer(); ?>