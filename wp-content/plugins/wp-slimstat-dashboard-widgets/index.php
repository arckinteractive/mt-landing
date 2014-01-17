<?php
/*
Plugin Name: WP SlimStat Dashboard Widgets
Plugin URI: http://wordpress.org/plugins/wp-slimstat-dashboard-widgets/
Description: Monitor your visitors from your Wordpress dashboard. Requires WP SlimStat 3.5.2+
Version: 3.1.4
Author: Camu
Author URI: http://slimstat.getused.to.it
*/

class wp_slimstat_dashboard_widgets{
	/**
	 * Loads localization files and adds a few actions
	 */
	 
	public static function init(){
		if (!class_exists('wp_slimstat'))
			return true;
	
		// Add some custom stylesheets
		add_action('admin_print_styles-index.php', array(__CLASS__, 'wp_slimstat_dashboard_widgets_css_js'));

		// Hook into the 'wp_dashboard_setup' action to register our function
		add_action('wp_dashboard_setup', array(__CLASS__, 'add_dashboard_widgets'));
	}
	// end init
	
	/**
	 * Loads a custom stylesheet file for the administration panels
	 */
	public static function wp_slimstat_dashboard_widgets_css_js(){
		wp_register_style('wp-slimstat-dashboard-widgets', WP_PLUGIN_URL.'/wp-slimstat/admin/css/slimstat.css');
		wp_enqueue_style('wp-slimstat-dashboard-widgets');
		wp_enqueue_script('slimstat_admin', WP_PLUGIN_URL.'/wp-slimstat/admin/js/slimstat.admin.js');
		wp_localize_script('slimstat_admin', 'SlimStatParams', array('async_load' => wp_slimstat::$options['async_load']));
	}
	// end wp_slimstat_stylesheet

	/**
	 * Attaches all the widgets to the dashboard
	 */
	public static function add_dashboard_widgets(){
		// If this user is whitelisted, we use the minimum capability
		if (strpos(wp_slimstat::$options['can_view'], $GLOBALS['current_user']->user_login) === false){
			$minimum_capability = wp_slimstat::$options['capability_can_view'];
		}
		else{
			$minimum_capability = 'read';
		}

		if (!current_user_can($minimum_capability)) return;

		include_once(WP_PLUGIN_DIR."/wp-slimstat/admin/view/wp-slimstat-reports.php");
		wp_slimstat_reports::init();

		$widgets = array('slim_p1_01','slim_p1_02','slim_p1_03','slim_p1_04','slim_p1_05','slim_p1_06','slim_p1_08','slim_p1_11','slim_p1_12','slim_p2_04','slim_p2_12','slim_p4_07','slim_p4_11');
		
		foreach ($widgets as $a_widget)
			wp_add_dashboard_widget($a_widget, wp_slimstat_reports::$all_reports_titles[$a_widget], array(__CLASS__, $a_widget));
	}
	// end add_dashboard_widgets

	// Widget wrappers
	public static function slim_p1_01(){
		wp_slimstat_reports::show_chart('slim_p1_01', wp_slimstat_db::get_data_for_chart('COUNT(t1.ip)', 'COUNT(DISTINCT(t1.ip))'), array(__('Pageviews','wp-slimstat-dashboard-widgets'), __('Unique IPs','wp-slimstat-dashboard-widgets')));
	}	
	public static function slim_p1_02(){
		wp_slimstat_reports::show_about_wpslimstat('slim_p1_02');
	}
	public static function slim_p1_03(){
		wp_slimstat_reports::show_overview_summary('slim_p1_03', wp_slimstat_db::count_records(), wp_slimstat_db::get_data_for_chart('COUNT(t1.ip)', 'COUNT(DISTINCT(t1.ip))'));
	}
	public static function slim_p1_04(){
		wp_slimstat_reports::show_results('recent', 'slim_p1_04', 'user', array('custom_where' => 't1.user <> "" AND t1.dt > '.(date_i18n('U')-300), 'use_date_filters' => false));
	}
	public static function slim_p1_05(){
		wp_slimstat_reports::show_spy_view('slim_p1_05');
	}
	public static function slim_p1_06(){
		wp_slimstat_reports::show_results('recent', 'slim_p1_06', 'searchterms');
	}
	public static function slim_p1_08(){
		wp_slimstat_reports::show_results('popular', 'slim_p1_08', 'SUBSTRING_INDEX(t1.resource, "?", 1)', array('total_for_percentage' => wp_slimstat_db::count_records(), 'as_column' => 'resource', 'filter_op' => 'contains'));
	}
	public static function slim_p1_11(){
		wp_slimstat_reports::show_results('popular_complete', 'slim_p1_11', 'user', array('total_for_percentage' => wp_slimstat_db::count_records('t1.user <> ""')));
	}
	public static function slim_p1_12(){
		wp_slimstat_reports::show_results('popular', 'slim_p1_12', 'searchterms', array('total_for_percentage' => wp_slimstat_db::count_records('t1.searchterms <> ""')));
	}
	public static function slim_p2_04(){
		wp_slimstat_reports::show_results('popular', 'slim_p2_04', 'browser', array('total_for_percentage' => wp_slimstat_db::count_records(), 'more_columns' => ',tb.version'.((wp_slimstat::$options['show_complete_user_agent_tooltip']=='yes')?',tb.user_agent':'')));
	}
	public static function slim_p2_12(){
		wp_slimstat_reports::show_visit_duration('slim_p2_12', wp_slimstat_db::count_records('visit_id > 0 AND tb.type <> 1', 'visit_id'));
	}
	public static function slim_p4_07(){
		wp_slimstat_reports::show_results('popular', 'slim_p4_07', 'category', array('total_for_percentage' => wp_slimstat_db::count_records('(tci.content_type LIKE "%category%")'), 'custom_where' => '(tci.content_type LIKE "%category%")', 'more_columns' => ',tci.category'));
	}
	public static function slim_p4_11(){
		wp_slimstat_reports::show_results('popular', 'slim_p4_11', 'resource', array('total_for_percentage' => wp_slimstat_db::count_records('tci.content_type = "post"'), 'custom_where' => 'tci.content_type = "post"'));
	}
}
// end of class declaration

// Bootstrap
if (function_exists('add_action') && empty($_GET['page']) && preg_match('#wp-admin/(index.php)?(\?.*)?$#', $_SERVER['REQUEST_URI'])){
	add_action('init', array('wp_slimstat_dashboard_widgets', 'init'), 10);
}