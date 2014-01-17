<?php
/**
 * Subscriptions List Table
 * 
 * Extends the WP_List_Table class to create a table for displaying sortable subscriptions.
 *
 * @package		WooCommerce Subscriptions
 * @subpackage	WC_Subscriptions_List_Table
 * @category	Class
 * @author		Brent Shepherd
 */

if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class WC_Subscriptions_List_Table extends WP_List_Table {

	var $message_transient_prefix = '_subscriptions_messages_';

	/**
	 * Create and instance of this list table.
	 * 
	 * @since 1.0
	 */
	public function __construct(){
		parent::__construct( array(
			'singular'  => 'subscription',
			'plural'    => 'subscriptions',
			'ajax'      => false
		) );
	}

	/**
	 * Outputs the content for each column.
	 * 
	 * @param array $item A singular item (one full row's worth of data)
	 * @param array $column_name The name/slug of the column to be processed
	 * @return string Text or HTML to be placed inside the column <td>
	 * @since 1.0
	 */
	public function column_default( $item, $column_name ){
		global $woocommerce;

		$current_gmt_time = gmdate( 'U' );

		switch( $column_name ){
			case 'status':
				$actions = array();

				$action_url = add_query_arg( 
					array( 
						'page'         => $_REQUEST['page'],
						'user'         => $item['user_id'],
						'subscription' => $item['subscription_key'],
						'_wpnonce'     => wp_create_nonce( $item['subscription_key'] )
					) 
				);

				if ( isset( $_REQUEST['status'] ) )
					$action_url = add_query_arg( array( 'status' => $_REQUEST['status'] ), $action_url );

				$order = new WC_Order( $item['order_id'] );

				$all_statuses = array(
					'active'    => __( 'Reactivate', WC_Subscriptions::$text_domain ),
					'on-hold'   => __( 'Suspend', WC_Subscriptions::$text_domain ),
					'cancelled' => __( 'Cancel', WC_Subscriptions::$text_domain ),
					'trash'     => __( 'Trash', WC_Subscriptions::$text_domain ),
					'deleted'   => __( 'Delete Permanently', WC_Subscriptions::$text_domain ),
				);

				foreach ( $all_statuses as $status => $label ) {
					if ( WC_Subscriptions_Manager::can_subscription_be_changed_to( $status, $item['subscription_key'], $item['user_id'] ) ) {
						$action = ( 'deleted' == $status ) ? 'delete' : $status; // For built in CSS
						$actions[$action] = sprintf( '<a href="%s">%s</a>', add_query_arg( 'new_status', $status, $action_url ), $label );
					}
				}

				if( $item['status'] == 'pending' ) {
					unset( $actions['active'] );
					unset( $actions['trash'] );
				} elseif( ! in_array( $item['status'], array( 'cancelled', 'expired', 'suspended' ) ) ) {
					unset( $actions['trash'] );
				}

				$actions = apply_filters( 'woocommerce_subscriptions_list_table_actions', $actions, $item );

				$column_content = sprintf( '<mark class="%s">%s</mark> %s', sanitize_title( $item[$column_name] ), WC_Subscriptions_Manager::get_status_to_display( $item[$column_name], $item['subscription_key'], $item['user_id'] ), $this->row_actions( $actions ) );
				$column_content = apply_filters( 'woocommerce_subscriptions_list_table_column_status_content', $column_content, $item, $actions, $this );
				break;

			case 'title' :
				//Return the title contents
				$column_content  = sprintf( '<a href="%s">%s</a>', get_edit_post_link( $item['product_id'] ), WC_Subscriptions_Order::get_item_name( $item['order_id'], $item['product_id'] ) );
				$column_content .= sprintf( '<input type="hidden" class="%1$s" name="%2$s[%3$s][%4$s][][%1$s]" value="%5$s" />', 'product_id', $this->_args['plural'], $item['user_id'], $item['subscription_key'], $item['product_id'] );

				$order      = new WC_Order( $item['order_id'] );
				$order_item = WC_Subscriptions_Order::get_item_by_product_id( $order, $item['product_id'] );
				$product    = $order->get_product_from_item( $order_item );

				if ( isset( $product->variation_data ) )
					$column_content .= '<br />' . woocommerce_get_formatted_variation( $product->variation_data, true );

				break;

			case 'order_id':
				$order = new WC_Order( $item[$column_name] );
				$column_content  = sprintf( '<a href="%1$s">%2$s</a>', get_edit_post_link( $item[$column_name] ), sprintf( __( 'Order %s', WC_Subscriptions::$text_domain ), $order->get_order_number() ) );
				$column_content .= sprintf( '<input type="hidden" class="%1$s" name="%2$s[%3$s][%4$s][][%1$s]" value="%5$s" />', $column_name, $this->_args['plural'], $item['user_id'], $item['subscription_key'], $item[$column_name] );
				break;

			case 'user':
				$user = get_user_by( 'id', $item['user_id'] );
				$column_content  = sprintf( '<a href="%s">%s</a>', admin_url( 'user-edit.php?user_id=' . $user->ID ), ucfirst( $user->display_name ) );
				$column_content .= sprintf( '<input type="hidden" class="%1$s" name="%2$s[%3$s][%4$s][][%1$s]" value="%5$s" />', 'user_id', $this->_args['plural'], $item['user_id'], $item['subscription_key'], $item['user_id'] );
				break;

			case 'start_date':
			case 'expiry_date':
			case 'end_date':
				if ( $column_name == 'expiry_date' && $item[$column_name] == 0 ) {
					$column_content  = __( 'Never', WC_Subscriptions::$text_domain );
				} else if ( $column_name == 'end_date' && $item[$column_name] == 0 ) {
					$column_content = __( 'Not yet ended', WC_Subscriptions::$text_domain );
				} else {
					$gmt_timestamp  = strtotime( $item[$column_name] );
					$user_timestamp = $gmt_timestamp + ( get_option( 'gmt_offset' ) * 3600 );
					$column_content = sprintf( '<time title="%s">%s</time>', esc_attr( $gmt_timestamp ), date_i18n( get_option( 'date_format' ), $user_timestamp ) );
				}
				break;

			case 'trial_expiry_date':
				$trial_expiration = WC_Subscriptions_Manager::get_trial_expiration_date( $item['subscription_key'], $item['user_id'], 'timestamp');
				if ( empty($trial_expiration) ) {
					$column_content = '-';
				} else {
					$column_content = sprintf( '<time title="%s">%s</time>', esc_attr( $trial_expiration ), date_i18n( get_option( 'date_format' ), ( $trial_expiration + get_option( 'gmt_offset' ) * 3600 ) ) );
				}
				break;

			case 'last_payment_date':
				if ( empty( $item['completed_payments'] ) ) {
					$column_content  = '-';
				} else {
					$last_payment_timestamp = strtotime( array_pop( $item['completed_payments'] ) );

					$time_diff = $current_gmt_time - $last_payment_timestamp;

					if ( $time_diff > 0 && $time_diff < 7 * 24 * 60 * 60 )
						$last_payment = sprintf( __( '%s ago', WC_Subscriptions::$text_domain ), human_time_diff( $last_payment_timestamp, $current_gmt_time ) );
					else
						$last_payment = date_i18n( get_option( 'date_format' ), $last_payment_timestamp + get_option( 'gmt_offset' ) * 3600 );

					$column_content = sprintf( '<time title="%s">%s</time>', esc_attr( $last_payment_timestamp ), $last_payment );
				}
				break;

			case 'next_payment_date':

				$next_payment_timestamp = WC_Subscriptions_Manager::get_next_payment_date( $item['subscription_key'], $item['user_id'], 'timestamp' );

				if ( $next_payment_timestamp == 0 ) {
					$column_content  = '-';
				} else {
					// Convert to site time
					$time_diff = $next_payment_timestamp - $current_gmt_time;

					if ( $time_diff > 0 && $time_diff < 7 * 24 * 60 * 60 )
						$next_payment = sprintf( __( 'In %s', WC_Subscriptions::$text_domain ), human_time_diff( $current_gmt_time, $next_payment_timestamp ) );
					else
						$next_payment = date_i18n( get_option( 'date_format' ), $next_payment_timestamp + get_option( 'gmt_offset' ) * 3600 );

					$column_content = sprintf( '<time class="next-payment-date" title="%s">%s</time>', esc_attr( $next_payment_timestamp ), $next_payment );

					if ( WC_Subscriptions_Manager::can_subscription_be_changed_to( 'new-payment-date', $item['subscription_key'], $item['user_id'] ) ) { 
						$column_content .= '<div class="edit-date-div row-actions hide-if-no-js">';
						$column_content .= '<img class="date-picker-icon" src="' . admin_url( 'images/date-button.gif' ) . '" title="Date Picker Icon"/>';
						$column_content .= '<a href="#edit_timestamp" class="edit-timestamp" tabindex="4">' . __( 'Change', WC_Subscriptions::$text_domain ) . '</a>';
						$column_content .= '<div class="date-picker-div hide-if-js">';
						$column_content .= WC_Subscriptions_Manager::touch_time( array(
								'date'         => date( 'Y-m-d', $next_payment_timestamp ),
								'echo'         => false,
								'multiple'     => true,
								'include_time' => false
							) 
						);
						$column_content .= '</div>';
						$column_content .= '</form>';
					}
				}
				break;

			case 'renewal_order_count':

				$count = WC_Subscriptions_Renewal_Order::get_renewal_order_count( $item['order_id'] );

				$column_content = sprintf(
					'<a href="%1$s">%2$d</a>',
					admin_url( 'edit.php?post_status=all&post_type=shop_order&_renewal_order_parent_id=' . absint( $item['order_id'] ) ),
					$count
				);
				break;
		}

		return $column_content;
	}

	/**
	 * Make sure the subscription key and user id are included in checkbox column.
	 * 
	 * @see WP_List_Table::::single_row_columns()
	 * @param array $item A singular item (one full row's worth of data)
	 * @return string Markup to be placed inside the column <td>
	 * @since 1.0
	 */
	public function column_cb( $item ){
		return sprintf( '<input type="checkbox" class="subscription_key" name="subscription_keys[%1$s][]" value="%2$s" />', $item['user_id'], $item['subscription_key'] );
	}

	/**
	 * Add all the Subscription field columns to the table.
	 * 
	 * @see WP_List_Table::::single_row_columns()
	 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
	 * @since 1.0
	 */
	public function get_columns(){

		$columns = array(
			'cb'                  => '<input type="checkbox" />',
			'status'              => __( 'Status', WC_Subscriptions::$text_domain ),
			'title'               => __( 'Subscription', WC_Subscriptions::$text_domain ),
			'user'                => __( 'User', WC_Subscriptions::$text_domain ),
			'order_id'            => __( 'Order', WC_Subscriptions::$text_domain ),
			'start_date'          => __( 'Start Date', WC_Subscriptions::$text_domain ),
			'expiry_date'         => __( 'Expiration', WC_Subscriptions::$text_domain ),
			'end_date'            => __( 'End Date', WC_Subscriptions::$text_domain ),
			'trial_expiry_date'   => __( 'Trial End Date', WC_Subscriptions::$text_domain ),
			'last_payment_date'   => __( 'Last Payment', WC_Subscriptions::$text_domain ),
			'next_payment_date'   => __( 'Next Payment', WC_Subscriptions::$text_domain ),
			'renewal_order_count' => __( 'Renewals', WC_Subscriptions::$text_domain ),
		);

		return $columns;
	}

	/**
	 * Make the table sortable by all columns and set the default sort field to be start_date.
	 * 
	 * @return array An associative array containing all the columns that should be sortable: 'slugs' => array( 'data_values', bool )
	 * @since 1.0
	 */
	public function get_sortable_columns() {

		$sortable_columns = array(
			'status'              => array( 'status', false ),
			'order_id'            => array( 'order_id', false ),
			'user'                => array( 'user', false ),
			'title'               => array( 'product_name', false ),
			'start_date'          => array( 'start_date', true ),
			'expiry_date'         => array( 'expiry_date', false ),
			'trial_expiry_date'   => array( 'trial_expiry_date', false ),
			'end_date'            => array( 'end_date', false ),
			'last_payment_date'   => array( 'last_payment_date', false ),
			'next_payment_date'   => array( 'next_payment_date', false ),
			'renewal_order_count' => array( 'renewal_order_count', false )
		);

		return $sortable_columns;
	}

	/**
	 * Make it quick an easy to cancel or activate more than one subscription
	 * 
	 * @return array An associative array containing all the bulk actions: 'slugs' => 'Visible Titles'
	 * @since 1.0
	 */
	public function get_bulk_actions() {

		$actions = array();

		if ( isset( $_REQUEST['status'] ) && $_REQUEST['status'] == 'trash' )
			$actions = array( 'deleted' => __( 'Delete Permanently', WC_Subscriptions::$text_domain ) );
		else if ( ! isset( $_REQUEST['status'] ) || $_REQUEST['status'] != 'trash' )
			$actions = array( 'trash' => __( 'Move to Trash', WC_Subscriptions::$text_domain ) );

		return $actions;
	}

	/**
	 * Get the current action selected from the bulk actions dropdown.
	 *
	 * @since 3.1.0
	 * @access public
	 *
	 * @return string|bool The action name or False if no action was selected
	 */
	function current_action() {

		$current_action = false;

		if ( isset( $_REQUEST['new_status'] ) )
			$current_action = $_REQUEST['new_status'];

		if ( isset( $_GET['_customer_user'] ) && ! empty( $_GET['_customer_user'] ) && isset( $_GET['_wpnonce'] ) && isset( $_GET['_wp_http_referer'] ) )
			$current_action = $_GET['_customer_user'];

		if ( isset( $_GET['_product_id'] ) && ! empty( $_GET['_product_id'] ) && isset( $_GET['_wpnonce'] ) && isset( $_GET['_wp_http_referer'] ) )
			$current_action = $_GET['_product_id'];

		if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] )
			$current_action = $_REQUEST['action'];

		return $current_action;
	}

	/**
	 * Handle activate & cancel actions for both individual items and bulk edit. 
	 *
	 * @since 1.0
	 */
	public function process_actions() {

		$custom_actions = apply_filters( 'woocommerce_subscriptions_list_table_pre_process_actions', array(
			'custom_action'  => false,
			'messages'       => array(),
			'error_messages' => array()
		));

		if ( $this->current_action() === false && $custom_actions['custom_action'] === false )
			return;

		$messages       = array();
		$error_messages = array();
		$query_args     = array();

		// Check if custom actions were taken by the filter - if so, it has handled the action and we only need to set the message/error messages
		if ( $custom_actions['custom_action'] !== false ) {

			$messages       = $custom_actions['messages'];
			$error_messages = $custom_actions['error_messages'];

		} else if ( isset( $_GET['subscription'] ) ) { // Single subscription action

			if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], $_GET['subscription'] ) ) 
				wp_die( __( 'Action failed. Invalid Nonce.', WC_Subscriptions::$text_domain ) ); 

			if ( ! WC_Subscriptions_Manager::can_subscription_be_changed_to( $_GET['new_status'], $_GET['subscription'], $_GET['user'] ) ) {
				$error_messages[] = sprintf( __( 'Error: Subscription status can not be changed to "%s".', WC_Subscriptions::$text_domain ), esc_html( $_GET['new_status'] ) );
			} else {
				switch ( $_GET['new_status'] ) {
					case 'active' :
						WC_Subscriptions_Manager::reactivate_subscription( $_GET['user'], $_GET['subscription'] );
						$messages[] = __( 'Subscription activated.', WC_Subscriptions::$text_domain );
						break;
					case 'on-hold' :
						WC_Subscriptions_Manager::put_subscription_on_hold( $_GET['user'], $_GET['subscription'] );
						$messages[] = __( 'Subscription put on-hold.', WC_Subscriptions::$text_domain );
						break;
					case 'cancelled' :
						WC_Subscriptions_Manager::cancel_subscription( $_GET['user'], $_GET['subscription'] );
						$messages[] = __( 'Subscription cancelled.', WC_Subscriptions::$text_domain );
						break;
					case 'trash' :
						WC_Subscriptions_Manager::trash_subscription( $_GET['user'], $_GET['subscription'] );
						$messages[] = __( 'Subscription trashed.', WC_Subscriptions::$text_domain );
						break;
					case 'deleted' :
						WC_Subscriptions_Manager::delete_subscription( $_GET['user'], $_GET['subscription'] );
						$messages[] = __( 'Subscription deleted.', WC_Subscriptions::$text_domain );
						break;
					default :
						$error_messages[] = __( 'Error: Unknown subscription status.', WC_Subscriptions::$text_domain );
						break;
				}
			}

		} else if ( isset( $_GET['subscription_keys'] ) ) { // Bulk actions

			if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-' . $this->_args['plural'] ) ) 
				wp_die( __( 'Bulk edit failed. Invalid Nonce.', WC_Subscriptions::$text_domain ) ); 

			$subscriptions = $_GET['subscription_keys'];

			$subscription_count = 0;
			$error_count = 0;

			if ( in_array( $this->current_action(), array( 'trash', 'deleted' ) ) ) {

				foreach ( $subscriptions as $user_id => $subscription_keys ) {
					foreach ( $subscription_keys as $subscription_key ) {
						if ( ! WC_Subscriptions_Manager::can_subscription_be_changed_to( $this->current_action(), $subscription_key, $user_id ) ) {
							$error_count++;
						} else {
							$subscription_count++;
							if ( 'trash' == $this->current_action() )
								WC_Subscriptions_Manager::trash_subscription( $user_id, $subscription_key );
							else
								WC_Subscriptions_Manager::delete_subscription( $user_id, $subscription_key );
						}
					}
				}

				if ( $subscription_count > 0 ) {
					if ( 'trash' == $this->current_action() )
						$messages[] = sprintf( _n( '%d subscription moved to trash.', '%s subscriptions moved to trash.', $subscription_count, WC_Subscriptions::$text_domain ), $subscription_count );
					else
						$messages[] = sprintf( _n( '%d subscription deleted.', '%s subscriptions deleted.', $subscription_count, WC_Subscriptions::$text_domain ), $subscription_count );
				}

				if ( $error_count > 0 ) {
					if ( 'trash' == $this->current_action() )
						$error_messages[] = sprintf( _n( '%d subscription could not be trashed - is it active or on-hold? Try cancelling it before trashing it.', '%s subscriptions could not be trashed - are they active or on-hold? Try cancelling them before trashing.', $error_count, WC_Subscriptions::$text_domain ), $error_count );
					else
						$error_messages[] = sprintf( _n( '%d subscription could not deleted - is it active or on-hold? Try cancelling it before deleting.', '%s subscriptions could not deleted - are they active or on-hold? Try cancelling them before deleting.', $error_count, WC_Subscriptions::$text_domain ), $error_count );
				}
			}
		}

		$message_nonce = wp_create_nonce( __FILE__ );

		set_transient( $this->message_transient_prefix . $message_nonce, array( 'messages' => $messages, 'error_messages' => $error_messages ), 60 * 60 );

		// Filter by a given customer or product?
		if ( isset( $_GET['_customer_user'] ) || isset( $_GET['_product_id'] ) ) {

			if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-subscriptions' ) )
				wp_die( __( 'Action failed. Invalid Nonce.', WC_Subscriptions::$text_domain ) );

			if ( ! empty( $_GET['_customer_user'] ) ) {
				$user_id = intval( $_GET['_customer_user'] );
				$user    = get_user_by( 'id', absint( $_GET['_customer_user'] ) );

				if ( false === $user )
					wp_die( __( 'Action failed. Invalid user ID.', WC_Subscriptions::$text_domain ) );

				$query_args['_customer_user'] = $user_id;
			}

			if ( ! empty( $_GET['_product_id'] ) ) {
				$product_id = intval( $_GET['_product_id'] );
				$product    = get_product( $product_id );

				if ( false === $product )
					wp_die( __( 'Action failed. Invalid product ID.', WC_Subscriptions::$text_domain ) );

				$query_args['_product_id'] = $product_id;
			}

		}

		$status = ( isset( $_GET['status'] ) ) ? $_GET['status'] : 'all';

		$query_args = array_merge( $query_args, array( 'status' => $status, 'message' => $message_nonce ) );

		$search_query = _admin_search_query();

		if ( ! empty( $search_query ) )
			$query_args['s'] = $search_query;

		$redirect_to = add_query_arg( $query_args, admin_url( 'admin.php?page=subscriptions' ) );

		// Redirect to avoid performning actions on a page refresh
		wp_safe_redirect( $redirect_to );
		exit;
	}

	/**
	 * Get an associative array ( id => link ) with the list
	 * of views available on this table.
	 *
	 * @since 1.0
	 * @return array
	 */
	function get_views() {
		$views = array();

		foreach ( $this->statuses as $status => $count ) {

			if ( ( isset( $_GET['status'] ) && $_GET['status'] == $status ) || ( ! isset( $_GET['status'] ) && $status == 'active' ) )
				$class = ' class="current"';
			else
				$class = '';

			$base_url = admin_url( 'admin.php?page=subscriptions' );

			if ( isset( $_REQUEST['s'] ) )
				$base_url = add_query_arg( 's', $_REQUEST['s'], $base_url );

			if ( isset( $_GET['_customer_user'] ) && ! empty( $_GET['_customer_user'] ) )
				$base_url = add_query_arg( '_customer_user', $_GET['_customer_user'], $base_url );

			if ( isset( $_GET['_product_id'] ) && ! empty( $_GET['_product_id'] ) )
				$base_url = add_query_arg( '_product_id', $_GET['_product_id'], $base_url );

			$views[$status] = sprintf( '<a href="%s"%s>%s (%s)</a>', add_query_arg( 'status', $status, $base_url ), $class, ucfirst( $status ), $count );
		}

		return $views;
	}

	/**
	 * Output any messages set on the class
	 *
	 * @since 1.0
	 */
	public function messages() {

		if ( isset( $_GET['message'] ) ) {

			$all_messages = get_transient( $this->message_transient_prefix . $_GET['message'] );

			if ( ! empty( $all_messages ) ) {

				delete_transient( $this->message_transient_prefix . $_GET['message'] );

				if ( ! empty( $all_messages['messages'] ) )
					echo '<div id="moderated" class="updated"><p>' . implode( "<br/>\n", $all_messages['messages'] ) . '</p></div>';

				if ( ! empty( $all_messages['error_messages'] ) )
					echo '<div id="moderated" class="error"><p>' . implode( "<br/>\n", $all_messages['error_messages'] ) . '</p></div>';
			}

		} elseif ( isset( $_REQUEST['s'] ) ) {

			echo '<div id="moderated" class="updated"><p>';
			echo '<a href="' . admin_url( 'admin.php?page=subscriptions' ) . '" class="close-subscriptions-search">&times;</a>';
			printf( __( 'Showing only subscriptions matching "%s"', WC_Subscriptions::$text_domain ), esc_html( $_REQUEST['s'] ) );
			echo '</p></div>';

		}

		if ( isset( $_GET['_customer_user'] ) || isset( $_GET['_product_id'] ) ) {

			echo '<div id="moderated" class="updated"><p>';
			echo '<a href="' . admin_url( 'admin.php?page=subscriptions' ) . '" class="close-subscriptions-search">&times;</a>';

			if ( ! empty( $_GET['_customer_user'] ) ) {

				$user_id = intval( $_GET['_customer_user'] );
				$user    = get_user_by( 'id', absint( $_GET['_customer_user'] ) );

				if ( false === $user )
					printf( __( 'Invalid user. ', WC_Subscriptions::$text_domain ), $user->display_name );
				else
					printf( __( "Showing %s's subscriptions", WC_Subscriptions::$text_domain ), $user->display_name );
			}

			if ( ! empty( $_GET['_product_id'] ) ) {

				$product_id = intval( $_GET['_product_id'] );
				$product    = get_product( $product_id );

				if ( false === $product )
					printf( __( 'Invalid product.', WC_Subscriptions::$text_domain ), $user->display_name );
				elseif ( ! empty( $_GET['_customer_user'] ) )
					printf( __( ' for product #%s &ndash; %s%s%s', WC_Subscriptions::$text_domain ), $product_id, '<em>', $product->get_title(), '</em>' );
				else
					printf( __( 'Showing subscriptions to product #%s &ndash; %s%s%s', WC_Subscriptions::$text_domain ), $product_id, '<em>', $product->get_title(), '</em>' );
			}

			echo '</p></div>';

		}
	}

	/**
	 * Get, sort and filter subscriptions for display.
	 *
	 * @uses $this->_column_headers
	 * @uses $this->items
	 * @uses $this->get_columns()
	 * @uses $this->get_sortable_columns()
	 * @uses $this->get_pagenum()
	 * @uses $this->set_pagination_args()
	 * @since 1.0
	 */
	function prepare_items() {

		$screen   = get_current_screen();
		$per_page = $this->get_items_per_page( $screen->get_option( 'per_page', 'option' ), 10 );

		$this->get_column_info();

		$this->process_actions();

		if ( isset( $_REQUEST['s'] ) ) {
			$subscriptions_grouped_by_user = WC_Subscriptions_Manager::search_subscriptions( $_REQUEST['s'] );
		} elseif ( isset( $_GET['_customer_user'] ) || isset( $_GET['_product_id'] ) ) {

			if ( isset( $_GET['_customer_user'] ) && ! empty( $_GET['_customer_user'] ) )
				$subscriptions_grouped_by_user = array( $_GET['_customer_user'] => WC_Subscriptions_Manager::get_users_subscriptions( $_GET['_customer_user'] ) );
			else
				$subscriptions_grouped_by_user = WC_Subscriptions_Manager::get_all_users_subscriptions();

			if ( isset( $_GET['_product_id'] ) && ! empty( $_GET['_product_id'] ) ) {
				foreach ( $subscriptions_grouped_by_user as $user_id => $subscriptions ) {
					foreach( $subscriptions as $subscription_key => $subscription ) {
						if ( $subscription['product_id'] == intval( $_GET['_product_id'] ) )
							continue;

						$order_item = WC_Subscriptions_Order::get_item_by_product_id( $subscription['order_id'], $subscription['product_id'] );

						if ( isset( $order_item['variation_id'] ) && $order_item['variation_id'] == intval( $_GET['_product_id'] ) )
							continue;

						unset( $subscriptions_grouped_by_user[$user_id][$subscription_key] );
					}
				}
			}
		} else {
			$subscriptions_grouped_by_user = WC_Subscriptions_Manager::get_all_users_subscriptions();
		}

		$status_to_show = ( isset( $_GET['status'] ) ) ? $_GET['status'] : 'all';

		// Reformat the subscriptions grouped by user to be usable by each row
		$subscriptions  = array();
		$this->statuses = array();

		foreach ( $subscriptions_grouped_by_user as $user_id => $users_subscriptions ) {
			foreach ( $users_subscriptions as $subscription_key => $subscription ) {
				$this->statuses[$subscription['status']] = ( isset( $this->statuses[$subscription['status']] ) ) ? $this->statuses[$subscription['status']] + 1 : 1;

				$all_subscriptions[$subscription_key] = $subscription + array( 
					'user_id'          => $user_id,
					'subscription_key' => $subscription_key
				);

				if ( $status_to_show == $subscription['status'] || ( $status_to_show == 'all' && $subscription['status'] != 'trash' ) ) {
					$subscriptions[$subscription_key] = $subscription + array( 
						'user_id'          => $user_id,
						'subscription_key' => $subscription_key
					);
				}
			}
		}

		// If we have a request for a status that does not exist, default to all subscriptions
		if ( ! isset( $this->statuses[$status_to_show] ) ) {
			if ( $status_to_show != 'all' ) {
				$status_to_show = $_GET['status'] = 'all';
				foreach ( $all_subscriptions as $subscription_key => $subscription )
					if ( $all_subscriptions[$subscription_key]['status'] != 'trash' )
						$subscriptions = $subscriptions + array( $subscription_key => $subscription );
			} else {
				$_GET['status'] = 'all';
			}
		}

		ksort( $this->statuses );

		$this->statuses = array( 'all' => array_sum( $this->statuses ) ) + $this->statuses;

		if ( isset( $this->statuses['trash'] ) )
			$this->statuses['all'] = $this->statuses['all'] - $this->statuses['trash'];

		usort( $subscriptions, array( &$this, 'sort_subscriptions' )  );

		// Add sorted & sliced data to the items property to be used by the rest of the class
		$this->items = array_slice( $subscriptions, ( ( $this->get_pagenum() - 1 ) * $per_page ), $per_page );

		$total_items = count( $subscriptions );

		$this->set_pagination_args( 
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page )
			) 
		);
	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @since 1.2
	 */
	function display_tablenav( $which ) {
		if ( 'top' == $which ) { ?>
		<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
		<?php if ( isset( $_REQUEST['status'] ) ) : ?>
		<input type="hidden" name="status" value="<?php echo $_REQUEST['status'] ?>" />
		<?php endif;
		}
		parent::display_tablenav( $which );
	}

	/**
	 * Display extra filter controls between bulk actions and pagination.
	 *
	 * @since 1.3.1
	 */
	function extra_tablenav( $which ) {
		if ( 'top' == $which ) { ?>
<select id="dropdown_products_and_variations" name="_product_id" data-placeholder="<?php _e( 'Search for a product&hellip;', WC_Subscriptions::$text_domain ); ?>" style="width: 240px">
	<option value=""><?php _e( 'Show all products', 'woocommerce' ) ?></option>
	<?php if ( ! empty( $_GET['_product_id'] ) ) : ?>
		<?php $product = get_product( absint( $_GET['_product_id'] ) ); ?>
	<option value="<?php echo absint( $_GET['_product_id'] ); ?>" <?php selected( 1, 1 ); ?>>
		<?php printf( '#%s &ndash; %s', absint( $_GET['_product_id'] ), esc_html( $product->get_title() ) ); ?>
	</option>
	<?php endif; ?>
</select>
<select id="dropdown_customers" name="_customer_user">
	<option value=""><?php _e( 'Show all customers', 'woocommerce' ) ?></option>
	<?php if ( ! empty( $_GET['_customer_user'] ) ) : ?>
		<?php $user = get_user_by( 'id', absint( $_GET['_customer_user'] ) ); ?>
	<option value="<?php echo absint( $user->ID ); ?>" <?php selected( 1, 1 ); ?>>
		<?php printf( '%s (#%s &ndash; %s)', esc_html( $user->display_name ), absint( $user->ID ), esc_html( $user->user_email ) ); ?>
	</option>
	<?php endif; ?>
</select>
		<?php submit_button( __( 'Filter' ), 'button', false, false, array( 'id' => 'post-query-submit' ) );
		}
	}

	/**
	 * The text to display before any sign-ups. 
	 *
	 * @since 1.0
	 */
	public function no_items() {

		if ( isset( $_GET['_customer_user'] ) || ! empty( $_GET['_customer_user'] ) )
			$user = get_user_by( 'id', intval( $_GET['_customer_user'] ) );

		if ( isset( $_REQUEST['_product_id'] ) || isset( $_REQUEST['_product_id'] ) )
			$product = get_product( intval( $_GET['_product_id'] ) );

		if ( isset( $_GET['_customer_user'] ) || ! empty( $_GET['_customer_user'] ) ) : ?>
		<?php if ( isset( $_REQUEST['_product_id'] ) || isset( $_REQUEST['_product_id'] ) ) : ?>
<p><?php printf( __( '%s has not purchased %s%s%s (#%s).', WC_Subscriptions::$text_domain ), $user->display_name, '<em>', $product->get_title(), '</em>', intval( $_GET['_product_id'] ) ); ?></p>
		<?php else : ?>
<p><?php printf( __( '%s has not purchased any subscriptions.', WC_Subscriptions::$text_domain ), $user->display_name ); ?></p>
		<?php endif; ?>
		<?php elseif ( isset( $_REQUEST['_product_id'] ) || isset( $_REQUEST['_product_id'] ) ) : ?>
<p><?php printf( __( 'No one has purchased %s%s%s (#%s) yet.', WC_Subscriptions::$text_domain ), '<em>', $product->get_title(), '</em>', intval( $_GET['_product_id'] ) ); ?></p>
		<?php elseif ( isset( $_REQUEST['s'] ) ) : ?>
<p><?php printf( __( 'No subscriptions found to have a subscriber name, email, title or order ID matching "%s".', WC_Subscriptions::$text_domain ), '<em>' . esc_attr( $_REQUEST['s'] ) . '</em>' ); ?></p>
		<?php else : ?>
<p><?php _e( 'Subscriptions will appear here for you to view and manage once purchased by a customer.', WC_Subscriptions::$text_domain ); ?></p>
<p><?php printf( __( '%sLearn more about managing subscriptions%s', WC_Subscriptions::$text_domain ), '<a href="http://docs.woothemes.com/document/store-manager-guide/#managingsubscriptions" target="_blank">', ' &raquo;</a>' ); ?></p>
<p><?php printf( __( '%sAdd a subscription product%s', WC_Subscriptions::$text_domain ), '<a href="' . WC_Subscriptions_Admin::add_subscription_url() . '">', ' &raquo;</a>' ); ?></p>
		<?php endif;
	}

	/**
	 * If no sort order set, default to title. If no sort order, default to descending.
	 *
	 * @since 1.0
	 */
	function sort_subscriptions( $a, $b ){

		$order_by = ( ! empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'start_date'; 

		$order = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'desc'; 

		switch ( $order_by ) {
			case 'product_name' :
				$product_name_a = get_the_title( $a['product_id'] );
				$product_name_b = get_the_title( $b['product_id'] );
				$result = strcasecmp( $product_name_a, $product_name_b );
				break;
			case 'user' : 
				$user_a = get_user_by( 'id', $a['user_id'] );
				$user_b = get_user_by( 'id', $b['user_id'] );
				$result = strcasecmp( $user_a->display_name, $user_b->display_name );
				break;
			case 'expiry_date' :
				if ( $order == 'asc' )
					$result = self::sort_with_zero_at_end( $a[$order_by], $b[$order_by] ); // Display subscriptions that have not ended at the end of the list
				else
					$result = self::sort_with_zero_at_beginning( $a[$order_by], $b[$order_by] );
				break;
			case 'end_date' :
				$result = self::sort_with_zero_at_end( $a[$order_by], $b[$order_by] ); // Display subscriptions that have not ended at the end of the list
				break;
			case 'next_payment_date' :
				$next_payment_a = WC_Subscriptions_Manager::get_next_payment_date( $a['subscription_key'], $a['user_id'], 'mysql' );
				$next_payment_b = WC_Subscriptions_Manager::get_next_payment_date( $b['subscription_key'], $b['user_id'], 'mysql' );
				$result = self::sort_with_zero_at_end( $next_payment_a, $next_payment_b ); // Display subscriptions with no future payments at the end
				break;
			case 'last_payment_date' :
				$last_payment_a = ( empty( $a['completed_payments'] ) ) ? 0 : strtotime( array_pop( $a['completed_payments'] ) );
				$last_payment_b = ( empty( $b['completed_payments'] ) ) ? 0 : strtotime( array_pop( $b['completed_payments'] ) );
				$result = self::sort_with_zero_at_end( $last_payment_a, $last_payment_b ); // Display subscriptions with no compelted payments at the end
				break;
			case 'trial_expiry_date':
				$trial_expiration_a = WC_Subscriptions_Manager::get_trial_expiration_date($a['subscription_key'], $a['user_id'], 'mysql');
				$trial_expiration_b = WC_Subscriptions_Manager::get_trial_expiration_date($b['subscription_key'], $b['user_id'], 'mysql');
				$result = self::sort_with_zero_at_end($trial_expiration_a, $trial_expiration_b);
				break;
			case 'renewal_order_count' :
				$result = strcmp( WC_Subscriptions_Renewal_Order::get_renewal_order_count( $a['order_id'] ), WC_Subscriptions_Renewal_Order::get_renewal_order_count( $b['order_id'] ) );
				break;
			case 'order_id' :
				$result = strnatcmp( $a[$order_by], $b[$order_by] );
				break;
			default :
				$result = strcmp( $a[$order_by], $b[$order_by] );
				break;
		}

		return ( $order == 'asc' ) ? $result : -$result; // Send final sort direction to usort
	}

	/**
	 * A special sorting function to always push a 0 or empty value to the end of the sorted list
	 *
	 * @since 1.2
	 */
	function sort_with_zero_at_end( $a, $b ){

		$order = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'desc'; 

		if ( ( $a == 0 || $b == 0 ) && $a != $b ) {
			if ( $order == 'desc' ) // Set 0 to be < anything other than itself & anything other than 0 to be greater than 0
				$result = ( $a == 0 ) ? -1 : 1;
			elseif ( $order == 'asc' ) // Set 0 to be > anything other than itself & anything other than 0 to be less than 0
				$result = ( $a == 0 ) ? 1 : -1;
		} else {
			$result = strcmp( $a, $b );
		}

		return $result;
	}

	/**
	 * A special sorting function to always push a 0 value to the beginning of a sorted list
	 *
	 * @since 1.2
	 */
	function sort_with_zero_at_beginning( $a, $b ){

		$order = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'desc'; 

		if ( ( $a == 0 || $b == 0 ) && $a != $b ) {
			if ( $order == 'desc' ) // Set 0 to be > anything other than itself & anything other than 0 to be less than 0
				$result = ( $a == 0 ) ? 1 : -1;
			elseif ( $order == 'asc' ) // Set 0 to be < anything other than itself & anything other than 0 to be greater than 0
				$result = ( $a == 0 ) ? -1 : 1;
		} else {
			$result = strcmp( $a, $b );
		}

		return $result;
	}
}
