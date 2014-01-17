<?php
/**
 * Hooks
 *
 * This is the hooks class, containing all processing and setup functionality
 * for inputting custom content at the various WooFramework hook calls.
 *
 * @package WooFramework
 * @subpackage Module
 *
 *
 * CLASS INFORMATION
 *
 * Date Created: 2011-04-19.
 * Last Modified: 2013-06-26.
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
 * - public $admin_page
 * - public $hooks
 * - public $hook_titles
 *
 * - public $stored_data
 *
 * - function Woo_Hook_Manager () (Constructor)
 * - function init ()
 * - function _generate_sections_menu ()
 * - function _generate_sections_html ()
 * - function register_admin_screen ()
 * - function admin_screen ()
 * - function admin_screen_help ()
 * - function enqueue_scripts ()
 * - function enqueue_styles ()
 * - function create_hooks ()
 * - function execute_hook ()
 * - function setup_hook_data ()
 * - function setup_hook_titles ()
 * - function add_exporter_data ()
 *
 */
class Woo_Hook_Manager {
	public $plugin_prefix;
	public $plugin_path;
	public $plugin_url;
	public $version;

	public $admin_page;
	public $hooks;
	public $hook_titles;

	public $stored_data;

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

		$this->init();
	} // End Constructor

	/**
	 * Initialise the plugin.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function init () {
		// Create the necessary filters.
		add_action( 'after_setup_theme', array( $this, 'create_hooks' ), 10 );

		if ( is_admin() ) {
			// Setup hook areas.
			$this->setup_hook_data();

			// Register the admin screen.
			add_action( 'admin_menu', array( $this, 'register_admin_screen' ), 20 );

			// Make sure our data is added to the WooFramework settings exporter.
			add_filter( 'wooframework_export_query_inner', array( $this, 'add_exporter_data' ) );
		}
	} // End init()

	/**
	 * Generate an unordered list of links to each section.
	 * @access  private
	 * @since   5.3.0
	 * @return  string Rendered menu HTML.
	 */
	private function _generate_sections_menu () {
		$html = '';
		$count = 0;

		if ( 1 < count( array_keys( $this->hooks ) ) ) {
			$html .= '<ul id="settings-sections" class="subsubsub hide-if-no-js">' . "\n";
			foreach ( array_keys( $this->hooks ) as $k ) {
				$count++;
				if ( in_array( $k, array_keys( $this->hook_titles ) ) ) {
					$title = $this->hook_titles[$k];
				} else {
					$title = ucwords( str_replace( '_', ' ', $k ) );
				}

				$css_class = $k . ' general';

				if ( $count == 1 ) { $css_class .= ' current'; }

				$html .= '<li><a href="#' . esc_attr( $k ) . '" class="tab ' . esc_attr( $css_class ) . '">' . $title . '</a>';
				if ( 1 < count( array_keys( $this->hooks ) ) && $count < count( array_keys( $this->hooks ) ) ) { $html .= ' | '; }
				$html .= '</li>' . "\n";
			}
			$html .= '</ul><div class="clear"></div>' . "\n";
		}

		echo $html;
	} // End _generate_sections_menu()

	/**
	 * Generate the HTML for the various sections.
	 * @access  private
	 * @since   5.3.0
	 * @return  string Rendered HTML.
	 */
	private function _generate_sections_html () {
		$html = '';

		if ( 0 < count( $this->hooks ) ) {
			foreach ( $this->hooks as $k => $v ) {
				if ( in_array( $k, array_keys( $this->hook_titles ) ) ) {
					$title = $this->hook_titles[$k];
				} else {
					$title = ucwords( str_replace( '_', ' ', $k ) );
				}

				$html .= '<div id="' . $k . '" class="content-section">' . "\n";
					$html .= '<h3 class="title">' . ucwords( $title ) . '</h3>' . "\n";
					if ( 0 < count( $v ) ) {
						$html .= '<table class="form-table">' . "\n";
						foreach ( $v as $i => $j ) {
							$html .= '<tr>' . "\n";
								$html .= '<th scope="row">' . $j['description'] . '</th>' . "\n";
								$html .= '<td>' . "\n";
									$html .= '<fieldset><legend class="screen-reader-text"><span>' . $j['description'] . '</span></legend>' . "\n";
									$html .= '<textarea id="' . esc_attr( $i ) . '-content" name="' . esc_attr( $i ) . '[content]" rows="5" cols="50" class="large-text code">' . stripslashes( $j['content'] ) . '</textarea>' . "\n";
									$html .= '<label for="' . esc_attr( $i ) . '[shortcodes]"><input id="' . esc_attr( $i ) . '-shortcodes" name="' . esc_attr( $i ) . '[shortcodes]" type="checkbox" value="1"' . checked( $j['shortcodes'], 1, false ) . ' /> ' . __( 'Execute Shortcodes on this Hook', 'woothemes' ) . '</label>' . "\n";
									$html .= '</fieldset>' . "\n";
								$html .= '</td>' . "\n";
							$html .= '</tr>' . "\n";
						}
						$html .= '</table>' . "\n";
					}
				$html .= '</div>' . "\n";
			}
		}

		echo $html;
	} // End _generate_sections_html()

	/**
	 * Register the admin screen within WordPress.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function register_admin_screen () {
		if ( function_exists( 'add_submenu_page' ) ) {
			$this->admin_page = add_submenu_page('woothemes', __( 'Hooks', 'woothemes' ), __( 'Hooks', 'woothemes' ), 'manage_options', 'woo-hook-manager', array( &$this, 'admin_screen' ) );

			// Admin screen logic.
			add_action( 'load-' . $this->admin_page, array( $this, 'admin_screen_logic' ) );

			// Add contextual help tabs.
			add_action( 'load-' . $this->admin_page, array( $this, 'admin_screen_help' ) );

			// Add JavaScripts.
			add_action( 'load-' . $this->admin_page, array( $this, 'enqueue_scripts' ) );
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
	 * Load contextual help for the admin screen.
	 * @access  public
	 * @since   1.0.0
	 * @return  string Modified contextual help string.
	 */
	public function admin_screen_help () {
		$screen = get_current_screen();
		if ( $screen->id != $this->admin_page ) return;

		$overview =
			  '<p>' . __( 'Fill in the area you\'d like to customise and hit the "Save Changes" button. It\'s as easy as that!', 'woothemes' ) . '</p>' .
			  '<p><strong>' . __( 'For more information:', 'woothemes' ) . '</strong></p>' .
			  '<p>' . sprintf( __( '<a href="%s" target="_blank">WooThemes Help Desk</a>', 'woothemes' ), 'http://support.woothemes.com/' ) . '</p>';

		$screen->add_help_tab( array( 'id' => 'filters_overview', 'title' => __( 'Overview', 'woothemes' ), 'content' => $overview ) );
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
		if ( isset( $_POST['submit'] ) && check_admin_referer( 'woohooks-options-update' ) ) {
			$fields_to_skip = array( 'submit', '_wp_http_referer', '_wpnonce' );

			$posted_data = $_POST;

			foreach ( $posted_data as $k => $v ) {
				// Remove the fields we want to skip.
				if ( in_array( $k, $fields_to_skip ) ) {
					unset( $posted_data[$k] );
				} else {
					// Make sure the "shortcodes" and "php" keys are available, even if not posted.
					if ( ! array_key_exists( 'shortcodes', $v ) ) { $posted_data[$k]['shortcodes'] = 0; }
					if ( ! array_key_exists( 'php', $v ) ) { $posted_data[$k]['php'] = 0; }
				}
			}

			if ( is_array( $posted_data ) ) {
				$is_updated = update_option( $this->plugin_prefix . 'stored_hooks', $posted_data );

				// Redirect to make sure the latest changes are reflected.
				wp_safe_redirect( admin_url( 'admin.php?page=woo-hook-manager&updated=true' ) );
				exit;
			}
			$is_processed = true;
		}
	} // End admin_screen_logic()

	/**
	 * Enqueue scripts for the admin screen.
	 * @access  public
	 * @since   5.3.0
	 * @return  void
	 */
	public function enqueue_scripts () {
		wp_register_script( 'woothemes-hooks-tabs-navigation', $this->plugin_url . 'assets/js/tabs-navigation.js', array( 'jquery' ), '1.0.0', true );
		wp_enqueue_script( 'woothemes-hooks-tabs-navigation' );
	} // End enqueue_scripts()

	/**
	 * Create the hooks using the saved content.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function create_hooks () {
		if ( ! is_admin() ) {
			$stored_hooks = get_option( $this->plugin_prefix . 'stored_hooks' );

			// Create the hooks, using an internal function to create the hook data.
			if ( is_array( $stored_hooks ) ) {

				$this->stored_data = $stored_hooks; // Store this data locally to avoid a second query in $this->execute_hook().

				foreach ( $stored_hooks as $k => $v ) {
					add_action($k, array( $this, 'execute_hook' ) );
				}
			}
		}
	} // End create_hooks()

	/**
	 * Executes the necessary hooks.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function execute_hook () {
		$hook = current_filter();
		$content = $this->stored_data[$hook]['content'];

		if( ! $hook || ! $content ) return;

		// Moved stripslashes here so that the do_shortcode function will accept parameters
		$content = stripslashes( $content );

		// If we are being instructed to execute shortcodes, execute them.
		if ( array_key_exists( 'shortcodes', $this->stored_data[$hook] ) && $this->stored_data[$hook]['shortcodes'] ) {
			$content = do_shortcode( $content );
		}

		echo $content;
	} // End execute_hook()

 	/**
 	 * Sets up the default and saved data for the various hook areas.
 	 * @access  public
 	 * @since   1.0.0
 	 * @return  void
 	 */
	public function setup_hook_data () {
		// Stored data.
		$stored_values = get_option( $this->plugin_prefix . 'stored_hooks' );

		$this->hooks = array();

		// Header Hooks
		$this->hooks['header'] = array(
								'woo_top' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed below the opening <code>&lt;body&gt;</code> tag.', 'woothemes' )
									),
								'woo_header_before' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed before the opening <code>#header</code> DIV tag.', 'woothemes' )
									)
,
								'woo_header_inside' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed at the top, inside the <code>#header</code> DIV tag.', 'woothemes' )
									),
								'woo_header_after' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed after the closing <code>#header</code> DIV tag.', 'woothemes' )
									)
							);

		// Navigation Hooks
		$this->hooks['nav'] = array(
								'woo_nav_before' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed before the opening <code>#navigation</code> DIV tag.', 'woothemes' )
									),
								'woo_nav_inside' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed at the top, inside the <code>#navigation</code> DIV tag.', 'woothemes' )
									),
								'woo_nav_after' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed after the closing <code>#navigation</code> DIV tag.', 'woothemes' )
									)
							);

		// Main Content Area Hooks
		$this->hooks['main'] = array(
								'woo_content_before' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed before the opening <code>#content</code> DIV tag.', 'woothemes' )
									)
,
								'woo_main_before' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed before the opening <code>#main</code> DIV tag.', 'woothemes' )
									),
								'woo_loop_before' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed before the WordPress Loop.', 'woothemes' )
									),
								'loop_start' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed at the start of the WordPress Loop.', 'woothemes' )
									),
								'loop_end' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed at the end of the WordPress Loop.', 'woothemes' )
									),
								'woo_loop_after' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed after the WordPress Loop.', 'woothemes' )
									),
								'woo_main_after' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed after the closing <code>#main</code> DIV tag.', 'woothemes' )
									),
								'woo_content_after' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed after the closing <code>#content</code> DIV tag.', 'woothemes' )
									)
							);

		// Post Hooks
		$this->hooks['post'] = array(
								'woo_post_before' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed before each post.', 'woothemes' )
									)
,
								'woo_post_inside_before' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed at the top, inside each post\'s DIV tag.', 'woothemes' )
									),
								'woo_post_inside_after' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed at the bottom, inside each post\'s DIV tag.', 'woothemes' )
									),
								'woo_post_after' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed after each post.', 'woothemes' )
									)
							);

		// Sidebar Hooks
		$this->hooks['sidebars'] = array(
								'woo_sidebar_before' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed before each sidebar.', 'woothemes' )
									)
,
								'woo_sidebar_inside_before' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed at the top, inside each sidebar\'s DIV tag.', 'woothemes' )
									),
								'woo_sidebar_inside_after' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed at the bottom, inside each sidebar\'s DIV tag.', 'woothemes' )
									),
								'woo_sidebar_after' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed after each sidebar.', 'woothemes' )
									)
							);

		// Footer Hooks
		$this->hooks['footer'] = array(
								'woo_footer_top' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed at the top of the <code>footer.php</code> file.', 'woothemes' )
									),
								'woo_footer_before' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed before the opening <code>#footer</code> DIV tag.', 'woothemes' )
									),
								'woo_footer_inside' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed at the top, inside the <code>#footer</code> DIV tag.', 'woothemes' )
									),
								'woo_footer_left_before' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed before the opening <code>#footer .col-left</code> DIV tag.', 'woothemes' )
									),
								'woo_footer_left_after' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed after the ending <code>#footer .col-left</code> DIV tag.', 'woothemes' )
									),
								'woo_footer_right_before' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed before the opening <code>#footer .col-right</code> DIV tag.', 'woothemes' )
									),
								'woo_footer_right_after' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed after the ending <code>#footer .col-right</code> DIV tag.', 'woothemes' )
									),
								'woo_footer_after' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed after the closing <code>#footer</code> DIV tag.', 'woothemes' )
									)
							);

		// WordPress Native Hooks
		$this->hooks['wordpress'] = array(
								'wp_head' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed before the closing <code>&lt;/head&gt;</code> tag.', 'woothemes' )
									),
								'wp_footer' => array(
										'content' => '',
										'shortcodes' => 0,
										'php' => '',
										'description' => __( 'Executed before the closing <code>&lt;/body&gt;</code> tag.', 'woothemes' )
									)
							);

		// Allow child themes/plugins to add their own hook sections.
		$this->hooks = apply_filters( 'woo_hook_manager_hooks', $this->hooks );

		// Assigned stored data to the appropriate hook area.
		foreach ( $this->hooks as $id => $arr ) {
			foreach ( $this->hooks[$id] as $k => $v ) {
				if ( is_array( $stored_values ) && array_key_exists( $k, $stored_values ) ) {
					if ( is_array( $stored_values[$k] ) ) {
						foreach ( $stored_values[$k] as $i => $j ) {
							$this->hooks[$id][$k][$i] = $j;
						}
					}
				}
			}
		}

		// Setup custom titles for certain hooks sections.
		$this->_setup_hook_titles();
	} // End setup_hook_data()

 	/**
 	 * Setup custom titles for certain sections.
 	 * @access  private
 	 * @since   1.0.0
 	 * @return  void
 	 */
	private function _setup_hook_titles () {
		$this->hook_titles = array();
		$this->hook_titles['nav'] = __( 'Navigation', 'woothemes' );
		$this->hook_titles['wordpress'] = __( 'WordPress', 'woothemes' );
	} // End _setup_hook_titles()

	/**
 	 * Add our saved data to the WooFramework data exporter.
 	 * @access  public
	 * @since   1.0.0
 	 * @param   string $data SQL query.
 	 * @return  string SQL query.
 	 */
	public function add_exporter_data ( $data ) {
		$data .= " OR option_name = '" . $this->plugin_prefix . "stored_hooks" . "'";
		return $data;
	} // End add_exporter_data()
} // End Class
?>