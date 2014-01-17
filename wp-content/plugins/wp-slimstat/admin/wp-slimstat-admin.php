<?php

class wp_slimstat_admin{
	public static $view_url = '';
	public static $config_url = '';
	public static $faulty_fields = array();
	
	protected static $admin_notice = "We've rewritten the DB API, which is now faster and easier to maintain. Please report any issues you may notice with filters and/or inconsistencies in your data.";
	// Would you like to promote your own free/premium extension for WP SlimStat? Let us know and we will list it on our <a href="http://slimstat.getused.to.it/addons/" target="_blank">Add-ons store</a>
	
	/**
	 * Init -- Sets things up.
	 */
	public static function init(){
		// This option requires a special treatment
		if (!empty($_POST['options']['use_separate_menu']))
			wp_slimstat::$options['use_separate_menu'] = in_array($_POST['options']['use_separate_menu'], array('yes','no'))?$_POST['options']['use_separate_menu']:'';

		self::$view_url = ((wp_slimstat::$options['use_separate_menu'] == 'yes')?'admin.php':'options.php').'?page=wp-slim-view-';
		self::$config_url = ((wp_slimstat::$options['use_separate_menu'] == 'yes')?'admin.php':'options.php').'?page=wp-slim-config&amp;tab=';
		load_plugin_textdomain('wp-slimstat', WP_PLUGIN_DIR .'/wp-slimstat/admin/lang', '/wp-slimstat/admin/lang');

		// If a localization does not exist, use English
		if (!isset($l10n['wp-slimstat'])){
			load_textdomain('wp-slimstat', WP_PLUGIN_DIR .'/wp-slimstat/admin/lang/wp-slimstat-en_US.mo');
		}

		// Hook for WPMU - New blog created
		add_action('wpmu_new_blog', array(__CLASS__, 'new_blog'), 10, 1);

		// Screen options: hide/show panels to customize your view
		add_filter('screen_settings', array(__CLASS__, 'screen_settings'), 10, 2);

		// Footer links
		add_filter('admin_footer_text',  array(__CLASS__, 'footer_admin'));

		// Show the activation and config links, if the network is not too large
		add_filter('plugin_action_links_wp-slimstat/wp-slimstat.php', array(__CLASS__, 'plugin_action_links'), 10, 2);

		// Display a notice that hightlights this version's features
		$admin_filemtime = @filemtime(WP_PLUGIN_DIR.'/wp-slimstat/admin/wp-slimstat-admin.php');
		if (($admin_filemtime > date('U') - 300) && !empty($_GET['page']) && strpos($_GET['page'], 'wp-slim-view') !== false){
			add_action('admin_notices', array(__CLASS__, 'show_admin_notice'));
		}

		// Remove spammers from the database
		if (wp_slimstat::$options['ignore_spammers'] == 'yes'){
			add_action('transition_comment_status', array(__CLASS__, 'remove_spam'), 15, 3);
		}

		if (function_exists('is_network_admin') && !is_network_admin()){
			// Add the appropriate entries to the admin menu, if this user can view/admin WP SlimStats
			add_action('admin_menu', array(__CLASS__, 'wp_slimstat_add_view_menu'));
			add_action('admin_menu', array(__CLASS__, 'wp_slimstat_add_config_menu'));

			// Display the column in the Edit Posts screen
			if (wp_slimstat::$options['add_posts_column'] == 'yes'){
				add_filter('manage_posts_columns', array(__CLASS__, 'add_column_header'));
				add_filter('manage_pages_columns', array(__CLASS__, 'add_column_header'));
				add_action('manage_posts_custom_column', array(__CLASS__, 'add_post_column'), 10, 2);
				add_action('manage_pages_custom_column', array(__CLASS__, 'add_post_column'), 10, 2);
				add_action('admin_print_styles-edit.php', array(__CLASS__, 'wp_slimstat_stylesheet'));
			}
			
			// Inline CSS to customize the icon associated to SlimStat in the sidebar
			add_action('admin_print_styles', array(__CLASS__, 'wp_slimstat_stylesheet_icon'));

			// Update the table structure and options, if needed
			if (!isset(wp_slimstat::$options['version']) || wp_slimstat::$options['version'] != wp_slimstat::$version){
				self::update_tables_and_options(false);
			}
			else{
				if (($admin_filemtime < date('U') - 864000) && wp_slimstat::$options['enable_ads_network'] == 'null'){
					wp_slimstat::$options['enable_ads_network'] = 'yes';
				}
			}
		}

		// Load the library of functions to generate the reports
		if ((!empty($_GET['page']) && strpos($_GET['page'], 'wp-slim-view') !== false) || (!empty($_POST['action']) && $_POST['action'] == 'slimstat_load_report')){
	
			include_once(dirname(__FILE__)."/view/wp-slimstat-reports.php");
			wp_slimstat_reports::init();

			// Add the ajax action to handle dynamic report updates
			if (!empty($_POST['action']) && $_POST['action'] == 'slimstat_load_report')
				add_action('wp_ajax_slimstat_load_report', array('wp_slimstat_reports', 'show_report_wrapper'));
		}
	}
	// end init

	/**
	 * Support for WP MU network activations (experimental)
	 */
	public static function activate(){
		if (function_exists('is_multisite') && is_multisite() && isset($_GET['networkwide']) && ($_GET['networkwide'] == 1)){
			$blogids = $GLOBALS['wpdb']->get_col($GLOBALS['wpdb']->prepare("
				SELECT blog_id
				FROM {$GLOBALS['wpdb']->blogs}
				WHERE site_id = %d
				AND deleted = 0
				AND spam = 0", $GLOBALS['wpdb']->siteid));

			foreach ($blogids as $blog_id){
				switch_to_blog($blog_id);
				wp_slimstat::$options = get_option('slimstat_options', array());
				self::init_environment(true);
			}
			restore_current_blog();
			wp_slimstat::$options = get_option('slimstat_options', array());
		}
		else{
			wp_slimstat::$options = get_option('slimstat_options', array());
			self::init_environment(true);
		}
	}
	// end activate

	/**
	 * Support for WP MU network activations
	 */
	public static function new_blog($_blog_id){
		switch_to_blog($_blog_id);
		wp_slimstat::$options = get_option('slimstat_options', array());
		self::init_environment(true);
		restore_current_blog();
		wp_slimstat::$options = get_option('slimstat_options', array());
	}
	// end new_blog

	/**
	 * Creates tables, initializes options and schedules purge cron
	 */
	public static function init_environment($_activate = true){
		self::init_tables($GLOBALS['wpdb']);

		// Schedule the autopurge hook
		if (false === wp_next_scheduled('wp_slimstat_purge'))
			wp_schedule_event('1262311200', 'daily', 'wp_slimstat_purge');

		// If this function hasn't been called during the upgrade process, make sure to init and update all the options
		if (empty(wp_slimstat::$options)) wp_slimstat::init_options();
		if ($_activate) self::update_tables_and_options(true);

		return true;
	}
	// end init_environment

	/**
	 * Creates and populates tables, if they aren't already there.
	 */
	public static function init_tables($_wpdb = ''){
		// Is InnoDB available?
		$have_innodb = $_wpdb->get_results("SHOW VARIABLES LIKE 'have_innodb'", ARRAY_A);
		$use_innodb = (!empty($have_innodb[0]) && $have_innodb[0]['Value'] == 'YES')?'ENGINE=InnoDB':'';

		// Table that stores the actual data about visits
		$stats_table_sql =
			"CREATE TABLE IF NOT EXISTS {$GLOBALS['wpdb']->prefix}slim_stats (
				id INT UNSIGNED NOT NULL auto_increment,
				ip INT UNSIGNED DEFAULT 0,
				other_ip INT UNSIGNED DEFAULT 0,
				user VARCHAR(255) DEFAULT '',
				language VARCHAR(5) DEFAULT '',
				country VARCHAR(16) DEFAULT '',
				domain VARCHAR(255) DEFAULT '',
				referer VARCHAR(2048) DEFAULT '',
				searchterms VARCHAR(2048) DEFAULT '',
				resource VARCHAR(2048) DEFAULT '',
				browser_id SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				screenres_id MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
				content_info_id MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
				plugins VARCHAR(255) DEFAULT '',
				notes VARCHAR(2048) DEFAULT '',
				visit_id INT UNSIGNED NOT NULL DEFAULT 0,
				dt INT(10) UNSIGNED DEFAULT 0,
				PRIMARY KEY id (id)
			) COLLATE utf8_general_ci $use_innodb";

		// A lookup table for browsers can help save some space
		$browsers_table_sql =
			"CREATE TABLE IF NOT EXISTS {$GLOBALS['wpdb']->base_prefix}slim_browsers (
				browser_id MEDIUMINT UNSIGNED NOT NULL auto_increment,
				browser VARCHAR(40) DEFAULT '',
				version VARCHAR(15) DEFAULT '',
				platform VARCHAR(15) DEFAULT '',
				css_version VARCHAR(5) DEFAULT '',
				type TINYINT UNSIGNED DEFAULT 0,
				user_agent VARCHAR(2048) DEFAULT '',
				PRIMARY KEY (browser_id),
				UNIQUE KEY unique_browser (browser, version, platform, css_version, type)
			) COLLATE utf8_general_ci $use_innodb";

		// A lookup table to store screen resolutions
		$screen_res_table_sql =
			"CREATE TABLE IF NOT EXISTS {$GLOBALS['wpdb']->base_prefix}slim_screenres (
				screenres_id MEDIUMINT UNSIGNED NOT NULL auto_increment,
				resolution VARCHAR(12) DEFAULT '',
				colordepth VARCHAR(5) DEFAULT '',
				antialias BOOL DEFAULT FALSE,
				PRIMARY KEY (screenres_id),
				UNIQUE KEY unique_screenres (resolution, colordepth, antialias)
			) COLLATE utf8_general_ci $use_innodb";

		// A lookup table to store content information
		$content_info_table_sql =
			"CREATE TABLE IF NOT EXISTS {$GLOBALS['wpdb']->base_prefix}slim_content_info (
				content_info_id INT UNSIGNED NOT NULL auto_increment,
				content_type VARCHAR(64) DEFAULT '',
				category VARCHAR(256) DEFAULT '',
				author VARCHAR(64) DEFAULT '',
				content_id BIGINT(20) UNSIGNED DEFAULT 0,
				PRIMARY KEY (content_info_id),
				UNIQUE KEY unique_content_info (content_type(20), category(20), author(20), content_id)
			) COLLATE utf8_general_ci $use_innodb";

		// This table will track outbound links (clicks on links to external sites)
		$outbound_table_sql =
			"CREATE TABLE IF NOT EXISTS {$GLOBALS['wpdb']->prefix}slim_outbound (
				outbound_id INT UNSIGNED NOT NULL auto_increment,
				outbound_domain VARCHAR(255) DEFAULT '',
				outbound_resource VARCHAR(2048) DEFAULT '',
				type TINYINT UNSIGNED DEFAULT 0,
				notes VARCHAR(512) DEFAULT '',
				position VARCHAR(32) DEFAULT '',
				id INT UNSIGNED NOT NULL DEFAULT 0,
				dt INT(10) UNSIGNED DEFAULT 0,
				PRIMARY KEY (outbound_id)
			) COLLATE utf8_general_ci $use_innodb";

		// Ok, let's create the table structure
		self::_create_table($browsers_table_sql, $GLOBALS['wpdb']->base_prefix.'slim_browsers', $_wpdb);
		self::_create_table($screen_res_table_sql, $GLOBALS['wpdb']->base_prefix.'slim_screenres', $_wpdb);
		self::_create_table($content_info_table_sql, $GLOBALS['wpdb']->base_prefix.'slim_content_info', $_wpdb);
		self::_create_table($outbound_table_sql, $GLOBALS['wpdb']->prefix.'slim_outbound', $_wpdb);
		self::_create_table($stats_table_sql, $GLOBALS['wpdb']->prefix.'slim_stats', $_wpdb);
	}
	// end init_tables

	/**
	 * Performs some clean-up maintenance (disable cron job).
	 */
	public static function deactivate(){
		// Unschedule the autopurge hook
		if (function_exists('is_multisite') && is_multisite() && isset($_GET['networkwide']) && ($_GET['networkwide'] == 1)){
			$blogids = $GLOBALS['wpdb']->get_col($GLOBALS['wpdb']->prepare("
				SELECT blog_id
				FROM %s
				WHERE site_id = %d
				AND deleted = 0
				AND spam = 0", $GLOBALS['wpdb']->blogs, $GLOBALS['wpdb']->siteid));

			foreach ($blogids as $blog_id) {
				switch_to_blog($blog_id);
				wp_clear_scheduled_hook('wp_slimstat_purge');
			}
			restore_current_blog();
		}
		else{
			wp_clear_scheduled_hook('wp_slimstat_purge');
		}
	}
	// end deactivate

	/**
	 * Updates the table structure, and make it backward-compatible with all the previous versions released.
	 */
	public static function update_tables_and_options($_activate = true){
		// Create initial structure or missing tables
		if (!$_activate) self::init_environment(false);

		// --- Updates for version 3.1 ---
		if (version_compare(wp_slimstat::$options['version'], '3.1', '<')){
			$GLOBALS['wpdb']->query("DROP TABLE IF EXISTS {$GLOBALS['wpdb']->base_prefix}slim_countries");
		}
		// --- END: Updates for version 3.1 ---
		
		// --- Updates for version 3.2 ---
		if (version_compare(wp_slimstat::$options['version'], '3.2', '<')){
			$GLOBALS['wpdb']->query("ALTER TABLE {$GLOBALS['wpdb']->prefix}slim_stats MODIFY country VARCHAR(16) DEFAULT ''");
		}
		// --- END: Updates for version 3.2 ---

		// --- Updates for version 3.3 ---
		if (version_compare(wp_slimstat::$options['version'], '3.3', '<')){
			$user_agent_exists = false;
			$table_structure = $GLOBALS['wpdb']->get_results("SHOW COLUMNS FROM {$GLOBALS['wpdb']->base_prefix}slim_browsers", ARRAY_A);

			foreach($table_structure as $a_row){
				if ($a_row['Field'] == 'user_agent'){
					$user_agent_exists = true;
					break;
				}
			}
			if (!$user_agent_exists){
				$GLOBALS['wpdb']->query("ALTER TABLE {$GLOBALS['wpdb']->base_prefix}slim_browsers ADD COLUMN user_agent VARCHAR(2048) DEFAULT '' AFTER type");
			}
		}
		// --- END: Updates for version 3.3 ---

		// --- Updates for version 3.3.3 ---
		if (version_compare(wp_slimstat::$options['version'], '3.3.3', '<')){
			$GLOBALS['wpdb']->query("ALTER TABLE {$GLOBALS['wpdb']->base_prefix}slim_stats MODIFY browser_id SMALLINT UNSIGNED NOT NULL DEFAULT 0, MODIFY screenres_id MEDIUMINT UNSIGNED NOT NULL DEFAULT 0, MODIFY content_info_id MEDIUMINT UNSIGNED NOT NULL DEFAULT 0");
		}
		// --- END: Updates for version 3.3.3 ---
		
		// New option 'version' added in version 2.8 - Keep it up-to-date
		if (!isset(wp_slimstat::$options['version']) || wp_slimstat::$options['version'] != wp_slimstat::$version){
			wp_slimstat::$options['version'] = wp_slimstat::$version;
		}

		return true;
	}
	// end update_tables_and_options

	/**
	 * Removes 'spammers' from the database when the corresponding comments are marked as spam
	 */
	public static function remove_spam($_new_status = '', $_old_status = '', $_comment = ''){
		if ($_new_status == 'spam'){
			wp_slimstat::$wpdb->query(wp_slimstat::$wpdb->prepare("DELETE ts FROM {$GLOBALS['wpdb']->prefix}slim_stats ts WHERE user = %s OR INET_NTOA(ip) = %s", $_comment->comment_author, $_comment->comment_author_IP));
		}
	}
	// end remove_spam

	/**
	 * Loads a custom stylesheet file for the administration panels
	 */
	public static function wp_slimstat_stylesheet(){
		wp_register_style('wp-slimstat', plugins_url('/admin/css/slimstat.css', dirname(__FILE__)));
		wp_enqueue_style('wp-slimstat');

	   	if (!empty(wp_slimstat::$options['custom_css'])){
	   		wp_add_inline_style('wp-slimstat', wp_slimstat::$options['custom_css']);
	   	}
	}
	// end wp_slimstat_stylesheet
	
	/**
	 * Customizes the icon associated to WP SlimStat in the sidebar
	 */
	public static function wp_slimstat_stylesheet_icon(){
		if (!array_key_exists('dashicons', $GLOBALS['wp_styles']->registered)){
			return true;
		}

		wp_add_inline_style('dashicons', "#adminmenu #toplevel_page_wp-slim-view-1 .wp-menu-image:before { content: '\\f239'; margin-top: -2px; }");
	}
	// end wp_slimstat_stylesheet_icon

	/**
	 * Loads user-defined stylesheet code
	 */
	public static function wp_slimstat_userdefined_stylesheet(){
		echo '<style type="text/css" media="screen">'.wp_slimstat::$options['custom_css'].'</style>';
	}
	// end wp_slimstat_userdefined_stylesheet

	public static function wp_slimstat_enqueue_scripts(){
		wp_enqueue_script('dashboard');
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_script('slimstat_admin', plugins_url('/admin/js/slimstat.admin.js', dirname(__FILE__)), array('jquery-ui-dialog'), null);

		// Pass some information to Javascript
		$params = array(
			'async_load' => wp_slimstat::$options['async_load'],
			'datepicker_image' => plugins_url('/admin/images/datepicker.png', dirname(__FILE__)),
			'current_tab' => wp_slimstat_reports::$current_tab,
			'expand_details' => isset(wp_slimstat::$options['expand_details'])?wp_slimstat::$options['expand_details']:'no',
			'refresh_interval' => (wp_slimstat_reports::$current_tab == 1)?intval(wp_slimstat::$options['refresh_interval']):0,
			'text_direction' => $GLOBALS['wp_locale']->text_direction
		);
		wp_localize_script('slimstat_admin', 'SlimStatAdminParams', $params);
	}
	
	public static function wp_slimstat_enqueue_config_scripts(){
		wp_enqueue_script('slimstat_config_admin', plugins_url('/admin/js/slimstat.config.admin.js', dirname(__FILE__)));
	}

	/**
	 * Adds a new entry in the admin menu, to view the stats
	 */
	public static function wp_slimstat_add_view_menu($_s){
		wp_slimstat::$options['capability_can_view'] = empty(wp_slimstat::$options['capability_can_view'])?'read':wp_slimstat::$options['capability_can_view'];

		// If this user is whitelisted, we use the minimum capability
		if (strpos(wp_slimstat::$options['can_view'], $GLOBALS['current_user']->user_login) === false){
			$minimum_capability = wp_slimstat::$options['capability_can_view'];
		}
		else{
			$minimum_capability = 'read';
		}

		$new_entry = array();
		if (wp_slimstat::$options['use_separate_menu'] == 'yes'){
			$new_entry[] = add_menu_page(__('SlimStat','wp-slimstat'), __('SlimStat','wp-slimstat'), $minimum_capability, 'wp-slim-view-1', array(__CLASS__, 'wp_slimstat_include_view'));
			$new_entry[] = add_submenu_page('wp-slim-view-1', __('Right Now','wp-slimstat'), __('Right Now','wp-slimstat'), $minimum_capability, 'wp-slim-view-1', array(__CLASS__, 'wp_slimstat_include_view'));
			$new_entry[] = add_submenu_page('wp-slim-view-1', __('Overview','wp-slimstat'), __('Overview','wp-slimstat'), $minimum_capability, 'wp-slim-view-2', array(__CLASS__, 'wp_slimstat_include_view'));
			$new_entry[] = add_submenu_page('wp-slim-view-1', __('Visitors','wp-slimstat'), __('Visitors','wp-slimstat'), $minimum_capability, 'wp-slim-view-3', array(__CLASS__, 'wp_slimstat_include_view'));
			$new_entry[] = add_submenu_page('wp-slim-view-1', __('Content','wp-slimstat'), __('Content','wp-slimstat'), $minimum_capability, 'wp-slim-view-4', array(__CLASS__, 'wp_slimstat_include_view'));
			$new_entry[] = add_submenu_page('wp-slim-view-1', __('Traffic Sources','wp-slimstat'), __('Traffic Sources','wp-slimstat'), $minimum_capability, 'wp-slim-view-5', array(__CLASS__, 'wp_slimstat_include_view'));
			$new_entry[] = add_submenu_page('wp-slim-view-1', __('World Map','wp-slimstat'), __('World Map','wp-slimstat'), $minimum_capability, 'wp-slim-view-6', array(__CLASS__, 'wp_slimstat_include_view'));
			if (has_action('wp_slimstat_custom_report')) $new_entry[] = add_submenu_page('wp-slim-view-1', __('Custom Reports','wp-slimstat'), __('Custom Reports','wp-slimstat'), $minimum_capability, 'wp-slim-view-7', array(__CLASS__, 'wp_slimstat_include_view'));
			$new_entry[] = add_submenu_page('wp-slim-view-1', __('Add-ons','wp-slimstat'), __('Add-ons','wp-slimstat'), $minimum_capability, 'wp-slim-view-addons', array(__CLASS__, 'wp_slimstat_include_addons'));
		}
		else{
			if (!is_admin_bar_showing()){
				$new_entry[] = add_submenu_page('index.php', __('SlimStat','wp-slimstat'), __('SlimStat','wp-slimstat'), $minimum_capability, 'wp-slim-view-1', array(__CLASS__, 'wp_slimstat_include_view'));
			}
			else{
				$new_entry[] = add_submenu_page('options.php', __('SlimStat','wp-slimstat'), __('SlimStat','wp-slimstat'), $minimum_capability, 'wp-slim-view-1', array(__CLASS__, 'wp_slimstat_include_view'));
			}

			// Let's tell WordPress that these page exist, without showing them
			$new_entry[] = add_submenu_page('options.php', __('Overview','wp-slimstat'), __('Overview','wp-slimstat'), $minimum_capability, 'wp-slim-view-2', array(__CLASS__, 'wp_slimstat_include_view'));
			$new_entry[] = add_submenu_page('options.php', __('Visitors','wp-slimstat'), __('Visitors','wp-slimstat'), $minimum_capability, 'wp-slim-view-3', array(__CLASS__, 'wp_slimstat_include_view'));
			$new_entry[] = add_submenu_page('options.php', __('Content','wp-slimstat'), __('Content','wp-slimstat'), $minimum_capability, 'wp-slim-view-4', array(__CLASS__, 'wp_slimstat_include_view'));
			$new_entry[] = add_submenu_page('options.php', __('Traffic Sources','wp-slimstat'), __('Traffic Sources','wp-slimstat'), $minimum_capability, 'wp-slim-view-5', array(__CLASS__, 'wp_slimstat_include_view'));
			$new_entry[] = add_submenu_page('options.php', __('World Map','wp-slimstat'), __('World Map','wp-slimstat'), $minimum_capability, 'wp-slim-view-6', array(__CLASS__, 'wp_slimstat_include_view'));
			if (has_action('wp_slimstat_custom_report')) $new_entry[] = add_submenu_page('options.php', __('Custom Reports','wp-slimstat'), __('Custom Reports','wp-slimstat'), $minimum_capability, 'wp-slim-view-7', array(__CLASS__, 'wp_slimstat_include_view'));
			$new_entry[] = add_submenu_page('options.php', __('Add-ons','wp-slimstat'), __('Add-ons','wp-slimstat'), $minimum_capability, 'wp-slim-view-addons', array(__CLASS__, 'wp_slimstat_include_addons'));
		}

		// Load styles and Javascript needed to make the reports look nice and interactive
		foreach($new_entry as $a_entry){
			add_action('load-'.$a_entry, array(__CLASS__, 'wp_slimstat_stylesheet'));
			add_action('load-'.$a_entry, array(__CLASS__, 'wp_slimstat_enqueue_scripts'));
			add_action('load-'.$a_entry, array(__CLASS__, 'contextual_help'));
		}

		return $_s;
	}
	// end wp_slimstat_add_view_menu

	/**
	 * Adds a new entry in the admin menu, to manage SlimStat options
	 */
	public static function wp_slimstat_add_config_menu($_s){
		wp_slimstat::$options['capability_can_admin'] = empty(wp_slimstat::$options['capability_can_admin'])?'activate_plugins':wp_slimstat::$options['capability_can_admin'];
		
		// If this user is whitelisted, we use the minimum capability
		if ((strpos(wp_slimstat::$options['can_admin'], $GLOBALS['current_user']->user_login) === false) && ($GLOBALS['current_user']->user_login != 'slimstatadmin')){
			$minimum_capability = wp_slimstat::$options['capability_can_admin'];
		}
		else{
			$minimum_capability = 'read';
		}

		if (wp_slimstat::$options['use_separate_menu'] == 'yes'){
			$new_entry = add_submenu_page('wp-slim-view-1', __('Settings','wp-slimstat'), __('Settings','wp-slimstat'), $minimum_capability, 'wp-slim-config', array(__CLASS__, 'wp_slimstat_include_config'));
		}
		else{
			$new_entry = add_submenu_page(null, __('Settings','wp-slimstat'), __('Settings','wp-slimstat'), $minimum_capability, 'wp-slim-config', array(__CLASS__, 'wp_slimstat_include_config'));
		}
		
		// Load styles and Javascript needed to make the reports look nice and interactive
		add_action('load-'.$new_entry, array(__CLASS__, 'wp_slimstat_stylesheet'));
		add_action('load-'.$new_entry, array(__CLASS__, 'wp_slimstat_enqueue_config_scripts'));

		return $_s;
	}
	// end wp_slimstat_add_config_menu

	/**
	 * Includes the appropriate panel to view the stats
	 */
	public static function wp_slimstat_include_view(){
		include(dirname(__FILE__).'/view/index.php');
	}
	// end wp_slimstat_include_view

	/**
	 * Includes the screen to manage add-ons
	 */
	public static function wp_slimstat_include_addons(){
		include(dirname(__FILE__).'/config/addons.php');
	}
	// end wp_slimstat_include_addons

	/**
	 * Includes the appropriate panel to configure WP SlimStat
	 */
	public static function wp_slimstat_include_config(){
		include(dirname(__FILE__).'/config/index.php');
	}
	// end wp_slimstat_include_config

	/**
	 * Removes the activation link if the network is too big
	 */
	public static function plugin_action_links($_links, $_file){
		if (function_exists('get_blog_count') && (get_blog_count() > 50))
			return $_links;

		if (empty(wp_slimstat::$options['can_admin']) || strpos(wp_slimstat::$options['can_admin'], $GLOBALS['current_user']->user_login) !== false){
			if (wp_slimstat::$options['use_separate_menu'] == 'yes'){
				$_links['wp-slimstat'] = '<a href="admin.php?page=wp-slim-config">'.__('Settings','wp-slimstat').'</a>';
			}
			else{
				$_links['wp-slimstat'] = '<a href="options-general.php?page=wp-slim-config">'.__('Settings','wp-slimstat').'</a>';
			}
		}
		return $_links;
	}
	// end plugin_action_links

	/**
	 * Adds a new column header to the Posts panel (to show the number of pageviews for each post)
	 */
	public static function add_column_header($_columns){
		include_once(dirname(__FILE__)."/view/wp-slimstat-reports.php");
		wp_slimstat_reports::init();

		$_columns['wp-slimstat'] = '<span class="slimstat-icon" title="'.__('Pageviews in the last 365 days','wp-slimstat').'"></span>';
		return $_columns;
	}
	// end add_comment_column_header

	/**
	 * Adds a new column to the Posts management panel
	 */
	public static function add_post_column($_column_name, $_post_id){
		if ('wp-slimstat' != $_column_name) return;
		$parsed_permalink = parse_url( get_permalink($_post_id) );
		$parsed_permalink = $parsed_permalink['path'].(!empty($parsed_permalink['query'])?'?'.$parsed_permalink['query']:'');
		wp_slimstat_db::init('resource contains '.$parsed_permalink.'|interval equals -365');
		$count = wp_slimstat_db::count_records();
		echo '<a href="'.wp_slimstat_reports::fs_url(array('resource' => "contains $parsed_permalink", 'interval' => 'equals -365')).'">'.$count.'</a>';
	}
	// end add_column

	/**
	 * Displays a tab to customize this user's screen options (what boxes to see/hide)
	 */
	public static function screen_settings($_current, $_screen){
		if (strpos($_screen->id, 'page_wp-slim-view') == false) return $_current;

		$current = '<form id="adv-settings" action="" method="post"><h5>'.__('Show on screen','wp-slimstat').'</h5><div class="metabox-prefs">';
		if (isset(wp_slimstat_reports::$all_reports)){
			foreach(wp_slimstat_reports::$all_reports as $a_box_id){
				if (!empty($a_box_id))
					$current .= "<label for='$a_box_id-hide'><input class='hide-postbox-tog' name='$a_box_id-hide' type='checkbox' id='$a_box_id-hide' value='$a_box_id'".(!in_array($a_box_id, wp_slimstat_reports::$hidden_reports)?' checked="checked"':'')." />".wp_slimstat_reports::$all_reports_titles[$a_box_id]."</label>";
			}
		}
		$current .= wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', true, false)."</div></form>";

		// Some panels don't have any screen options
		if (strpos($current, 'label') == false){
			return $_current;
		}

		return $current;
	}

	/**
	 * Displays an alert message
	 */
	public static function show_alert_message($_message = '', $_type = 'update'){
		echo "<div id='slimstat-message' class='wp-ui-highlight'><p>$_message</p></div>";
	}

	/**
	 * Displays a message related to the current version of WP SlimStat
	 */
	public static function show_admin_notice(){
		echo '<div class="updated" style="padding:10px">'.self::$admin_notice.'</div>';
	}
	
	/*
	 * Updates the options 
	 */
	public static function update_options($_options = array()){
		if (!isset($_POST['options']) || empty($_options)) return true;
		
		foreach($_options as $_option_name => $_option_details){
			// Some options require a special treatment and are updated somewhere else
			if (isset($_option_details['skip_update']))
				continue;

			if (isset($_POST['options'][$_option_name]))
				wp_slimstat::$options[$_option_name] = $_POST['options'][$_option_name];
		}

		if (!empty(self::$faulty_fields)){
			self::show_alert_message(__('There was an error updating the following options:','wp-slimstat').' '.implode(', ', self::$faulty_fields), 'updated below-h2');
		}
		else{
			self::show_alert_message(__('Your settings have been successfully updated.','wp-slimstat'), 'updated below-h2');
		}
	}

	/*
	 * Displays the options 
	 */
	public static function display_options($_options = array(), $_current_tab = 1){ ?>
		<form action="<?php echo self::$config_url.$_current_tab ?>" method="post" id="form-slimstat-options-tab-<?php echo $_current_tab ?>">
			<table class="form-table widefat <?php echo $GLOBALS['wp_locale']->text_direction ?>">
			<tbody><?php
				$i = 0;
				foreach($_options as $_option_name => $_option_details){
					$i++;
					if ($_option_details['type'] != 'textarea'){
						self::settings_table_row($_option_name, $_option_details, $i%2==0);
					}
					else{
						self::settings_textarea($_option_name, $_option_details, $i%2==0);
					}
				}
			?></tbody>
			</table>
			<p class="submit"><input type="submit" value="<?php _e('Save Changes','wp-slimstat') ?>" class="button-primary" name="Submit"></p>	
		</form><?php
	}

	protected static function settings_table_row($_option_name = '', $_option_details = array(), $_alternate = false){
		$_option_details = array_merge(array('description' =>'', 'type' => '', 'long_description' => '', 'before_input_field' => '', 'after_input_field' => '', 'custom_label_yes' => '', 'custom_label_no' => ''), $_option_details);
		
		if (!isset(wp_slimstat::$options[$_option_name])){
			wp_slimstat::$options[$_option_name] = ''; 
		}

		echo '<tr'.($_alternate?' class="alternate"':'').'>';
		switch($_option_details['type']){
			case 'section_header': ?>
				<td colspan="2" class="slimstat-options-section-header"><?php echo $_option_details['description'] ?></td><?php
				break;
			case 'yesno': ?>
				<th scope="row"><label for="<?php echo $_option_name ?>"><?php echo $_option_details['description'] ?></label></th>
				<td>
					<span class="block-element"><input type="radio" name="options[<?php echo $_option_name ?>]" id="<?php echo $_option_name ?>_yes" value="yes"<?php echo (wp_slimstat::$options[$_option_name] == 'yes')?' checked="checked"':''; ?>> <?php echo !empty($_option_details['custom_label_yes'])?$_option_details['custom_label_yes']:__('Yes','wp-slimstat') ?>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span>
					<span class="block-element"><input type="radio" name="options[<?php echo $_option_name ?>]" id="<?php echo $_option_name ?>_no" value="no" <?php echo (wp_slimstat::$options[$_option_name] == 'no')?'  checked="checked"':''; ?>> <?php echo !empty($_option_details['custom_label_no'])?$_option_details['custom_label_no']:__('No','wp-slimstat') ?></span>
					<span class="description"><?php echo $_option_details['long_description'] ?></span>
				</td><?php
				break;
			case 'text':
			case 'integer': ?>
				<th scope="row"><label for="<?php echo $_option_name ?>"><?php echo $_option_details['description'] ?></label></th>
				<td>
					<span class="block-element"><?php echo $_option_details['before_input_field'] ?><input type="<?php echo ($_option_details['type'] == 'integer')?'number':'text' ?>" class="<?php echo ($_option_details['type'] == 'integer')?'small-text':'regular-text' ?>" name="options[<?php echo $_option_name ?>]" id="<?php echo $_option_name ?>" value="<?php echo wp_slimstat::$options[$_option_name] ?>"> <?php echo $_option_details['after_input_field'] ?></span>
					<span class="description"><?php echo $_option_details['long_description'] ?></span>
				</td><?php
				break;
			default:
		}
		echo '</tr>';
	}

	protected static function settings_textarea($_option_name = '', $_option_details = array('description' =>'', 'type' => '', 'long_description' => ''), $_alternate = false){
		if (!isset(wp_slimstat::$options[$_option_name])){
			wp_slimstat::$options[$_option_name] = '';
		} ?>

		<tr<?php echo ($_alternate?' class="alternate"':''); ?>>
			<td colspan="2">
				<label for="<?php echo $_option_name ?>"><?php echo $_option_details['description'] ?></label>
				<p class="description"><?php echo $_option_details['long_description'] ?></p>
				<p><textarea class="large-text code" cols="50" rows="3" name="options[<?php echo $_option_name ?>]" id="<?php echo $_option_name ?>"><?php echo !empty(wp_slimstat::$options[$_option_name])?stripslashes(wp_slimstat::$options[$_option_name]):'' ?></textarea></p>
			</td>
		</tr><?php
	}

	/**
	 * Displays warning if plugin is not working properly (client-side data not collected for some reason)
	 */
	public static function check_screenres(){
		if (wp_slimstat::$options['enable_javascript'] == 'yes'){
			$count_humans = wp_slimstat::$wpdb->get_var("SELECT COUNT(*) FROM {$GLOBALS['wpdb']->prefix}slim_stats WHERE visit_id > 0");
			if ($count_humans > 0){
				$count_screenres = wp_slimstat::$wpdb->get_var("SELECT COUNT(*) FROM {$GLOBALS['wpdb']->base_prefix}slim_screenres");
				if ($count_screenres == 0){
					self::show_alert_message(__('WARNING: a misconfigured setting and/or server environment is preventing WP SlimStat from properly tracking your visitors. Please <a target="_blank" href="http://wordpress.org/extend/plugins/wp-slimstat/faq/">check the FAQs</a> for more information.','wp-slimstat'), 'error below-h2');
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Contextual help
	 */
	public static function contextual_help(){
		// This contextual help is only available to those using WP 3.3 or newer
		if (empty($GLOBALS['wp_version']) || version_compare($GLOBALS['wp_version'], '3.3', '<')) return true;

		$screen = get_current_screen();

		$screen->add_help_tab(
			array(
				'id' => 'wp-slimstat-definitions',
				'title' => __('Definitions','wp-slimstat'),
				'content' => '
<ul>
<li><b>'.__('Pageview','wp-slimstat').'</b>: '.__('A request to load a single HTML file ("page"). This should be contrasted with a "hit", which refers to a request for any file from a web server. WP SlimStat logs a pageview each time the tracking code is executed','wp-slimstat').'</li>
<li><b>'.__('(Human) Visit','wp-slimstat').'</b>: '.__("A period of interaction between a visitor's browser and your website, ending when the browser is closed or when the user has been inactive on that site for 30 minutes",'wp-slimstat').'</li>
<li><b>'.__('Known Visitor','wp-slimstat').'</b>: '.__('Any user who has left a comment on your blog, and is thus identified by Wordpress as a returning visitor','wp-slimstat').'</li>
<li><b>'.__('Unique IP','wp-slimstat').'</b>: '.__('Used to differentiate between multiple requests to download a file from one internet address (IP) and requests originating from many distinct addresses; since this measurement looks only at the internet address a pageview came from, it is useful, but not perfect','wp-slimstat').'</li>
<li><b>'.__('Originating IP','wp-slimstat').'</b>: '.__('the originating IP address of a client connecting to a web server through an HTTP proxy or load balancer','wp-slimstat').'</li>
<li><b>'.__('Direct Traffic','wp-slimstat').'</b>: '.__('All those people showing up to your Web site by typing in the URL of your Web site coming or from a bookmark; some people also call this "default traffic" or "ambient traffic"','wp-slimstat').'</li>
<li><b>'.__('Search Engine','wp-slimstat').'</b>: '.__('Google, Yahoo, MSN, Ask, others; this bucket will include both your organic as well as your paid (PPC/SEM) traffic, so be aware of that','wp-slimstat').'</li>
<li><b>'.__('Search Terms','wp-slimstat').'</b>: '.__('Keywords used by your visitors to find your website on a search engine','wp-slimstat').'</li>
<li><b>'.__('SERP','wp-slimstat').'</b>: '.__('Short for search engine results page, the Web page that a search engine returns with the results of its search. The value shown represents your rank (or position) within that list of results','wp-slimstat').'</li>
<li><b>'.__('User Agent','wp-slimstat').'</b>: '.__('Any program used for accessing a website; this includes browsers, robots, spiders and any other program that was used to retrieve information from the site','wp-slimstat').'</li>
<li><b>'.__('Outbound Link','wp-slimstat').'</b>: '.__('A link from one domain to another is said to be outbound from its source anchor and inbound to its target. This report lists all the links to other websites followed by your visitors.','wp-slimstat').'</li>
</ul>'
			)
		);
		$screen->add_help_tab(
			array(
				'id' => 'wp-slimstat-basic-filters',
				'title' => __('Basic Filters','wp-slimstat'),
				'content' => '
<ul>
<li><b>'.__('Browser','wp-slimstat').'</b>: '.__('User agent (Firefox, Chrome, ...)','wp-slimstat').'</li>
<li><b>'.__('Country Code','wp-slimstat').'</b>: '.__('2-letter code (us, ru, de, it, ...)','wp-slimstat').'</li>
<li><b>'.__('IP','wp-slimstat').'</b>: '.__('Visitor\'s public IP address','wp-slimstat').'</li>
<li><b>'.__('Search Terms','wp-slimstat').'</b>: '.__('Keywords used by your visitors to find your website on a search engine','wp-slimstat').'</li>
<li><b>'.__('Language Code','wp-slimstat').'</b>: '.__('Please refer to this <a target="_blank" href="http://msdn.microsoft.com/en-us/library/ee825488(v=cs.20).aspx">language culture page</a> (first column) for more information','wp-slimstat').'</li>
<li><b>'.__('Operating System','wp-slimstat').'</b>: '.__('Accepts identifiers like win7, win98, macosx, ...; please refer to <a target="_blank" href="http://php.net/manual/en/function.get-browser.php">this manual page</a> for more information','wp-slimstat').'</li>
<li><b>'.__('Permalink','wp-slimstat').'</b>: '.__('URL accessed on your site','wp-slimstat').'</li>
<li><b>'.__('Referer','wp-slimstat').'</b>: '.__('Complete address of the referrer page','wp-slimstat').'</li>
<li><b>'.__('Visitor\'s Name','wp-slimstat').'</b>: '.__('Visitors\' names according to the cookie set by Wordpress after they leave a comment','wp-slimstat').'</li>
</ul>'
			)
		);

		$screen->add_help_tab(
			array(
				'id' => 'wp-slimstat-advanced-filters',
				'title' => __('Advanced Filters','wp-slimstat'),
				'content' => '
<ul>
<li><b>'.__('Browser Version','wp-slimstat').'</b>: '.__('user agent version (9.0, 11, ...)','wp-slimstat').'</li>
<li><b>'.__('Browser Type','wp-slimstat').'</b>: '.__('1 = search engine crawler, 2 = mobile device, 3 = syndication reader, 0 = all others','wp-slimstat').'</li>
<li><b>'.__('Color Depth','wp-slimstat').'</b>: '.__('visitor\'s screen\'s color depth (8, 16, 24, ...)','wp-slimstat').'</li>
<li><b>'.__('CSS Version','wp-slimstat').'</b>: '.__('what CSS standard was supported by that browser (1, 2, 3 and other integer values)','wp-slimstat').'</li>
<li><b>'.__('Pageview Attributes','wp-slimstat').'</b>: '.__('this field is set to <em>[pre]</em> if the resource has been accessed through <a target="_blank" href="https://developer.mozilla.org/en/Link_prefetching_FAQ">Link Prefetching</a> or similar techniques','wp-slimstat').'</li>
<li><b>'.__('Post Author','wp-slimstat').'</b>: '.__('author associated to that post/page when the resource was accessed','wp-slimstat').'</li>
<li><b>'.__('Post Category ID','wp-slimstat').'</b>: '.__('ID of the category/term associated to the resource, when available','wp-slimstat').'</li>
<li><b>'.__('Originating IP','wp-slimstat').'</b>: '.__('visitor\'s originating IP address, if available','wp-slimstat').'</li>
<li><b>'.__('Resource Content Type','wp-slimstat').'</b>: '.__('post, page, cpt:<em>custom-post-type</em>, attachment, singular, post_type_archive, tag, taxonomy, category, date, author, archive, search, feed, home; please refer to the <a target="_blank" href="http://codex.wordpress.org/Conditional_Tags">Conditional Tags</a> manual page for more information','wp-slimstat').'</li>
<li><b>'.__('Screen Resolution','wp-slimstat').'</b>: '.__('viewport width and height (1024x768, 800x600, ...)','wp-slimstat').'</li>
<li><b>'.__('Visit ID','wp-slimstat')."</b>: ".__('generally used in conjunction with <em>is not empty</em>, identifies human visitors','wp-slimstat').'</li>
<li><b>'.__('Date Filters','wp-slimstat')."</b>: ".__('you can specify the timeframe by entering a number in the <em>interval</em> field; use -1 to indicate <em>to date</em> (i.e., day=1, month=1, year=blank, interval=-1 will set a year-to-date filter)','wp-slimstat').'</li>
<li><b>'.__('SERP Position','wp-slimstat')."</b>: ".__('set the filter to Referer contains cd=N&, where N is the position you are looking for','wp-slimstat').'</li>
</ul>'
			)
		);
	}
	// end contextual_help

	// Footer link
	public static function footer_admin($_original_footer = ''){
		return $_original_footer.__(' And for keeping an eye on your visitors with <a href="http://slimstat.getused.to.it/">WP SlimStat</a>.','wp-slimstat');
	}

	/**
	 * Creates a table in the database
	 */
	protected static function _create_table($_sql = '', $_tablename = '', $_wpdb = ''){
		$_wpdb->query($_sql);

		// Let's make sure this table was actually created
		foreach ($_wpdb->get_col("SHOW TABLES LIKE '$_tablename'", 0) as $a_table)
			if ($a_table == $_tablename) return true;

		return false;
	}
	// end _create_table
}
// end of class declaration