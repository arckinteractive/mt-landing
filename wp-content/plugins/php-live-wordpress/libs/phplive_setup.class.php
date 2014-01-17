<?php
	/* (c) OSI Codes Inc. */
	/* http://www.osicodesinc.com */
	/* Dev team: 615 */
	require_once( 'phplive.class.php' ) ;

	FINAL CLASS phplive_admin EXTENDS phplive
	{
		protected function __construct()
		{
			parent::__construct() ;
			// [v] admin_menu
			add_action( 'admin_menu', Array( $this, 'phplive_admin_menu' ) ) ;
			wp_enqueue_style( 'phplive_wp', $this->fetch_phplive_wp_plugin_path().'/css/style.css' ) ;
			add_action( 'wp_ajax_my_action', Array( $this, 'phplive_admin_ajax' ) ) ;
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG == true ) { add_action( 'init', Array( $this, 'error_reporting' ) ) ; }
		}

		public static function get_instance()
		{
			if ( !isset( self::$instance ) ) { $class = __CLASS__ ; self::$instance = new $class ; }
			return self::$instance ;
		}

		public function error_reporting() { error_reporting(E_ALL & ~E_USER_NOTICE) ; }

		public function phplive_admin_menu()
		{
			add_menu_page( 'PHP Live! WordPress', 'PHP Live!', 'administrator', 'phplive_wp', Array($this, 'phplive_admin_html'), $this->fetch_phplive_wp_plugin_path().'/pics/phplive.png' ) ;
		}

		public function phplive_admin_html()
		{
			$phplive_html_code = get_option( 'phplive_html_code' ) ;
			$phplive_url_showhide = get_option( 'phplive_url_showhide' ) ;
			global $current_user ;

			get_currentuserinfo() ;
			// for future reference of populating other options
			$name = $current_user->display_name ;
			$lname = $current_user->user_lastname ;
			$email = $current_user->user_email ;

			$wp_output = '<script type="text/javascript" src="'.$this->fetch_phplive_wp_plugin_path().'/js/pre_load.js"></script>' ;
			$wp_output .= '<div style="padding-top: 20px; padding-right: 20px;">' ;

			$div_html = $div_settings = "" ;

			$wp_output .= '<div class="phplive_wp_menu_wrapper" style="min-width: 620px;"><div id="menu_html" class="phplive_wp_menu" onClick="phplive_wp_launch(\'html\')"><img src="'.$this->fetch_phplive_wp_plugin_path().'/pics/wordpress.png" width="16" height="16" border="0"> Paste your PHP Live! HTML Code Here</div><div id="menu_settings" class="phplive_wp_menu" onClick="phplive_wp_launch(\'settings\')"><img src="'.$this->fetch_phplive_wp_plugin_path().'/pics/settings.png" width="16" height="16" border="0"> Reset</div><div style="clear: both;"></div></div>' ;

			$phplive_url_show = ( ( $phplive_url_showhide == "show" ) || !$phplive_url_showhide ) ? "checked" : "" ;
			$phplive_url_hide = ( $phplive_url_showhide == "hide" ) ? "checked" : "" ;
			$div_html = "
				<form>
					<div><img src='".$this->fetch_phplive_wp_plugin_path()."/pics/page_white_code.png' width='16' height='16' border='0'> A PHP Live! system must already be setup, either the <a href='http://www.phplivesupport.com/r.php?r=pt' target='new'>FREE Trial</a>, <a href='http://www.phplivesupport.com/r.php?r=pd' target='new'>Download Solution</a> or the <a href='http://www.phplivesupport.com/r.php?r=po' target='new'>On Demand Solution</a>.  Paste your PHP Live! HTML Code here to integate PHP Live! with your WordPress website.</div>
					<div style='margin-top: 15px; display: none;' class='phplive_wp_info_good' id='div_alert'>Update Success</div>
					<div style='margin-top: 15px;'><textarea id='phplive_html_code' rows=8 wrap='virtual' style='padding: 5px; width: 100%;'>$phplive_html_code</textarea></div>

					<!-- <div style='margin-top: 15px;'>
						Toggle to display or hide the live chat icon.
						<div style='margin-top: 15px;'>
							<input type='radio' name='phplive_url_showhide' id='phplive_url_show' ".$phplive_url_show."> Display chat icon.
							<input type='radio' name='phplive_url_showhide' id='phplive_url_hide' ".$phplive_url_hide."> Hide chat icon.
						</div>
					</div> -->
					<div style='margin-top: 15px;'><input type='button' value='Update HTML Code' id='submit' class='button-primary' onClick='phplive_wp_sethtml()'></div>

					<div style='margin-top: 25px;' class='phplive_wp_info_box'>Don't forget to move the PHP Live! widget to your WordPress <a href='widgets.php'>widgets</a> area.</div>
				</form>
			" ;

			$div_settings = "
				<form>
					<div style='font-size: 14px; font-weight: bold;'>Reset the PHP Live! addon values.</div>
					<div style='margin-top: 15px;'>Reset will clear the PHP Live! HTML Code.  Reset does not uninstall the plugin or remove the actual PHP Live! system.</div>
					<div style='margin-top: 25px;'><input type='button' value='Reset' id='submit' class='button-primary' onClick='phplive_wp_reset()'></div>
				</form>
			" ;

			$wp_output .= '<div id="phplive_setup_body_wrapper" style="text-align: justify;"><div id="phplive_setup_body_html" style="padding-top: 15px; padding-bottom: 15px;">'.$div_html.'</div><div id="phplive_setup_body_settings" style="display: none; padding-top: 15px; padding-bottom: 15px;">'.$div_settings.'</div></div>' ;
			$wp_output .= '</div><script type="text/javascript" src="'.$this->fetch_phplive_wp_plugin_path().'/js/load.js"></script>' ;

			print $wp_output ;
		}

		public function phplive_admin_ajax()
		{
			if ( isset( $_POST["action"] ) && isset( $_POST["action_sub"] ) )
			{
				if ( $_POST["action_sub"] == "set_html" )
				{
					$phplive_html_code = isset( $_POST['phplive_html_code'] ) ? $_POST['phplive_html_code'] : "" ;
					$phplive_url_showhide = isset( $_POST["phplive_url_showhide"] ) ? $_POST["phplive_url_showhide"] : "" ;
					if ( $phplive_html_code && $phplive_url_showhide ) {
						update_option( 'phplive_html_code', preg_replace( "/%plus%/", "+", urldecode( $phplive_html_code ) ) ) ;
						update_option( 'phplive_url_showhide', $phplive_url_showhide ) ;
						$json_data = "json_data = { \"status\": 1, \"error\": \"\" };" ;
					}
					else
						$json_data = "json_data = { \"status\": 0, \"error\": \"Invalid PHP Live! HTML Code.\" };" ;
				}
				else if ( $_POST["action_sub"] == "reset" )
				{
					delete_option( 'phplive_html_code' ) ;
					delete_option( 'phplive_url_showhide' ) ;
					$json_data = "json_data = { \"status\": 1, \"error\": \"\" };" ;
				}
				else
					$json_data = "json_data = { \"status\": 1, \"error\": \"Invalid sub action.\" };" ;

			}
			else
				$json_data = "json_data = { \"status\": 0, \"error\": \"Invalid action.\" };" ;
		
			print $json_data ; die() ;
		}

	}
?>
