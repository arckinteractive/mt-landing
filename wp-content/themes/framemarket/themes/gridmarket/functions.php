<?php
add_action( 'after_setup_theme', 'gridmarket_setup', 10 );
function gridmarket_setup() {

		global $content_width;
		if ( ! isset( $content_width ) ) {
			$content_width = 720;
		}
	}


function framemarket_enqueue_styles(){
	$version = '1.4';

	global $bp_existed;


	if ( (!is_admin()) && ($bp_existed == 'true') ) {
		wp_enqueue_style( 'buddypress-default', get_template_directory_uri() . '/buddypress/bp-default.css', array( 'framemarket' ), $version);
	}

	if ( !is_admin() ) {
		wp_enqueue_style( 'framemarket', get_template_directory_uri() . '/css/framemarket.css', array(), $version);

		wp_enqueue_style( 'gridmarket', get_stylesheet_directory_uri() . '/css/grid.css', array( 'framemarket' ), $version);

				$themename = wp_get_theme();
				$themeinput = $themename . '_styleinput';

				$options = get_option('framemarket_theme_options');
				$stylesheet = isset($options[$themeinput]) ? $options[$themeinput] : '';

				if ($stylesheet != ""){
					wp_enqueue_style( 'gridmarket_style', get_stylesheet_directory_uri() .  '/styles/' . $stylesheet . '.css', array( 'framemarket' ), $version);

				}
				else{
				wp_enqueue_style( 'gridmarket_orange', get_stylesheet_directory_uri() . '/styles/darkorange.css', array( 'framemarket' ), $version);
				}

				wp_enqueue_style( 'gridmarket_custom', get_stylesheet_directory_uri() . '/css/custom.css', array( 'framemarket' ), $version);
			 }

}

function gridmarket_widgets_init() {
	register_sidebar(
		array(
			'name'          => __( 'Footer one', 'framemarket' ),
			'id'            => 'footer-one',
			'description'   => 'Footer one',
			'before_widget' => '<div id="%1$s" class="footer-widget side widget %2$s">',
        	'after_widget' => '</div>',
        	'before_title' => '<h3 class="widgettitle">',
        	'after_title' => '</h3>'
		)
	);
	register_sidebar(
		array(
			'name'          => __( 'Footer two', 'framemarket' ),
			'id'            => 'footer-two',
			'description'   => 'Footer two',
		'before_widget' => '<div id="%1$s" class="footer-widget side widget %2$s">',
        	'after_widget' => '</div>',
        	'before_title' => '<h3 class="widgettitle">',
        	'after_title' => '</h3>'
		)
	);
	register_sidebar(
		array(
			'name'          => __( 'Footer three', 'framemarket' ),
			'id'            => 'footer-three',
			'description'   => 'Footer three',
			'before_widget' => '<div id="%1$s" class="footer-widget side widget %2$s">',
        	'after_widget' => '</div>',
        	'before_title' => '<h3 class="widgettitle">',
        	'after_title' => '</h3>'
		)
	);
	register_sidebar(
		array(
			'name'          => __( 'Footer four', 'framemarket' ),
			'id'            => 'footer-four',
			'description'   => 'Footer four',
			'before_widget' => '<div id="%1$s" class="footer-widget side widget %2$s">',
        	'after_widget' => '</div>',
        	'before_title' => '<h3 class="widgettitle">',
        	'after_title' => '</h3>'
		)
	);
	register_sidebar(
		array(
			'name'          => __( 'Footer five', 'framemarket' ),
			'id'            => 'footer-five',
			'description'   => 'Footer five',
			'before_widget' => '<div id="%1$s" class="footer-widget side widget %2$s end">',
        	'after_widget' => '</div>',
        	'before_title' => '<h3 class="widgettitle">',
        	'after_title' => '</h3>'
		)
	);
}
add_action( 'widgets_init', 'gridmarket_widgets_init' );
require_once ( get_stylesheet_directory() . '/functions/theme.php' );
?>