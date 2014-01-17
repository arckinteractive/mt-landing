<?php
/**
 * Layouts
 *
 * This is the layouts class, containing all processing and setup functionality
 * for managing the dimensions of the various layout types available.
 *
 * @package WooFramework
 * @subpackage Module
 *
 * CLASS INFORMATION
 *
 * Date Created: 2011-03-21.
 * Last Modified: 2013-06-27.
 * Author: Matty.
 * Since: 4.0.0
 *
 *
 * TABLE OF CONTENTS
 *
 * - public $plugin_prefix
 * - public $plugin_path
 * - public $plugin_url
 * - public $version
 *
 * - public $woo_options_prefix
 *
 * - public $admin_page
 *
 * - public $layouts
 * - public $layouts_info
 *
 * - public $gutter
 *
 * - function __construct ()
 * - function init ()
 * - function register_admin_screen ()
 * - function admin_screen ()
 * - function _generate_settings_html ()
 * - function _generate_sections_html ()
 * - function admin_screen_help ()
 * - function enqueue_scripts ()
 * - function enqueue_styles ()
 * - function get_layout_info ()
 * - function render_dynamic_css ()
 * - function generate_layout_css ()
 * - function setup_layouts ()
 * - function setup_layout_information ()
 * - function add_exporter_data ()
 */

class Woo_Layout {
	public $plugin_prefix;
	public $plugin_path;
	public $plugin_url;
	public $version;

	public $woo_options_prefix;

	public $admin_page;

	public $layouts;
	public $layouts_info;

	public $gutter;

	/**
	 * Class Constructor.
	 * @access  public
	 * @since   1.0.0
	 * @param   string $plugin_prefix Prefix to use in this class.
	 * @param   string $plugin_path   The path to this plugin.
	 * @param   string $plugin_url    The URL to this plugin.
	 * @param   string $version       Version number.
	 */
	public function __construct ( $plugin_prefix, $plugin_path, $plugin_url, $version ) {
		$this->plugin_prefix = $plugin_prefix;
		$this->plugin_path = $plugin_path;
		$this->plugin_url = $plugin_url;
		$this->version = $version;
		$this->woo_options_prefix = 'woo';

		$this->init();
	} // End Constructor

	/**
	 * Initialise the plugin.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function init () {
		if ( is_admin() ) {
			// Register the admin screen.
			add_action( 'admin_menu', array( $this, 'register_admin_screen' ), 11 );

			// Make sure our data is added to the WooFramework settings exporter.
			add_filter( 'wooframework_export_query_inner', array( $this, 'add_exporter_data' ) );
		}

		// Setup default layouts.
		$this->setup_layouts();

		// Setup default layout information.
		$this->setup_layout_information();

		// Generate the dynamic CSS data.
		if ( 'true' == get_option( $this->woo_options_prefix . '_layout_manager_enable', 'false' ) ) {
			add_action( 'wp_print_styles', array( $this, 'render_dynamic_css' ) );
		}
	} // End init()

	/**
	 * Register the admin screen within WordPress.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function register_admin_screen () {
		if ( function_exists( 'add_submenu_page' ) ) {
			$this->admin_page = add_submenu_page('woothemes', __( 'Layouts', 'woothemes' ), __( 'Layouts', 'woothemes' ), 'manage_options', 'woo-layout-manager', array( $this, 'admin_screen' ) );

			// Admin screen logic.
			add_action( 'load-' . $this->admin_page, array( $this, 'admin_screen_logic' ) );

			// Add contextual help tabs.
			add_action( 'load-' . $this->admin_page, array( $this, 'admin_screen_help' ) );

			// Admin screen JavaScript.
			add_action( 'admin_print_scripts-' . $this->admin_page, array( $this, 'enqueue_scripts' ) );

			// Admin screen CSS.
			add_action( 'admin_print_styles-' . $this->admin_page, array( $this, 'enqueue_styles' ) );
		}
	} // End register_admin_screen()

	/**
	 * Load the admin screen markup.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_screen () {
		// Keep the screen XHTML separate and load it from that file.
		include_once( $this->plugin_path . '/screens/admin.php' );
	} // End admin_screen()

	/**
	 * Generate the HTML for the various settings.
	 * @access  private
	 * @since   5.3.0
	 * @return  string Rendered HTML.
	 */
	private function _generate_settings_html () {
		$stored = array( '_layout_manager_enable' => get_option( $this->woo_options_prefix . '_layout_manager_enable', 'false' ) );
		$html = '<h3 class="title">' . __( 'Settings', 'woothemes' ) . '</h3>' . "\n";

		$html .= '<table class="form-table">' . "\n";
		$html .= '<tr>' . "\n";
			$html .= '<th scope="row">' . __( 'Enable Layouts', 'woothemes' ) . '</th>' . "\n";
			$html .= '<td>' . "\n";
				$html .= '<fieldset><legend class="screen-reader-text"><span>' . __( 'Enable Layouts', 'woothemes' ) . '</span></legend>' . "\n";
				$html .= '<label for="' . esc_attr( $this->woo_options_prefix . '_layout_manager_enable' ) . '"><input type="checkbox" name="' . esc_attr( $this->woo_options_prefix . '_layout_manager_enable' ) . '" id="' . esc_attr( $this->woo_options_prefix . '_layout_manager_enable' ) . '" value="true" ' . checked( 'true', $stored['_layout_manager_enable'], false ) . ' /> ' . __( 'Load in the customisations you make to the layouts, using the options below.', 'woothemes' ) . '</label>' . "\n";
				$html .= '</fieldset>' . "\n";
			$html .= '</td>' . "\n";
		$html .= '</tr>' . "\n";
		$html .= '</table>' . "\n";

		echo $html;
	} // End _generate_settings_html()

	/**
	 * Generate HTML to display a note about the currently-selected layout.
	 * @access  private
	 * @since   5.3.0
	 * @return  string Rendered HTML.
	 */
	private function _generate_layout_note () {
		$stored = array(
						'layout-width' => get_option( 'woo_layout_width', '940px' ),
						'layout-type' => get_option( 'woo_layout', 'two-col-left' ),
						'images-dir' => get_template_directory_uri() . '/functions/images/'
					);

			$html = '<div id="layout-width-notice">' . "\n";
			$html .= '<p><em>' . sprintf( __( 'Your current layout width is %s and your current layout type is %s.', 'woothemes' ), '<strong class="layout-width-value">' . $stored['layout-width'] . '</strong>', '<strong id="layout-type" class="' . esc_attr( $stored['layout-type'] ) . '">' . $this->layouts_info[$stored['layout-type']]['name'] . '</strong>' ) . '</em></p>' . "\n";
			$html .= '<p><em>' . sprintf( __( 'You can select your desired layout on the %stheme options%s screen.', 'woothemes' ), '<strong><a href="' . esc_url( admin_url( 'admin.php?page=woothemes' ) ) . '">', '</a></strong>' ) . '</em></p>' . "\n";
			$html .= '<input type="hidden" name="woo-framework-image-dir" value="' . esc_attr( $stored['images-dir'] ) . '" />' . "\n";
			$html .= '<input type="hidden" name="woo-gutter" value="' . esc_attr( $this->gutter ) . '" />' . "\n";
			$html .= '</div><!--/#layout-width-notice-->' . "\n";

		echo $html;
	} // End _generate_layout_note()

	/**
	 * Generate the HTML for the various sections.
	 * @access  private
	 * @since   5.3.0
	 * @return  string Rendered HTML.
	 */
	private function _generate_sections_html () {
		$html = '';

		if ( 0 < count( $this->layouts_info ) ) {
			foreach ( $this->layouts_info as $k => $v ) {
				$html .= '<div id="' . esc_attr( $k ) . '" class="section layout">' . "\n";
					$html .= '<h3 class="heading">' . $v['name'] . '</h3><!--/.heading-->' . "\n";
					$html .= '<div class="controls">' . "\n";

						if ( isset( $this->layouts[$k] ) ) {
							foreach ( $this->layouts[$k] as $i => $j ) {
								$html .= '<label for="">"' . ucwords( $i ) . '" ' . __( 'Column', 'woothemes' ) . '</label>' . "\n";
								$html .= '<input id="layouts-' . esc_attr( $k ) . '-' . esc_attr( $i ) . '" name="layouts[' . esc_attr( $k ) . '][' . esc_attr( $i ) . ']" value="' . intval( $j ) . '" maxlength="3" class="input-text-small woo-input" />%' . "\n";
								$html .= '<div class="clear"></div><!--/.clear-->';
							}
						}

					$html .= '</div><!--/.controls-->' . "\n";

					if ( isset( $v['description'] ) ) {
						$html .= '<div class="description">' . "\n";
							$html .= $v['description'] . "\n";
						$html .= '</div><!--/.description-->' . "\n";
					}

				$html .= '</div><!--/.section-->' . "\n";
				$html .= '<div class="clear"></div><!--/.clear-->' . "\n";
			}
		}

		echo $html;
	} // End _generate_sections_html()

	/**
	 * Load contextual help for the admin screen.
	 * @access  public
	 * @since   1.0.0
	 * @return  string Modified contextual help string.
	 */
	public function admin_screen_help () {
		$screen = get_current_screen();
		if ( $screen->id != $this->admin_page ) return;

		$overview =
			  '<p>' . __( 'Adjust the widths of the layout you\'d like to customise and hit the "Save Changes" button. It\'s as easy as that!', 'woothemes' ) . '</p>' .
			  '<p><strong>' . __( 'For more information:', 'woothemes' ) . '</strong></p>' .
			  '<p>' . sprintf( __( '<a href="%s" target="_blank">WooThemes Help Desk</a>', 'woothemes' ), 'http://support.woothemes.com/' ) . '</p>';

		$screen->add_help_tab( array( 'id' => 'layouts_overview', 'title' => __( 'Overview', 'woothemes' ), 'content' => $overview ) );
	} // End admin_screen_help()

	/**
	 * Logic to run on the admin screen.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_screen_logic () {
		$is_processed = false;

		// Save logic.
		if ( isset( $_POST['submit'] ) && check_admin_referer( 'woolayout-options-update' ) ) {
			$fields_to_skip = array( 'woolayout_update', '_wp_http_referer', '_wpnonce' );

			$posted_data = $_POST;

			// Update Layout Manager Enable
			if ( ( isset( $_POST['woo_layout_manager_enable'] ) ) && ( 'true' == $_POST['woo_layout_manager_enable'] )  ) {
				update_option( $this->woo_options_prefix . '_layout_manager_enable', 'true' );
			} else {
				update_option( $this->woo_options_prefix . '_layout_manager_enable', 'false' );
			}

			// Make sure we skip over the fields we don't need,
			// and validate the values that we do need to make sure
			// that they're all numeric values less than or equal to 100.

			foreach ( $posted_data as $k => $v ) {
				if ( in_array( $k, $fields_to_skip ) ) {
					unset( $posted_data[$k] );
				} else {
					// Get the woo_options array and update the necessary fields.
					$options = get_option( $this->woo_options_prefix . '_options' );
					$has_new_options = false;

					if ( is_array( $posted_data[$k] ) ) {
						$has_valid_values = true;

						// Validate the values.
						foreach ( $posted_data[$k] as $i => $j ) {
							foreach ( $posted_data[$k][$i] as $l => $m ) {
								if ( is_numeric( $m ) && ( $m <= 100 ) ) {} else { $has_valid_values = false; break; }
							}
						}

						// Set anything greater than 100 equal to 100.
						foreach ( $posted_data[$k] as $i => $j ) {
							foreach ( $posted_data[$k][$i] as $l => $m ) {
								if ( is_numeric( $m ) && ( $m <= 100 ) ) {} else { $posted_data[$k][$i][$l] = 100; }
							}
						}

						// Setup the values to be saved.
						if ( $has_valid_values ) {
							$posted_data[$k] = $v;
						}

						// Make sure that all values provided for each section add up to 100.
						foreach ( $posted_data[$k] as $i => $j ) {
							$total = 0;
							foreach ( $posted_data[$k][$i] as $l => $m ) {
								$total += $m;
							}

							if ( $total < 100 ) {
								$remainder = 100 - $total;
								$posted_data[$k][$i]['content'] += $remainder;
							}
						}
					} else {
						// Update non-layout options.
						update_option( $k, $v );

						if ( is_array( $options ) ) {
							$options[$k] = $v;
							$has_new_options = true;
						}
					}

					// If options in woo_options have been changed, update the woo_options array.
					if ( $has_new_options ) {
						update_option( $this->woo_options_prefix . '_options', $options );
					}
				}
			}

			if ( is_array( $posted_data ) ) {
				$is_updated = update_option( $this->plugin_prefix . 'stored_layouts', $posted_data );

				// Redirect to make sure the latest changes are reflected.
				wp_safe_redirect( admin_url( 'admin.php?page=woo-layout-manager&updated=true' ) );
				exit;
			}
			$is_processed = true;
		}
	} // End admin_screen_logic()

	/**
	 * Enqueue the JavaScript files for the admin screen.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function enqueue_scripts () {
		wp_register_script( 'jquery-layout-min', $this->plugin_url . 'assets/js/jquery.layout.min.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-draggable', 'jquery-ui-resizable' ), '1.2.0', true );
		wp_register_script( 'woo-layout-functions', $this->plugin_url . 'assets/js/functions.js', array( 'jquery', 'jquery-layout-min' ), '1.0.0', true );

		wp_enqueue_script( 'woo-layout-functions' );
	} // End enqueue_scripts()

	/**
	 * Enqueue the CSS files for the admin screen.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function enqueue_styles () {
		wp_register_style( 'woo-layout-interface', $this->plugin_url . '/assets/css/interface.css' );
		wp_enqueue_style( 'woo-layout-interface' );
	} // End enqueue_styles()

	/**
	 * Get layout info for the current screen.
	 * @access  public
	 * @since   1.0.0
	 * @return  array Layout data.
	 */
	public function get_layout_info () {
		global $post;

		// Determine the width and layout type in use.
		$layout = 'two-col-left';
		$container_width = 940;
		$container_class = '';

		$woo_layouts = get_option( 'woo_layout_' . 'stored_layouts' );
		$woo_options = get_option( 'woo_options' );

		$db_layout = get_option( 'woo_layout' );
		$db_layout_width = get_option( 'woo_layout_width' );

		if ( ! empty( $db_layout_width ) ) {
			$container_width = intval( $db_layout_width );
			if ( $container_width != '940px' ) { $container_class = '-' . $container_width; }
		}

		// If the selected page doesn't have a specific layout, use the default.
		if( ! empty( $db_layout ) ) { 
			$layout = $db_layout; 
		}
		$stored_layout = '';
		if ( is_singular() ) {
			$stored_layout = get_post_meta( $post->ID, 'layout', true );

			if ( '' != $stored_layout ) { $layout = $stored_layout; }
		} 

		$data = array();
		$data['layout'] = $layout;
		$data['container_width'] = $container_width;
		$data['container_class'] = $container_class;
		$data['one_percent'] = ( $container_width / 100 );
		$data['gutter'] = 3.23;
		$data['width_main'] = intval( $woo_layouts['layouts'][$layout]['content'] );
		if ( isset( $this->layouts[$layout] ) && array_key_exists( 'primary', $this->layouts[$layout] ) ) {
			$data['width_primary'] = intval( $woo_layouts['layouts'][$layout]['primary'] );
		} else {
			$data['width_primary'] = 0;
		}
		if ( isset( $this->layouts[$layout] ) && array_key_exists( 'secondary', $this->layouts[$layout] ) ) {
			$data['width_secondary'] = intval( $woo_layouts['layouts'][$layout]['secondary'] );
		} else {
			$data['width_secondary'] = 0;
		}

		if ( is_array( $woo_layouts ) && is_array( $woo_options ) ) {

			$data['layout'] = $layout;
			$data['container_width'] = $container_width;
			$data['container_class'] = $container_class;
			$data['one_percent'] = ( $container_width / 100 );
			$data['gutter'] = 3.23;

			if( isset( $woo_layouts['layouts'][$layout]['content'] ) ) $data['width_main'] = intval( $woo_layouts['layouts'][$layout]['content'] );
			if( isset( $woo_layouts['layouts'][$layout]['primary'] ) ) $data['width_primary'] = intval( $woo_layouts['layouts'][$layout]['primary'] );
			if( isset( $woo_layouts['layouts'][$layout]['secondary'] ) ) $data['width_secondary'] = intval( $woo_layouts['layouts'][$layout]['secondary'] );
			
		}

		return $data;
	} // End get_layout_info()

	/**
	 * Render the dynamic CSS data.
	 * @access  public
	 * @since   5.3.0
	 * @return  void
	 */
	public function render_dynamic_css () {
		echo '<style type="text/css">' . "\n" . $this->generate_layout_css() . "\n" . '</style>' . "\n";
	} // End render_dynamic_css()

	/**
	 * Generate dynamic layout CSS for the current screen.
	 * @access  public
	 * @since   5.3.0
	 * @return  string Generated CSS.
	 */
	public function generate_layout_css () {
		// Determine the width and layout type in use.
		$layout = 'two-col-left';
		$container_width = 940;
		$container_class = '';

		$data = $this->get_layout_info();

		$layout = $data['layout'];
		$container_width = $data['container_width'];
		$container_class = $data['container_class'];

		$one_percent = $data['one_percent'];

		// Setup the default gutter spacing.
		$gutter = $data['gutter'];

		// Begin output of dynamic CSS.
		$css = '';

		// Begin media query
		$css .= '@media only screen and (min-width: 768px) {' . "\n";

		$width_main = $data['width_main'];
		$width_primary = $data['width_primary'];
		$width_secondary = $data['width_secondary'];
		$width_maincontainer = $width_main + $width_primary;

		if ( $width_secondary ) { $width_maincontainer = $width_maincontainer - 1.08; }
		
		if( $width_secondary  == 0  || $width_primary == 0 ) { 
			$width_maincontainer = 100;
		}
		$css .= 'body.' . $layout . $container_class . ' #main-sidebar-container { width: ' . $width_maincontainer . '%' . '; }' . "\n";
		
		if ( $width_secondary ) { 
			$compensator = 2.15;
			$css .= 'body.' . $layout . $container_class . ' #sidebar-alt { width: ' . ( $width_secondary - $compensator ) . '%' . '; }' . "\n"; 
			$container_compensator = ( $compensator * 100 ) / $width_maincontainer;
			$width_main = ( ( $width_main * 100 ) / $width_maincontainer ) - $container_compensator;
			$width_primary = ( ( $width_primary * 100 ) / $width_maincontainer ) - $container_compensator;
		}

		if ( $width_primary ) {
			if ( ! $width_secondary) {
				$width_main -= $gutter/2;
				$width_primary -= $gutter/2;
			}
			$css .= 'body.' . $layout . $container_class . ' #main-sidebar-container #sidebar { width: ' . $width_primary . '%' . '; }' . "\n";
		}

		if ( $width_main ) {
			$css .= 'body.' . $layout . $container_class . ' #main-sidebar-container #main { width: ' . $width_main . '%' . '; }' . "\n";
		}
		
		$css .= '}';

		return $css;
	} // End generate_layout_css()

	/**
	 * Setup layout values, grouped by layout.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function setup_layouts() {
		$this->gutter = 10;

		$this->layouts = array();

		// One Column
		$one_col = array( 'content' => '100' );
		$this->layouts['one-col'] = $one_col;

		// Two Columns Left
		$two_col_left = array( 'content' => '65', 'primary' => '30' );
		$this->layouts['two-col-left'] = $two_col_left;

		// Two Columns Right
		$two_col_right = array( 'primary' => '30', 'content' => '65' );
		$this->layouts['two-col-right'] = $two_col_right;

		// Three Columns Left
		$three_col_left = array( 'content' => '55', 'primary' => '30', 'secondary' => '15' );
		$this->layouts['three-col-left'] = $three_col_left;

		// Three Columns Middle
		$three_col_middle = array( 'secondary' => '15', 'content' => '55', 'primary' => '30' );
		$this->layouts['three-col-middle'] = $three_col_middle;

		// Three Columns Right
		$three_col_right = array( 'secondary' => '15', 'primary' => '30', 'content' => '55' );
		$this->layouts['three-col-right'] = $three_col_right;

		// Merge the stored layout information with our current defaults.
		$stored_values = get_option( $this->plugin_prefix . 'stored_layouts' );

		if ( is_array( $stored_values ) && is_array( $stored_values['layouts'] ) && count( $stored_values['layouts'] ) ) {
			foreach ( $stored_values['layouts'] as $k => $v ) {
				if ( is_array( $v ) ) {
					$this->layouts[$k] = $v;
				}
			}
		}
	} // End setup_layouts()

 	/**
 	 * Setup layout meta information.
 	 * @access  public
 	 * @since   1.0.0
 	 * @return  void
 	 */
	public function setup_layout_information () {
		$this->layouts_info = array();

		// One Column
		$one_col = array( 'name' => __( 'One Column', 'woothemes' ), 'description' => __( 'One column with no sidebars.', 'woothemes' ) );
		$this->layouts_info['one-col'] = $one_col;

		// Two Columns Left
		$two_col_left = array( 'name' => __( 'Two Columns (Content on the Left)', 'woothemes' ), 'description' => __( 'Two columns with the main content column on the left.', 'woothemes' ) );
		$this->layouts_info['two-col-left'] = $two_col_left;

		// Two Columns Right
		$two_col_right = array( 'name' => __( 'Two Columns (Content on the Right)', 'woothemes' ), 'description' => __( 'Two columns with the main content column on the right.', 'woothemes' ) );
		$this->layouts_info['two-col-right'] = $two_col_right;

		// Three Columns Left
		$three_col_left = array( 'name' => __( 'Three Columns (Content on the Left)', 'woothemes' ), 'description' => __( 'Three columns with the main content column on the left.', 'woothemes' ) );
		$this->layouts_info['three-col-left'] = $three_col_left;

		// Three Columns Middle
		$three_col_middle = array( 'name' => __( 'Three Columns (Content in the Middle)', 'woothemes' ), 'description' => __( 'Three columns with the main content column in the middle.', 'woothemes' ) );
		$this->layouts_info['three-col-middle'] = $three_col_middle;

		// Three Columns Right
		$three_col_right = array( 'name' => __( 'Three Columns (Content on the Right)', 'woothemes' ), 'description' => __( 'Three columns with the main content column on the right.', 'woothemes' ) );
		$this->layouts_info['three-col-right'] = $three_col_right;
	} // End setup_layout_information()

	/**
 	 * Add our saved data to the WooFramework data exporter.
 	 * @access  public
	 * @since   1.0.0
 	 * @param   string $data SQL query.
 	 * @return  string SQL query.
 	 */
	public function add_exporter_data ( $data ) {
		$data .= " OR option_name = '" . $this->plugin_prefix . "stored_layouts" . "'";
		return $data;
	} // End add_exporter_data()

} // End Class
?>