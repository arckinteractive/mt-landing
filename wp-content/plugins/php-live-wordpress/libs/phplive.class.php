<?php
	/* (c) OSI Codes Inc. */
	/* http://www.osicodesinc.com */
	/* Dev team: 615 */
	require_once('phplive.widget.php');

	CLASS phplive
	{
		protected static $instance ;
		protected $plugin_path = null ;
		protected $phplive_html_code = null ;

		protected function __construct()
		{
			add_action( 'widgets_init', create_function( null, 'return register_widget("phplive_widget") ;' ) ) ;
		}

		public static function get_instance()
		{
			if ( !isset( self::$instance ) ) { $phplive_class = __CLASS__ ; self::$instance = new $phplive_class ; }
			return self::$instance ;
		}

		public function fetch_phplive_wp_plugin_path()
		{
			if ( is_null( $this->plugin_path ) ) { $this->plugin_path = WP_PLUGIN_URL.'/php-live-wordpress/libs' ; }
			return $this->plugin_path ;
		}

		public function fetch_phplive_html_code()
		{
			$this->phplive_html_code = get_option( 'phplive_html_code' ) ;

			if ( is_null( $this->phplive_html_code ) || !$this->phplive_html_code ) { $this->phplive_html_code ; }
			return $this->phplive_html_code ;
		}

		public function widget_fetch_phplive_html_code()
		{
			$phplive_url_showhide = get_option( 'phplive_url_showhide' ) ;
			// disable check for now
			if( !$phplive_url_showhide && 0 )
				print "<div style=\"padding: 10px; background: #ECEEED; border: 1px solid #DDDDDD;\">PHP Live! for WordPress has not been <a href=\"wp-admin/admin.php?page=phplive_wp\">setup</a>.</div>" ;
			else
			{
				if ( $phplive_url_showhide != "hide" )
					print $this->fetch_phplive_html_code() ;
			}
		}
	}
?>
