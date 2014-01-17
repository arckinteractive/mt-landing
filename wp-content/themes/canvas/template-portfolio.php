<?php
/**
 * Template Name: Portfolio
 *
 * The portfolio page template displays your portfolio items with
 * a switcher to quickly filter between the various portfolio galleries. 
 *
 * @package WooFramework
 * @subpackage Template
 */

 global $woo_options; 
 get_header();
?>
    <!-- #content Starts -->
	<?php woo_content_before(); ?>
    <div id="content" class="col-full">
    
    	<div id="main-sidebar-container">    

            <!-- #main Starts -->
            <?php woo_main_before(); ?>
            <section id="main"> 
<?php
	woo_loop_before();
	
	// Show page content first
	if (have_posts()) { $count = 0;
		while (have_posts()) { the_post(); $count++;
			woo_get_template_part( 'content', 'page-template-business' ); // Use business content so we don't output a page title
		}
	}

	// Load portfolio gallery	
	get_template_part( 'loop', 'portfolio' );

	woo_loop_after();
?>
            </section><!-- /#main -->
            <?php woo_main_after(); ?>
    
            <?php get_sidebar(); ?>

		</div><!-- /#main-sidebar-container -->         

		<?php get_sidebar( 'alt' ); ?>

    </div><!-- /#content -->
	<?php woo_content_after(); ?>

<?php get_footer(); ?>