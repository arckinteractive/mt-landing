<?php
/*  Copyright Maniu, Carson McDonald */
include_once 'externals/OAuth.php';

class Google_Analytics_Async_Dashboard {

    var $text_domain;
    var $plugin_url;

    var $ready;
    var $oauth_token;
    var $oauth_secret;
    var $profile_id = 0;
    var $post;
    var $stats_source;

    var $base_url = 'https://www.googleapis.com/analytics/v2.4/';
    var $account_base_url = 'https://www.googleapis.com/analytics/v2.4/management/';
    var $account_base_url_new = 'https://www.googleapis.com/analytics/v3/management/';

    var $http_code;
    var $error = false;
    var $cache_timeout = 14400;
    var $cache_name = '';
    var $cache;

    var $start_date;
    var $end_date;
    var $filter = array();

    function __construct() {
        global $google_analytics_async;

        $this->text_domain = $google_analytics_async->text_domain;
        $this->plugin_url = $google_analytics_async->plugin_url;

        add_action('admin_init', array( &$this, 'admin_init' ));
        add_action('admin_init', array( &$this, 'admin_init_handle_google_login' ));
    }

    function admin_init() {
        global $google_analytics_async, $pagenow;

        $is_network_admin = (is_network_admin() || (defined('DOING_AJAX') && isset($_POST['network_admin']) && $_POST['network_admin'])) ? 1 : 0;

        //load only for: dashboard, post type page and correct ajax call
        if($pagenow == 'index.php' || ($pagenow == 'post.php' && isset($_GET['post'])) || (isset($_POST['action']) && $_POST['action'] == 'load_google_analtics')) {
            //setup correct google analytics profile id
            if(!$is_network_admin && isset($google_analytics_async->settings['google_login']['logged_in']) && isset($google_analytics_async->settings['track_settings']['google_analytics_account_id']) && $google_analytics_async->settings['track_settings']['google_analytics_account_id']) {
                $this->stats_source = 'site';
                $this->profile_id = $google_analytics_async->settings['track_settings']['google_analytics_account_id'];

                $this->oauth_token = $google_analytics_async->settings['google_login']['token'];
                $this->oauth_secret = $google_analytics_async->settings['google_login']['token_secret'];
            }
            if(!$this->profile_id) {
                if(isset($google_analytics_async->network_settings['google_login']['logged_in']) && isset($google_analytics_async->network_settings['track_settings']['google_analytics_account_id']) && $google_analytics_async->network_settings['track_settings']['google_analytics_account_id']) {
                    $this->stats_source = 'network';
                    $this->profile_id = $google_analytics_async->network_settings['track_settings']['google_analytics_account_id'];

                    $this->oauth_token = $google_analytics_async->network_settings['google_login']['token'];
                    $this->oauth_secret = $google_analytics_async->network_settings['google_login']['token_secret'];

                    if(!$is_network_admin) {
                        $site_url_parts = explode('/', str_replace('http://', '', str_replace('https://', '', site_url())));

                        $this->filter[] = 'ga:hostname=='.$site_url_parts[0];

                        if(count($site_url_parts) > 1) {
                            unset($site_url_parts[0]);
                            $pagepath = implode('/', $site_url_parts);

                            $this->filter[] = 'ga:pagePath=~^/'.$pagepath;
                        }
                    }
                }
            }

            if($this->profile_id) {
                //set up all the variables needed to get data
                $start_date = time() - (60 * 60 * 24 * 30);
                $this->start_date = date('Y-m-d', $start_date);
                $this->end_date = date('Y-m-d');

                $this->post = (isset($_GET['post']) && $_GET['post']) ? $_GET['post'] : ((isset($_POST['post']) && $_POST['post']) ? $_POST['post'] : 0);
                if($this->post)
                    $this->filter[] = 'ga:pagePath=~/'.basename(get_permalink($this->post)).'/$';

                $this->cache_name = 'gac_'.$this->profile_id.get_current_blog_id().$is_network_admin.$this->start_date.$this->post;

                //if its a ajax call, we dont want cached version
                if(!defined('DOING_AJAX'))
                    $this->cache = get_transient($this->cache_name);

                add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );

                add_action('wp_dashboard_setup', array(&$this, 'register_google_analytics_dashboard_widget'));
                add_action('wp_network_dashboard_setup', array(&$this, 'register_google_analytics_dashboard_widget'));
                add_action('add_meta_boxes', array(&$this, 'register_google_analytics_post_widget'));

                //ajax is needed only if there is no cached version
                add_action('wp_ajax_load_google_analtics', array(&$this, 'loaded_google_analytics'));
            }
        }
    }

    function admin_init_handle_google_login() {
        global $google_analytics_async;
        $is_network = is_network_admin() ? 'network' : '';
        $redirect_url = $is_network ? admin_url('/network/settings.php') : admin_url('/options-general.php');

        //handle google login process
        if( isset($_REQUEST['google_login']) && $_REQUEST['google_login'] == 1 ) {
            if(($is_network && !is_super_admin()) || !current_user_can('manage_options'))
                die(__('Cheatin&#8217; uh?'));

            $google_analytics_async->save_options(array('google_login_temp' => array()), $is_network);

            $parameters = array(
                'oauth_callback' => add_query_arg(array('page' => 'google-analytics', 'google_login_return' => 'true'), $redirect_url),
                'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
                'xoauth_displayname' => 'Google Analytics'
            );

            $method = new Google_Analytics_OAuthSignatureMethod_HMAC_SHA1();
            $consumer = new Google_Analytics_OAuthConsumer('anonymous', 'anonymous', NULL);
            $request = Google_Analytics_OAuthRequest::from_consumer_and_token($consumer, NULL, 'GET', 'https://www.google.com/accounts/OAuthGetRequestToken', $parameters);
            $request->sign_request($method, $consumer, NULL);

            $response = wp_remote_get($request->to_url(), array('sslverify' => false));
            if(is_wp_error($response)) {
                wp_redirect(add_query_arg(array('page' => 'google-analytics', 'dmsg' => urlencode($response->get_error_message()))));
                exit();
            }
            else{
                $response_code = wp_remote_retrieve_response_code( $response );

                if($response_code == 200) {
                    $response_body = wp_remote_retrieve_body($response);
                    parse_str($response_body, $access_parameters);

                    $google_analytics_async->save_options(array('google_login_temp' => array('token' => $access_parameters['oauth_token'], 'token_secret' => $access_parameters['oauth_token_secret'])), $is_network);

                    wp_redirect(add_query_arg('oauth_token', urlencode($access_parameters['oauth_token']), 'https://www.google.com/accounts/OAuthAuthorizeToken?oauth_token'));
                }
                else
                    wp_redirect(add_query_arg(array('page' => 'google-analytics', 'dmsg' => urlencode($response)), $redirect_url));

                exit();
            }
        }
        else if( isset($_REQUEST['google_login_return']) ) {
            if(($is_network && !is_super_admin()) || !current_user_can('manage_options'))
                die(__('Cheatin&#8217; uh?'));

            $google_login_temp = $google_analytics_async->current_settings['google_login_temp'];

            $parameters = array('oauth_verifier' => $_REQUEST['oauth_verifier']);

            $method = new Google_Analytics_OAuthSignatureMethod_HMAC_SHA1();
            $consumer = new Google_Analytics_OAuthConsumer('anonymous', 'anonymous', NULL);
            $upgrade_token = new Google_Analytics_OAuthConsumer($google_login_temp['token'], $google_login_temp['token_secret']);
            $request = Google_Analytics_OAuthRequest::from_consumer_and_token($consumer, $upgrade_token, 'GET', 'https://www.google.com/accounts/OAuthGetAccessToken', $parameters);
            $request->sign_request($method, $consumer, $upgrade_token);

            $response = wp_remote_get($request->to_url(), array('sslverify' => false));
            if(is_wp_error($response)) {
                wp_redirect(add_query_arg(array('page' => 'google-analytics', 'dmsg' => urlencode($response->get_error_message()), $redirect_url)));
                exit();
            }
            else{
                $response_code = wp_remote_retrieve_response_code( $response );

                $google_analytics_async->save_options(array('google_login_temp' => array()), $is_network);

                if($response_code == 200) {
                    $response_body = wp_remote_retrieve_body($response);
                    parse_str($response_body, $access_parameters);

                    $google_analytics_async->save_options(array('google_login' => array('token' => $access_parameters['oauth_token'], 'token_secret' => $access_parameters['oauth_token_secret'], 'logged_in' => 1)), $is_network);

                    wp_redirect(add_query_arg(array('page' => 'google-analytics', 'dmsg' => urlencode(__( 'Login successful!', $this->text_domain ))), $redirect_url));
                }
                else
                    wp_redirect(add_query_arg(array('page' => 'google-analytics', 'dmsg' => urlencode($response)), $redirect_url));

                exit();
            }
        }
        elseif( isset($_REQUEST['google_logout']) && $_REQUEST['google_logout'] == 1 ) {
            if(($is_network && !is_super_admin()) || !current_user_can('manage_options'))
                die(__('Cheatin&#8217; uh?'));

            $google_analytics_async->save_options(array('google_login' => array()), $is_network);

            wp_redirect(add_query_arg(array('page' => 'google-analytics', 'dmsg' => urlencode(__( 'Logout successful!', $this->text_domain ))), $redirect_url));
            exit();
        }
    }

    function admin_enqueue_scripts($hook) {
        if($hook == 'index.php' || ($hook == 'post.php' && isset($_GET['post']))) {
            wp_register_script('google_charts_api', 'https://www.google.com/jsapi');
            wp_enqueue_script('google_charts_api');

            wp_register_script('google_analytics_async', $this->plugin_url . 'ga-async.js', array('jquery','sack', 'google_charts_api'));
            wp_enqueue_script('google_analytics_async');
            $params = array();
            if(is_network_admin())
                $params['network_admin'] = 1;
            if($this->post)
                $params['post'] = $this->post;
            if($this->cache)
                $params['data'] = $this->cache['data'];
            wp_localize_script( 'google_analytics_async', 'ga', $params );

            wp_register_style( 'GoogleAnalyticsAsyncStyle', $this->plugin_url . 'ga-async.css' );
            wp_enqueue_style( 'GoogleAnalyticsAsyncStyle' );
        }
    }

    function prepare_authentication_header($url) {
        $signature_method = new Google_Analytics_OAuthSignatureMethod_HMAC_SHA1();
        $consumer = new Google_Analytics_OAuthConsumer('anonymous', 'anonymous', NULL);
        $token = new Google_Analytics_OAuthConsumer($this->oauth_token, $this->oauth_secret);
        $oauth_req = Google_Analytics_OAuthRequest::from_consumer_and_token($consumer, $token, 'GET', $url, array());
        $oauth_req->sign_request($signature_method, $consumer, $token);

        $headers = $oauth_req->to_header();
        $headers = explode(": ",$headers);
        $headers[$headers[0]] = $headers[1];

        return $headers;
    }

    function get_accounts() {
        global $google_analytics_async;

        //when getting account we need current settings... always.
        $this->oauth_token = $google_analytics_async->current_settings['google_login']['token'];
        $this->oauth_secret = $google_analytics_async->current_settings['google_login']['token_secret'];

        $headers = $this->prepare_authentication_header($this->account_base_url_new.'accounts/~all/webproperties/~all/profiles');
        $response = wp_remote_get($this->account_base_url_new.'accounts/~all/webproperties/~all/profiles', array('sslverify' => false, 'headers' => $headers));
        if(is_wp_error($response)) {
            $this->error = $response->get_error_message();
            return false;
        }
        else {
            $this->http_code = wp_remote_retrieve_response_code( $response );
            $response_body = wp_remote_retrieve_body($response);

            if($this->http_code != 200) {
                $this->error = $response_body;
                return false;
            }
            else {
                $response = json_decode($response_body);
                $this->error = '';
                $host_ready = '';

                $current_site_url = rtrim(get_site_url(), "/");

                global $google_analytics_async;
                $is_network = is_network_admin() ? 'network' : '';

                $accounts = array();
                foreach($response->items as $analytics_profile) {
                    //var_dump($analytics_profile);
                    $tracking_code = $analytics_profile->webPropertyId;
                    $account_id = 'ga:'.$analytics_profile->id;
                    $title = $analytics_profile->name;
                    $website_url = rtrim($analytics_profile->websiteUrl, "/");

                    $this->profile_id = $account_id;
                    if(!isset($save_settings) && (empty($google_analytics_async->current_settings['track_settings']['tracking_code']) || empty($google_analytics_async->current_settings['track_settings']['google_analytics_account_id']))) {
                        if($current_site_url == $website_url) {
                            if(empty($google_analytics_async->current_settings['track_settings']['tracking_code'])){
                                $google_analytics_async->current_settings['track_settings']['tracking_code'] = $tracking_code;
                            }
                            if(empty($google_analytics_async->current_settings['track_settings']['google_analytics_account_id'])) {
                                $google_analytics_async->current_settings['track_settings']['google_analytics_account_id'] = $account_id;
                            }
                            $google_analytics_async->save_options( $google_analytics_async->current_settings, $is_network );
                        }
                    }

                    $accounts[$account_id] = $title.' - '.$website_url;
                }

                return $accounts;
            }
        }
    }

    function register_google_analytics_dashboard_widget() {
        global $google_analytics_async;

        if(
            $this->stats_source == 'network'
            && !is_super_admin()
            && !empty( $google_analytics_async->network_settings['track_settings']['supporter_only_reports'] )
            && function_exists('is_pro_site')
            && !is_pro_site(get_current_blog_id(), $google_analytics_async->network_settings['track_settings']['supporter_only_reports'])
        )
            return;

        wp_add_dashboard_widget('google_analytics_dashboard', __('Google Analytics Statistics', $this->text_domain), array(&$this, 'loading_google_analytics'));
    }
    function register_google_analytics_post_widget() {
        global $google_analytics_async;

        if(
            $this->stats_source == 'network'
            && !is_super_admin()
            && !empty( $google_analytics_async->network_settings['track_settings']['supporter_only_reports'] )
            && function_exists('is_pro_site')
            && !is_pro_site(get_current_blog_id(), $google_analytics_async->network_settings['track_settings']['supporter_only_reports'])
        )
            return;

        $screens = array( 'post', 'page' );

        foreach ( $screens as $screen )
            add_meta_box('google_analytics_dashboard', __('Google Analytics Statistics', $this->text_domain), array(&$this, 'loading_google_analytics'), $screen, 'normal');
    }

    function loading_google_analytics() {
        if(!$this->cache)
            echo '<div id="google-analytics-statistics"><p>'.__('Loading...', $this->text_domain).'</p></div>';
        else
            echo '<div id="google-analytics-statistics">'.$this->loaded_google_analytics().'</div>';
    }

    function loaded_google_analytics() {
        if(!current_user_can( 'manage_options' ))
            die(__('Cheatin&#8217; uh?'));

        //if no cache, data will be requested by ajax
        if(!$this->cache) {
            $pageviews_data = $this->request('simple', '', 'ga:date', 'ga:pageviews');
            $visits_data = $this->request('simple', '', 'ga:date', 'ga:visits');
            $unique_visitors_data = $this->request('simple', '', 'ga:date', 'ga:visitors');
            $summary_data = $this->request('simple', '', '','ga:visits,ga:bounces,ga:entrances,ga:timeOnSite,ga:newVisits');
            $keywords_data = $this->request('simple', 5, 'ga:keyword', 'ga:visits', '-ga:visits');
            $sources_data = $this->request('simple', 5, 'ga:source', 'ga:visits', '-ga:visits');
            if(!$this->post)
                $pages_data = $this->request('advanced', 12, 'ga:hostname,ga:pageTitle,ga:pagePath', 'ga:pageviews', '-ga:pageviews');

            $return = array();
            if($this->error)
                $return['html'] = __('Error loading data.', $this->text_domain).' '.$this->error;
            else {
                $total_pageviews = $total_unique_visitors = 0;

                $count = 0;
                $return['data'] = array(array(__('Day', $this->text_domain), __('Pageviews', $this->text_domain), __('Visits', $this->text_domain), __('Unique Visitors', $this->text_domain)));
                foreach($pageviews_data as $day => $pageview) {
                    $current_date = strtotime($this->start_date) + (60 * 60 * 24 * $count);

                    if(isset($visits_data[$day]) && isset($unique_visitors_data[$day])) {
                        $return['data'][] = array(date('M d', $current_date), (int)$pageview, (int)$visits_data[$day], (int)$unique_visitors_data[$day]);

                        $total_pageviews += (int)$pageview;
                        $total_unique_visitors += (int)$unique_visitors_data[$day];
                    }

                    $count++;
                }

                $visits = isset($summary_data['value']['ga:visits']) ? number_format($summary_data['value']['ga:visits']) : 0;
                $pageviews = isset($total_pageviews) ? number_format($total_pageviews) : 0;
                $unique_visitors = isset($total_unique_visitors) ? number_format($total_unique_visitors) : 0;
                $page_per_visit = (isset($summary_data['value']['ga:visits']) && $summary_data['value']['ga:visits'] > 0) ? round($total_pageviews / $summary_data['value']['ga:visits'], 2) : '0';
                $bounce_rate = ((isset($summary_data['value']['ga:entrances']) && $summary_data['value']['ga:entrances'] > 0) ? round($summary_data['value']['ga:bounces'] / $summary_data['value']['ga:entrances'] * 100, 2) : '0').'%';			 	 		 	 						
                $avg_visit_duration = (isset($summary_data['value']['ga:visits']) && $summary_data['value']['ga:visits']) ? date("H:i:s",$summary_data['value']['ga:timeOnSite'] / $summary_data['value']['ga:visits']) : '00:00:00';
                $new_visits = ((isset($summary_data['value']['ga:visits']) && $summary_data['value']['ga:visits'] > 0) ? round($summary_data['value']['ga:newVisits'] / $summary_data['value']['ga:visits'] * 100, 2) : '0').'%';

                $top_posts = $top_searches = $top_referers = array();

                if(isset($pages_data))
                    foreach($pages_data as $page) {
                        //var_dump($page);
                        $url = $page['value'];
                        $title = $page['children']['value'];
                        $page_views = $page['children']['children']['children']['ga:pageviews'];
                        $host = $page['children']['children']['value'];

                        $top_posts[] = '<tr><td><a href="http://'.$host.$url.'">'.$title.'</a></td><td align="right">'.$page_views.'</td></tr>';
                    }

                foreach($keywords_data as $keyword => $stat)
                    if($keyword != "(not set)")
                        $top_searches[] = '<tr><td>'.$keyword.'</td><td align="right">'.$stat.'</td></tr>';

                foreach($sources_data as $source => $stat)
                    if($source != "(not set)")
                        $top_referers[] = '<tr><td>'.$source.'</td><td align="right">'.$stat.'</td></tr>';

                $return['html'] = '
                    <div class="google_analytics_chart_holder">
                        <div id="google_analytics_chart" style="width: 100%; height: 300px;"></div>
                    </div>

                    <div class="google_analytics_basic_stats">
                        <ul>
                            <li><label>'.__( 'Visits', $this->text_domain ).'</label><span>'.$visits.'</span></li>
                            <li><label>'.__( 'Unique Visitors', $this->text_domain ).'</label><span>'.$unique_visitors.'</span></li>
                            <li><label>'.__( 'Pageviews', $this->text_domain ).'</label><span>'.$pageviews.'</span></li>
                            <li><label>'.__( 'Pages / Visit', $this->text_domain ).'</label><span>'.$page_per_visit.'</span></li>
                            <li><label>'.__( 'Bounce Rate', $this->text_domain ).'</label><span>'.$bounce_rate.'</span></li>
                            <li><label>'.__( 'Avg. Visit Dur.', $this->text_domain ).'</label><span>'.$avg_visit_duration.'</span></li>
                            <li><label>'.__( 'New Visits', $this->text_domain ).'</label><span>'.$new_visits.'</span></li>
                        </ul>
                    </div>';
                if($top_posts || $top_searches || $top_referers) {
                    $return['html'] .= '
                        <div class="google_analytics_extended_stats">';

                        if($top_posts)
                            $return['html'] .= '
                                <div class="google_analytics_top_posts_pages">
                                    <table>
                                    <tr><td><h4>'.__( 'Top Posts / Pages', $this->text_domain ).'</h4></td><td align="right"><h4>'.__( 'Views', $this->text_domain ).'</h4></td></tr>
                                    '.implode($top_posts).'
                                    </table>
                                </div>';

                        if($top_searches || $top_referers) {
                            $return['html'] .= '
                                <div class="google_analytics_top_searches_referrals">';

                            if($top_searches)
                                    $return['html'] .= '
                                        <div class="google_analytics_searches">
                                            <table>
                                            <tr><td><h4>'.__( 'Top Searches', $this->text_domain ).'</h4></td><td align="right"><h4>'.__( 'Visits', $this->text_domain ).'</h4></td></tr>
                                            '.implode($top_searches).'
                                            </table>
                                        </div>';

                            if($top_referers)
                                $return['html'] .= '
                                    <div class="google_analytics_top_referrals last">
                                        <table>
                                        <tr><td><h4>'.__( 'Top Referrals', $this->text_domain ).'</h4></td><td align="right"><h4>'.__( 'Visits', $this->text_domain ).'</h4></td></tr>
                                        '.implode($top_referers).'
                                        </table>
                                    </div>';

                            $return['html'] .= '
                                </div>';
                        }
                    $return['html'] .= '
                        </div>
                    ';
                }
            }

            if(!$this->error)
                set_transient($this->cache_name, $return, $this->cache_timeout);

            echo json_encode($return);
            die();
        }
        else
            echo $this->cache['html'];
    }

    function request($type, $max_results = '', $dimensions = '', $metrics = '', $sort = '') {
        $url_parameters = array(
            'ids' => $this->profile_id,
            'start-date' => $this->start_date,
            'end-date' => $this->end_date
        );
        if(!empty($max_results))
            $url_parameters['max-results'] = $max_results;
        if(!empty($dimensions))
            $url_parameters['dimensions'] = $dimensions;
        if(!empty($metrics))
            $url_parameters['metrics'] = $metrics;
        if(!empty($sort))
            $url_parameters['sort'] = $sort;
        if(count($this->filter) > 0)
            $url_parameters['filters'] = implode(';', $this->filter);
        $url = add_query_arg($url_parameters, $this->base_url . 'data');

        $response = wp_remote_get($url, array('sslverify' => false, 'headers' => $this->prepare_authentication_header($url)));
        if($response && is_wp_error($response)) {
            $this->error = $response->get_error_message();
            return false;
        }
        else {
            $this->http_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);

            if($this->http_code != 200) {
                $this->error = $response_body;
                return false;
            }
            else {
                $xml = simplexml_load_string($response_body);

                $return_values = array();
                foreach($xml->entry as $entry) {
                    if($type == 'simple') {
                        if($dimensions == '')
                            $dim_name = 'value';
                        else {
                            $dimension = $entry->xpath('dxp:dimension');
                            $dimension_attributes = $dimension[0]->attributes();
                            $dim_name = (string)$dimension_attributes['value'];
                        }

                        $metric = $entry->xpath('dxp:metric');
                        if(sizeof($metric) > 1) {
                            foreach($metric as $single_metric) {
                                $metric_attributes = $single_metric->attributes();
                                $return_values[$dim_name][(string)$metric_attributes['name']] = (string)$metric_attributes['value'];
                            }
                        }
                        else {
                            $metric_attributes = $metric[0]->attributes();
                            $return_values[$dim_name] = (string)$metric_attributes['value'];
                        }
                    }
                    else {
                        $metrics = array();
                        foreach($entry->xpath('dxp:metric') as $metric) {
                            $metric_attributes = $metric->attributes();
                            $metrics[(string)$metric_attributes['name']] = (string)$metric_attributes['value'];
                        }

                        $last_dimension_var_name = null;
                        foreach($entry->xpath('dxp:dimension') as $dimension) {
                            $dimension_attributes = $dimension->attributes();

                            $dimension_var_name = 'dimensions_' . strtr((string)$dimension_attributes['name'], ':', '_');
                            $$dimension_var_name = array();

                            if($last_dimension_var_name == null)
                                $$dimension_var_name = array('name' => (string)$dimension_attributes['name'],'value' => (string)$dimension_attributes['value'],'children' => $metrics);
                            else
                                $$dimension_var_name = array('name' => (string)$dimension_attributes['name'],'value' => (string)$dimension_attributes['value'],'children' => $$last_dimension_var_name);

                            $last_dimension_var_name = $dimension_var_name;
                        }
                        array_push($return_values, $$last_dimension_var_name);
                    }
                }

                return $return_values;
            }
        }
    }
}

global $google_analytics_async_dashboard;
$google_analytics_async_dashboard = new Google_Analytics_Async_Dashboard();
?>