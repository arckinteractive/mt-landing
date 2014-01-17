<?php
// Avoid direct access to this piece of code
if (!function_exists('add_action')) exit(0);

// Available icons
$supported_browser_icons = array('Android','Anonymouse','Baiduspider','BlackBerry','BingBot','CFNetwork','Chrome','Chromium','Default Browser','Exabot/BiggerBetter','FacebookExternalHit','FeedBurner','Feedfetcher-Google','Firefox','Internet Archive','Googlebot','Google Feedfetcher','Google Web Preview','IE','IEMobile','iPad','iPhone','iPod Touch','Maxthon','Mediapartners-Google','Microsoft-WebDAV','msnbot','Mozilla','NewsGatorOnline','Netscape','Nokia','Opera','Opera Mini','Opera Mobi','Python','PycURL','Safari','W3C_Validator','WordPress','Yahoo! Slurp','YandexBot');
$supported_os_icons = array('android','blackberry os','iphone osx','ios','java','linux','macosx','symbianos','win7','win8','win8.1','winphone7','winvista','winxp','unknown');
$supported_browser_types = array(__('Human','wp-slimstat'),__('Bot/Crawler','wp-slimstat'),__('Mobile Device','wp-slimstat'),__('Syndication Reader','wp-slimstat'));

// Set the filters
$tables_to_join = 'tb.*,tci.*';
wp_slimstat_db::$filters_normalized['misc']['limit_results'] = wp_slimstat::$options['number_results_raw_data'];
if (wp_slimstat::$options['include_outbound_links_right_now'] == 'yes'){
	$tables_to_join .= ',tob.outbound_domain,tob.outbound_resource';
}

// Get the data
$results = wp_slimstat_db::get_recent('t1.id', '', $tables_to_join);
$count_page_results = count($results);
$count_all_results = wp_slimstat_db::count_records('1=1', '*', true, true, $tables_to_join);

// Report Header
if (empty($_POST['report_id'])){
	wp_slimstat_reports::report_header('slim_p7_02', 'tall');
}

if ($count_page_results == 0){
	echo '<p class="nodata">'.__('No data to display','wp-slimstat').'</p>';
}
else if (wp_slimstat::$options['async_load'] != 'yes' || !empty($_POST['report_id'])){
	
	// Pagination
	echo wp_slimstat_reports::report_pagination('slim_p7_02', $count_page_results, $count_all_results);

	// Loop through the results
	for($i=0;$i<$count_page_results;$i++){
		
		$results[$i]['ip'] = long2ip($results[$i]['ip']);
		$host_by_ip = $results[$i]['ip'];
		if (wp_slimstat::$options['convert_ip_addresses'] == 'yes'){
			$host_by_ip = gethostbyaddr( $results[$i]['ip'] );
		}
		
		$results[$i]['dt'] = date_i18n(wp_slimstat_db::$formats['date_time_format'], $results[$i]['dt'], true);

		// Print session header?
		if ($i == 0 || $results[$i-1]['visit_id'] != $results[$i]['visit_id'] || ($results[$i]['visit_id'] == 0 && ($results[$i-1]['ip'] != $results[$i]['ip'] || $results[$i-1]['browser'] != $results[$i]['browser'] || $results[$i-1]['platform'] != $results[$i]['platform']))){

			// Color-coded headers
			$highlight_row = !empty($results[$i]['searchterms'])?' is-search-engine':(($results[$i]['type'] != 1)?' is-direct':'');

			// Country
			$results[$i]['country'] = "<a class='slimstat-filter-link inline-icon' href='".wp_slimstat_reports::fs_url(array('country' => 'equals '.$results[$i]['country']))."'><img class='slimstat-tooltip-trigger' src='".wp_slimstat_reports::$plugin_url."/images/flags/{$results[$i]['country']}.png' width='16' height='16'/><span class='slimstat-tooltip-content'>".__('c-'.$results[$i]['country'],'wp-slimstat')."</span></a>";

			// Browser
			if ($results[$i]['version'] == 0) $results[$i]['version'] = '';
			$browser_title = (wp_slimstat::$options['show_complete_user_agent_tooltip'] == 'no')?"{$results[$i]['browser']} {$results[$i]['version']}":$results[$i]['user_agent'];
			$browser_icon = wp_slimstat_reports::$plugin_url.'/images/browsers/other-browsers-and-os.png';
			if (in_array($results[$i]['browser'], $supported_browser_icons)){
				$browser_icon = wp_slimstat_reports::$plugin_url.'/images/browsers/'.sanitize_title($results[$i]['browser']).'.png';
			}
			$browser_filtered = "<a class='slimstat-filter-link inline-icon' href='".wp_slimstat_reports::fs_url(array('browser' => 'equals '.$results[$i]['browser']))."'><img class='slimstat-tooltip-trigger' src='$browser_icon' width='16' height='16'/><span class='slimstat-tooltip-content'>$browser_title</span></a>";

			// Platform
			$platform_icon = wp_slimstat_reports::$plugin_url."/images/browsers/other-browsers-and-os.png' title='".__($results[$i]['platform'],'wp-slimstat')."' width='16' height='16'/>";
			if (in_array(strtolower($results[$i]['platform']), $supported_os_icons)){
				$platform_icon = wp_slimstat_reports::$plugin_url.'/images/platforms/'.sanitize_title($results[$i]['platform']).'.png';
			}
			$platform_filtered = "<a class='slimstat-filter-link inline-icon' href='".wp_slimstat_reports::fs_url(array('platform' => 'equals '.$results[$i]['platform']))."'><img class='slimstat-tooltip-trigger' src='$platform_icon' width='16' height='16'/><span class='slimstat-tooltip-content'>".__($results[$i]['platform'],'wp-slimstat')."</span></a>";

			// Browser Type
			$browser_type_filtered = '';
			if ($results[$i]['type'] != 0){
				$browser_type_filtered = "<a class='slimstat-filter-link inline-icon' href='".wp_slimstat_reports::fs_url(array('type' => 'equals '.$results[$i]['type']))."'><img class='slimstat-tooltip-trigger' src='". wp_slimstat_reports::$plugin_url.'/images/browsers/type'.$results[$i]['type'].".png' width='16' height='16'/><span class='slimstat-tooltip-content'>{$supported_browser_types[$results[$i]['type']]}</span></a>";
			}

			// IP Address and user
			if (empty($results[$i]['user'])){
				$ip_address = "<a class='slimstat-filter-link' href='".wp_slimstat_reports::fs_url(array('ip' => 'equals '.$results[$i]['ip']))."'>$host_by_ip</a>";
			}
			else{
				$display_user_name = $results[$i]['user'];
				if (wp_slimstat::$options['show_display_name'] == 'yes' && strpos($results[$i]['notes'], 'user:') !== false){
					$display_real_name = get_user_by('login', $results[$i]['user']);
					if (is_object($display_real_name)) $display_user_name = $display_real_name->display_name;
				}
				$ip_address = "<a class='slimstat-filter-link' href='".wp_slimstat_reports::fs_url(array('user' => 'equals '.$results[$i]['user']))."'>{$display_user_name}</a>";
				$ip_address .= " <a class='slimstat-filter-link' href='".wp_slimstat_reports::fs_url(array('ip' => 'equals '.$results[$i]['ip']))."'>({$results[$i]['ip']})</a>";
				$highlight_row = (strpos( $results[$i]['notes'], '[user]') !== false)?' is-known-user':' is-known-visitor';
				
			}
			if (!empty(wp_slimstat::$options['ip_lookup_service'])) $ip_address = "<a class='slimstat-font-location-1 whois' href='".wp_slimstat::$options['ip_lookup_service']."{$results[$i]['ip']}' target='_blank' title='WHOIS: {$results[$i]['ip']}'></a> $ip_address";

			// Originating IP Address
			$other_ip_address = '';
			if (!empty($results[$i]['other_ip'])){
				$results[$i]['other_ip'] = long2ip($results[$i]['other_ip']);
				$other_ip_address = "<a class='slimstat-filter-link' href='".wp_slimstat_reports::fs_url(array('other_ip' => 'equals '.$results[$i]['other_ip']))."'>(".__('Originating IP','wp-slimstat').": {$results[$i]['other_ip']})</a>";
			}
			
			// Plugins
			$plugins = '';
			if (!empty($results[$i]['plugins'])){
				$results[$i]['plugins'] = explode(',', $results[$i]['plugins']);
				foreach($results[$i]['plugins'] as $a_plugin){
					$a_plugin = trim($a_plugin);
					$plugins .= "<a class='slimstat-filter-link inline-icon' href='".wp_slimstat_reports::fs_url(array('plugins' => 'contains '.$a_plugin))."'><img class='slimstat-tooltip-trigger' src='".wp_slimstat_reports::$plugin_url."/images/plugins/$a_plugin.png' width='16' height='16'/><span class='slimstat-tooltip-content'>".__($a_plugin,'wp-slimstat')."</span></a> ";
				}
			}				

			echo "<p class='header$highlight_row'>{$results[$i]['country']} $browser_filtered $platform_filtered $browser_type_filtered $ip_address $other_ip_address <span class='plugins'>$plugins</span></p>";
		}

		echo "<p>";
		$results[$i]['referer'] = (strpos($results[$i]['referer'], '://') === false)?"http://{$results[$i]['domain']}{$results[$i]['referer']}":$results[$i]['referer'];
		
		// Permalink: find post title, if available
		if (!empty($results[$i]['resource'])){
			$results[$i]['resource'] = "<a class='inline-icon url' target='_blank' title='".htmlentities(__('Open this URL in a new window','wp-slimstat'), ENT_QUOTES, 'UTF-8')."' href='".htmlentities($results[$i]['resource'], ENT_QUOTES, 'UTF-8')."'></a> <a class='slimstat-filter-link' href='".wp_slimstat_reports::fs_url(array('resource' => 'equals '.$results[$i]['resource']))."'>".wp_slimstat_reports::get_resource_title($results[$i]['resource']).'</a>';
		}
		else{
			$results[$i]['resource'] = __('Local search results page','wp-slimstat');
		}

		// Search Terms, with link to original SERP
		if (!empty($results[$i]['searchterms'])){
			$results[$i]['searchterms'] = "<i class='inline-icon spaced searchterms' title='".__('Search Terms','wp-slimstat')."'></i> ".wp_slimstat_reports::get_search_terms_info($results[$i]['searchterms'], $results[$i]['domain'], $results[$i]['referer']);
		}
		$results[$i]['domain'] = (!empty($results[$i]['domain']) && empty($results[$i]['searchterms']))?"<a class='inline-icon spaced inbound-link' target='_blank' title='".htmlentities(__('Open this referrer in a new window','wp-slimstat'), ENT_QUOTES, 'UTF-8')."' href='{$results[$i]['referer']}'></a> {$results[$i]['domain']}":'';
		$results[$i]['outbound_domain'] = (!empty($results[$i]['outbound_domain']))?"<a class='inline-icon spaced outbound-link' target='_blank' title='".htmlentities(__('Open this outbound link in a new window','wp-slimstat'), ENT_QUOTES, 'UTF-8')."' href='{$results[$i]['outbound_resource']}'></a> {$results[$i]['outbound_domain']}":'';
		$results[$i]['dt'] = "<i class='inline-icon spaced date-time' title='".__('Date and Time','wp-slimstat')."'></i> {$results[$i]['dt']}";
		$results[$i]['content_type'] = !empty($results[$i]['content_type'])?"<i class='inline-icon spaced content-type' title='".__('Content Type','wp-slimstat')."'></i> <a class='slimstat-filter-link' href='".wp_slimstat_reports::fs_url(array('content_type' => 'equals '.$results[$i]['content_type']))."'>{$results[$i]['content_type']}</a> ":'';
		echo "{$results[$i]['resource']} <span class='details'>{$results[$i]['searchterms']} {$results[$i]['domain']} {$results[$i]['outbound_domain']} {$results[$i]['content_type']} {$results[$i]['dt']}</span>";
		echo '</p>';
	}
	
	// Pagination
	if ($count_page_results > 20){
		echo wp_slimstat_reports::report_pagination('slim_p7_02', $count_page_results, $count_all_results);
	}
}

if (empty($_POST['report_id'])): ?>
	</div>
</div>
<p style="clear:both" class="legend"><span class="legend-title"><?php _e('Color codes','wp-slimstat') ?>:</span>
	<span class="little-color-box is-search-engine"><?php _e('Visit with keywords','wp-slimstat') ?></span>
	<span class="little-color-box is-known-visitor"><?php _e('Known Visitor','wp-slimstat') ?></span>
	<span class="little-color-box is-known-user"><?php _e('Known User','wp-slimstat') ?></span>
	<span class="little-color-box is-direct"><?php _e('Human Visitor','wp-slimstat') ?></span>
	<span class="little-color-box"><?php _e('Bot or Crawler','wp-slimstat') ?></span>
</p><?php
endif; 