<div class="wrap">
    <h2><?php _e( 'Google Analytics', $this->text_domain ) ?></h2>

    <?php
        //Display status message
        if ( isset( $_GET['dmsg'] ) ) { ?>
            <div id="message" class="updated fade"><p><?php echo urldecode( $_GET['dmsg'] ); ?></p></div><?php
        }

        if ( 'network' == $network ): ?>
            <p><?php  _e( 'Google Analytics is the enterprise-class web analytics solution that gives you rich insights into your website traffic and marketing effectiveness. Powerful, flexible and easy-to-use features now let you see and analyze your traffic data in an entirely new way. With Google Analytics, you\'re more prepared to write better-targeted ads, strengthen your marketing initiatives and create higher converting websites.', $this->text_domain ); ?></p>
            <p><?php  _e( 'To get going, just <a href="http://www.google.com/analytics/">sign up for Google Analytics</a>, set up a new account and log in with the button bellow to automatically configure basic settings. You may have to manually adjust settings if necessary.', $this->text_domain ); ?> <?php _e( 'Please keep in mind that it can take several hours before you see any stats.', $this->text_domain ); ?></p>

            <h3 class="title"><?php _e( 'Basic Network Tracking Settings', $this->text_domain ) ?></h3>
            <?php
            if(!isset($accounts)) {
                echo '<p>'.__( 'Please login to google analytics account to automatically get tracking code for this website and enable access to network statistics inside WordPress Admin Dashboard.', $this->text_domain ).'</p>';
                echo '<p><a href="'.add_query_arg('google_login', 1).'" class="button button-secondary">'.__( 'Login with Google account', $this->text_domain ).'</a></p>';
            }
            else {
                echo '<p><a href="'.add_query_arg('google_logout', 1).'" class="button button-secondary">'.__( 'Logout from Google account', $this->text_domain ).'</a></p>';
            }
            ?>

            <form method="post" action="">
                <table class="form-table">

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Network-wide Tracking Code', $this->text_domain ); ?></th>
                        <td>
                            <input type="text" name="tracking_code" class="regular-text" value="<?php if ( !empty( $this->current_settings['track_settings']['tracking_code'] ) ) { echo $this->current_settings['track_settings']['tracking_code']; } ?>" />
                            <p class="description"><?php _e( 'Your Google Analytics tracking code. Ex: UA-XXXXX-X. The Network-wide tracking code will track your entire network of sub-sites.', $this->text_domain ); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Google Analytics Statistics inside WordPress Dashboard For All Sites In Network', $this->text_domain ); ?></th>
                        <td>
                            <?php
                            if(isset($accounts) ) {
                                if(is_array($accounts) && count($accounts) > 0) {
                                    echo '<select name="google_analytics_account_id">';
                                        echo '<option value=""></option>';
                                    foreach($accounts as $account_id => $account_name) {
                                        echo '<option value="'.$account_id.'" '.((isset($this->current_settings['track_settings']['google_analytics_account_id']) && $this->current_settings['track_settings']['google_analytics_account_id'] == $account_id) ? 'selected' : '').'>'.$account_name.'</option>';
                                    }
                                    echo '</select>';
                                    echo '<p class="description">'.__( 'Choose correct Google Analytics profile to use for displaying statistics inside WordPress admin panel. Please make sure that this is a profile used for network wide tracking.', $this->text_domain ).'</p>';
                                }
                                else {
                                    echo '<p class="description">'.__( 'You do not have any Google Analytics profiles to choose from. Please <a href="http://www.google.com/analytics/">create</a> new one.', $this->text_domain ).'</p>';
                                }
                            }
                            else {
                                echo '<p class="description">'.__( 'You need to login to google with the button above to enable this functionality.', $this->text_domain ).'</p>';
                            }
                            ?>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Admin pages tracking', $this->text_domain ); ?></th>
                        <td>
                            <label><input type="radio" name="track_admin" value="1" <?php if ( !empty( $this->current_settings['track_settings']['track_admin'] ) ) echo 'checked="checked"'; ?> /> <?php _e( 'Enable', $this->text_domain ); ?></label>
                            <br />
                            <label><input type="radio" name="track_admin" value="0" <?php if ( empty( $this->current_settings['track_settings']['track_admin'] ) ) echo 'checked="checked"'; ?> /> <?php _e( 'Disable', $this->text_domain ); ?></label>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Subdomain tracking', $this->text_domain ); ?></th>
                        <td>
                            <label><input type="radio" name="track_subdomains" value="1" <?php if ( !empty( $this->current_settings['track_settings']['track_subdomains'] ) ) echo 'checked="checked"'; ?> /> <?php _e( 'Enable', $this->text_domain ) ?></label>
                            <br />
                            <label><input type="radio" name="track_subdomains" value="0" <?php if ( empty( $this->current_settings['track_settings']['track_subdomains'] ) ) echo 'checked="checked"'; ?> /> <?php _e( 'Disable', $this->text_domain ) ?></label>
                        </td>
                    </tr>

					<tr valign="top">
						<th scope="row"><?php _e( 'Domain Mapping', $this->text_domain ); ?></th>
						<td>
							<label><input type="radio" name="domain_mapping" value="1" <?php if ( !empty( $this->current_settings['track_settings']['domain_mapping'] ) ) echo 'checked="checked"'; ?> /> <?php _e( 'Enable', $this->text_domain ) ?></label>
							<br />
							<label><input type="radio" name="domain_mapping" value="0" <?php if ( empty( $this->current_settings['track_settings']['domain_mapping'] ) ) echo 'checked="checked"'; ?> /> <?php _e( 'Disable', $this->text_domain ) ?></label>
						</td>
					</tr>

                </table>

                <h3 class="title"><?php _e( 'Advanced Network Tracking Settings', $this->text_domain ) ?></h3>

                <table class="form-table">

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Support Display Advertising', $this->text_domain ); ?></th>
                        <td>
                            <label><label><input type="radio" name="display_advertising" value="1" <?php if ( !empty( $this->current_settings['track_settings']['display_advertising'] ) ) echo 'checked="checked"'; ?> /> <?php _e( 'Enable', $this->text_domain ) ?></label>
                            <br />
                            <label><input type="radio" name="display_advertising" value="0" <?php if ( empty( $this->current_settings['track_settings']['display_advertising'] ) ) echo 'checked="checked"'; ?> /> <?php _e( 'Disable', $this->text_domain ) ?></label>
                            <p class="description"><?php _e( 'This feature allows you to add demographics and interests reporting to Google Analytics. You can read more about it <a href="https://support.google.com/analytics/answer/3450482?hl=en&ref_topic=3413645&rd=1">here</a>. Please keep in mind that it requires <a href="https://support.google.com/analytics/answer/2700409">updating your privacy policy</a> on all sites in network.');?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Tracking Method', $this->text_domain ); ?></th>
                        <td>
                            <label><input type="radio" name="track_method" value="classic" <?php if ( !isset($this->current_settings['track_settings']['track_method']) || (isset($this->current_settings['track_settings']['track_method']) && $this->current_settings['track_settings']['track_method'] == 'classic') ) echo 'checked="checked"'; ?> /> <?php _e( 'Classic Analytics', $this->text_domain ) ?></label>
                            <br />
                            <label><input type="radio" name="track_method" value="universal" <?php if ( isset($this->current_settings['track_settings']['track_method']) && $this->current_settings['track_settings']['track_method'] == 'universal') echo 'checked="checked"'; ?> /> <?php _e( 'Universal Analytics (beta)', $this->text_domain ) ?>
                            <span class="description"><?php _e( 'Support for this method is in beta. You can read more about it <a href="https://support.google.com/analytics/answer/2790010?hl=en-GB&ref_topic=2790009">here</a>. Keep in mind that "display advertising" currently does not work with this method.', $this->text_domain ); ?></span></label>
                            <br />
                            <label><input type="radio" name="track_method" value="both" <?php if ( isset($this->current_settings['track_settings']['track_method']) && $this->current_settings['track_settings']['track_method'] == 'both' ) echo 'checked="checked"'; ?> /> <?php _e( 'Use both (beta)', $this->text_domain ) ?>
                            <span class="description"><?php _e( 'To use both, you need to have separate tracking code for universal analytics. Please add it here:', $this->text_domain ); ?></span>
                            <input type="text" name="tracking_code2" class="regular-text" value="<?php if ( isset($this->current_settings['track_settings']['tracking_code2']) && !empty( $this->current_settings['track_settings']['tracking_code2'] ) ) { echo $this->current_settings['track_settings']['tracking_code2']; } ?>" /></label>
                            <br />

                        </td>
                    </tr>

                    <?php
                    if ( function_exists('is_pro_site') ):
                        $levels = (array)get_site_option( 'psts_levels' );
                    ?>
                        <tr valign="top">
                            <th scope="row">
                                <?php _e( 'Minimum Pro Site level for use of Google Analytics features', $this->text_domain ); ?>
                            </th>
                            <td>
                                <select name="supporter_only">
                                    <?php $current = isset($this->current_settings['track_settings']['supporter_only']) ? $this->current_settings['track_settings']['supporter_only'] : 0; ?>
                                    <option value="0"<?php selected($this->current_settings['track_settings']['supporter_only'], 0) ?>><?php _e( 'Disable', $this->text_domain ); ?></option>
                                    <?php
                                    foreach ($levels as $level => $value) {
                                    ?>
                                        <option value="<?php echo $level; ?>"<?php selected($current, $level) ?>><?php echo $level . ': ' . esc_attr($value['name']); ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                                 <p class="description"><?php _e( 'Enable use of Google Analytics features for sites with selected minimum Pro Site level.', $this->text_domain ); ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <?php _e( 'Minimum Pro Site level for Google Analytics Statistics inside WordPress Dashboard', $this->text_domain ); ?>
                            </th>
                            <td>
                                <select name="supporter_only_reports">
                                    <?php $current = isset($this->current_settings['track_settings']['supporter_only_reports']) ? $this->current_settings['track_settings']['supporter_only_reports'] : 0; ?>
                                    <option value="0"<?php selected($current, 0) ?>><?php _e( 'Disable', $this->text_domain ); ?></option>
                                    <?php
                                    foreach ($levels as $level => $value) {
                                    ?>
                                        <option value="<?php echo $level; ?>"<?php selected($current, $level) ?>><?php echo $level . ': ' . esc_attr($value['name']); ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                                <p class="description"><?php _e( 'Enable network-wide tracking code based Google Analytics Statistics inside WordPress Dashboard for sites with selected minimum Pro Site level.', $this->text_domain ); ?></p>
                            </td>
                        </tr>
                    <?php
                    endif;
                    ?>

                </table>

                <p class="submit">
                    <?php wp_nonce_field('submit_settings_network'); ?>
                    <input type="submit" name="submit" class="button-primary" value="<?php _e( 'Save Changes', $this->text_domain ); ?>" />
                </p>

            </form>

        <?php else: ?>

            <p><?php  _e( 'Google Analytics is the enterprise-class web analytics solution that gives you rich insights into your website traffic and marketing effectiveness. Powerful, flexible and easy-to-use features now let you see and analyze your traffic data in an entirely new way. With Google Analytics, you\'re more prepared to write better-targeted ads, strengthen your marketing initiatives and create higher converting websites.', $this->text_domain ); ?></p>
            <p><?php  _e( 'To get going, just <a href="http://www.google.com/analytics/">sign up for Google Analytics</a>, set up a new account and log in with the button bellow to automatically configure basic settings. You may have to manually adjust settings if necessary.', $this->text_domain ); ?> <?php _e( 'Please keep in mind that it can take several hours before you see any stats.', $this->text_domain ); ?></p>

            <h3 class="title"><?php _e( 'Basic Site Tracking Settings', $this->text_domain ) ?></h3>
            <?php
            if(!isset($accounts)) {
                echo '<p>'.__( 'Please login to google analytics account to automatically get site tracking code for this account and enable access to statistics inside WordPress Admin Dashboard.', $this->text_domain ).'</p>';
                echo '<p><a href="'.add_query_arg('google_login', 1).'" class="button button-secondary">'.__( 'Login with google account', $this->text_domain ).'</a></p>';
            }
            else {
                echo '<p><a href="'.add_query_arg('google_logout', 1).'" class="button button-secondary">'.__( 'Logout from google account', $this->text_domain ).'</a></p>';
            }
            ?>
            <form method="post" action="">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e( 'Site Tracking Code', $this->text_domain ); ?></th>
                        <td>
                            <label><input type="text" name="tracking_code" class="regular-text" value="<?php if ( !empty( $this->current_settings['track_settings']['tracking_code'] ) ) { echo $this->current_settings['track_settings']['tracking_code']; } ?>" /></label>
                            <br />
                            <p class="description"><?php _e( 'Your Google Analytics tracking code. Ex: UA-XXXXX-X. The Site tracking code will track this site. For more information on finding the tracking code, please visit <a href="https://support.google.com/analytics/answer/1032385?rd=1">this</a> site.', $this->text_domain ); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Custom Google Analytics Statistics inside WordPress Dashboard', $this->text_domain ); ?></th>
                        <td>
                            <?php
                            $network_settings = $this->get_options( null, 'network' );
                            if(isset($network_settings['google_login']['logged_in']) && isset($network_settings['track_settings']['google_analytics_account_id']) && $network_settings['track_settings']['google_analytics_account_id'])
                                $network_stats_message = __( 'Currently .', $this->text_domain );
                            if(isset($accounts) ) {
                                if(count($accounts) > 0) {
                                    echo '<select name="google_analytics_account_id">';
                                        echo '<option value="0"></option>';
                                    foreach($accounts as $account_id => $account_name) {
                                        echo '<option value="'.$account_id.'" '.((isset($this->current_settings['track_settings']['google_analytics_account_id']) && $this->current_settings['track_settings']['google_analytics_account_id'] == $account_id) ? 'selected' : '').'>'.$account_name.'</option>';
                                    }
                                    echo '</select>';
                                    echo '<p class="description">'.__( 'Choose your own Google Analytics profile to use for displaying statistics inside WordPress admin panel.', $this->text_domain ).'</p>';
                                }
                                else {
                                    echo '<p class="description">'.__( 'You do not have any Google Analytics profiles to choose from. Please <a href="http://www.google.com/analytics/">create</a> new one.', $this->text_domain ).'</p>';
                                }
                            }
                            else {
                                echo '<p class="description">'.__( 'You need to login to google with the button above to use your profile for displaying statistics.', $this->text_domain ).'</p>';
                            }
                            ?>
                        </td>
                    </tr>

                </table>
                <h3 class="title"><?php _e( 'Advanced Site Tracking Settings', $this->text_domain ) ?></h3>
                <table class="form-table">

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Support Display Advertising', $this->text_domain ); ?></th>
                        <td>
                            <label><input type="radio" name="display_advertising" value="1" <?php if ( !empty( $this->current_settings['track_settings']['display_advertising'] ) ) echo 'checked="checked"'; ?> /> <?php _e( 'Enable', $this->text_domain ) ?></label>
                            <br />
                            <label><input type="radio" name="display_advertising" value="0" <?php if ( empty( $this->current_settings['track_settings']['display_advertising'] ) ) echo 'checked="checked"'; ?> /> <?php _e( 'Disable', $this->text_domain ) ?></label>
                            <p class="description"><?php _e( 'This feature allows you to add demographics and interests reporting to Google Analytics. You can read more about it <a href="https://support.google.com/analytics/answer/3450482?hl=en&ref_topic=3413645&rd=1">here</a>. Please keep in mind that it requires <a href="https://support.google.com/analytics/answer/2700409">updating your privacy policy</a>.');?>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Tracking Method', $this->text_domain ); ?></th>
                        <td>
                            <label><input type="radio" name="track_method" value="classic" <?php if ( !isset($this->current_settings['track_settings']['track_method']) || (isset($this->current_settings['track_settings']['track_method']) && $this->current_settings['track_settings']['track_method'] == 'classic') ) echo 'checked="checked"'; ?> /> <?php _e( 'Classic Analytics', $this->text_domain ) ?></label>
                            <br />
                            <label><input type="radio" name="track_method" value="universal" <?php if ( isset($this->current_settings['track_settings']['track_method']) && $this->current_settings['track_settings']['track_method'] == 'universal') echo 'checked="checked"'; ?> /> <?php _e( 'Universal Analytics (beta)', $this->text_domain ) ?>
                            <span class="description"><?php _e( 'Support for this method is in beta. You can read more about it <a href="https://support.google.com/analytics/answer/2790010?hl=en-GB&ref_topic=2790009">here</a>. Keep in mind that "display advertising" currently does not work with this method.', $this->text_domain ); ?></span></label>
                            <br />
                            <label><input type="radio" name="track_method" value="both" <?php if ( isset($this->current_settings['track_settings']['track_method']) && $this->current_settings['track_settings']['track_method'] == 'both' ) echo 'checked="checked"'; ?> /> <?php _e( 'Use both (beta)', $this->text_domain ) ?>
                            <span class="description"><?php _e( 'To use both, you need to have separate tracking code for universal analytics. Please add it here:', $this->text_domain ); ?></span>
                            <input type="text" name="tracking_code2" class="regular-text" value="<?php if ( isset($this->current_settings['track_settings']['tracking_code2']) && !empty( $this->current_settings['track_settings']['tracking_code2'] ) ) { echo $this->current_settings['track_settings']['tracking_code2']; } ?>" /></label>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <?php wp_nonce_field('submit_settings'); ?>
                    <input type="submit" name="submit" class="button-primary" value="<?php _e( 'Save Changes', $this->text_domain ); ?>" />
                </p>
            </form>
        <?php endif; ?>
</div>