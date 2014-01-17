<?php
if ( ! function_exists( 'framemarket_setup' ) ) :
function framemarket_setup() {
	global $multi_site_on, $bp_existed;

	load_theme_textdomain('framemarket', get_template_directory() . '/languages/');
	$locale = get_locale();
	$locale_file = get_template_directory() . "/languages/$locale.php";
	if ( is_readable( $locale_file ) )
		require_once( $locale_file );

		add_theme_support('automatic-feed-links');
		add_theme_support('post-thumbnails');
		$defaults = array(
			'default-color'          => '',
			'default-image'          => '',
			'wp-head-callback'       => '_custom_background_cb',
			'admin-head-callback'    => '',
			'admin-preview-callback' => ''
		);
		add_theme_support( 'custom-background', $defaults );

		add_theme_support( 'post-formats', array( 'aside', 'gallery', 'link', 'image', 'quote', 'status', 'video', 'audio', 	'chat') );
		register_nav_menu('main_menu', __('Main Menu'));
		add_filter( 'wp_page_menu_args', 'framemarket_page_menu_args' );
		add_filter( 'excerpt_length', 'framemarket_excerpt_length' );
		//add_custom_image_header( 'framemarket_header_style', 'framemarket_admin_header_style' );
		$defaults = array(
			'default-image'          => '',
			'random-default'         => false,
			'width'                  => 0,
			'height'                 => 0,
			'flex-height'            => false,
			'flex-width'             => false,
			'default-text-color'     => '',
			'header-text'            => true,
			'uploads'                => true,
			'wp-head-callback'       => 'framemarket_header_style',
			'admin-head-callback'    => 'framemarket_header_style',
			'admin-preview-callback' => 'framemarket_header_style',
		);

		global $content_width;
		if ( ! isset( $content_width ) ) {
			$content_width = 720;
		}

		if(!defined('HEADER_TEXTCOLOR')) define( 'HEADER_TEXTCOLOR', '' );
		if(!defined('HEADER_IMAGE')) define('HEADER_IMAGE', get_stylesheet_directory_uri().'library/images/headers/default.jpg');
		if(!defined('HEADER_IMAGE_WIDTH')) define( 'HEADER_IMAGE_WIDTH', apply_filters( 'framemarket_header_image_width', 980 ) );
		if(!defined('HEADER_IMAGE_HEIGHT')) define('HEADER_IMAGE_HEIGHT', apply_filters('framemarket_header_image_height', 100));
		if(!defined('NO_HEADER_TEXT')) define( 'NO_HEADER_TEXT', true );

		add_theme_support( 'custom-header', $defaults );



			function framemarket_header_style() {
			?>
			<style type="text/css">
			   #branding h1 a{
			            background: url(<?php header_image(); ?>) no-repeat;
			        }
			</style>
			<?php
			}


			function framemarket_admin_header_style() {
			?>
			<style type="text/css">
					#headimg {
			            width: <?php echo HEADER_IMAGE_WIDTH; ?>px;
			            height: <?php echo HEADER_IMAGE_HEIGHT; ?>px;
					}
			</style>
			<?php
			}

	add_action( 'wp_enqueue_scripts', 'framemarket_load_scripts' );
	add_action( 'widgets_init', 'framemarket_widgets_init' );
	add_action( 'wp_enqueue_scripts', 'framemarket_enqueue_styles' );

	require( dirname( __FILE__ ) . '/library/functions/conditional-functions.php' );
	if($bp_existed == 'true') {
		require( dirname( __FILE__ ) . '/library/functions/bp-functions.php' );
		add_filter( 'comment_form_defaults', 'wpmudev_comment_form', 10 );
	}

	require_once ( TEMPLATEPATH . '/options/options.php' );
	require_once ( TEMPLATEPATH . '/options/customizer.php' );

	require( dirname( __FILE__ ) . '/library/functions/theme.php' );

	if ( class_exists( 'MarketPress' ) ) {
		require( dirname( __FILE__ ) . '/library/functions/marketpress.php');
	}

}
endif;
add_action( 'after_setup_theme', 'framemarket_setup');

if ( ! function_exists( 'framemarket_enqueue_styles' ) ) :
function framemarket_enqueue_styles(){
	$version = '1.4';

	global $bp_existed;
	$themename = wp_get_theme();
	if ( (!is_admin()) && ($bp_existed == 'true') ) {
		wp_enqueue_style( 'buddypress-default', get_template_directory_uri() . '/buddypress/bp-default.css', array(), $version);
	}

	if ( (!is_admin()) && ($themename == "FrameMarket") ) {
		wp_enqueue_style( 'framemarket-default', get_template_directory_uri() . '/css/basicstyles.css', array() , $version);
	 }
	if (!is_admin()){
		wp_enqueue_style( 'framemarket', get_template_directory_uri() . '/css/framemarket.css', array( 'framemarket-default' ), $version);
	}

}
endif;

if ( ! function_exists( 'framemarket_load_scripts' ) ) :
function framemarket_load_scripts() {
	$version = '1.4';
	if ( !is_admin() ) {
		wp_enqueue_script("jquery");

		if ( class_exists( 'MarketPress' ) ) {
			wp_enqueue_script( "jquery-mutations", get_template_directory_uri() . "/library/scripts/mutations/mutations.core.js", array('jquery') , $version);
			wp_enqueue_script( "jquery-mutations-attr",get_template_directory_uri() . "/library/scripts/mutations/mutations.attr.js", array('jquery-mutations') , $version);
			wp_enqueue_script( "jquery-mutations-html", get_template_directory_uri() . "/library/scripts/mutations/mutations.html.js", array('jquery-mutations', 'jquery-mutations-attr') , $version);
			wp_enqueue_script( "slidingcart", get_template_directory_uri() . "/library/scripts/slidingcart.js", array('jquery', 'jquery-mutations', 'jquery-mutations-attr', 'jquery-mutations-html') , $version);

			add_filter( 'mp_register_post_type', 'framemarket_commentson' );
			function framemarket_commentson( $args) {
				$args['supports'] = array_merge($args['supports'], array('comments'));
				return $args;
			}

			global $mp;
			remove_action( 'template_redirect', array(&$mp, 'load_store_theme') );
		}

		if ( is_singular() && get_option( 'thread_comments' ) && comments_open() )
		wp_enqueue_script( 'comment-reply' );
	}
}
endif;

function framemarket_widgets_init() {
	global $bp_existed;
	register_sidebar(
		array(
			'name'          => __( 'Sidebar', 'framemarket' ),
			'id'            => 'sidebar',
			'description'   => 'Sidebar',
			'before_widget' => '',
			'after_widget' => '</div>',
       		'before_title' => '<h3 class="widgettitle">',
       		'after_title' => '</h3><div id="%1$s" class="widget %2$s">'
			)
	);
	if($bp_existed == 'true') {
	register_sidebar(
		array(
			'name'          => __( 'BuddyPress sidebar', 'framemarket' ),
			'id'            => 'sidebar-members',
			'description'   => 'BuddyPress sidebar',
			'before_widget' => '',
	        'after_widget' => '</div>',
	        'before_title' => '<h3 class="widgettitle">',
	        'after_title' => '</h3><div id="%1$s" class="widget %2$s">'
		)
	);
	}
}
add_action( 'widgets_init', 'framemarket_widgets_init' );


/* -------------------- Update Notifications Notice -------------------- */
if ( !function_exists( 'wdp_un_check' ) ) {
  add_action( 'admin_notices', 'wdp_un_check', 5 );
  add_action( 'network_admin_notices', 'wdp_un_check', 5 );
  function wdp_un_check() {
    if ( !class_exists( 'WPMUDEV_Update_Notifications' ) && current_user_can( 'edit_users' ) )
      echo '<div class="error fade"><p>' . __('Please install the latest version of <a href="http://premium.wpmudev.org/project/update-notifications/" title="Download Now &raquo;">our free Update Notifications plugin</a> which helps you stay up-to-date with the most stable, secure versions of WPMU DEV themes and plugins. <a href="http://premium.wpmudev.org/wpmu-dev/update-notifications-plugin-information/">More information &raquo;</a>', 'wpmudev') . '</a></p></div>';
  }
}

?>