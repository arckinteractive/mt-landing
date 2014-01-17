<?php
/**
 * Subscriptions Order Class
 * 
 * Mirrors and overloads a few functions in the WC_Order class to work for subscriptions. 
 *
 * @package		WooCommerce Subscriptions
 * @subpackage	WC_Subscriptions_Order
 * @category	Class
 * @author		Brent Shepherd
 */
class WC_Subscriptions_Order {

	/**
	 * Store a record of which product/item IDs need to have subscriptions details updated 
	 * whenever a subscription is saved via the "Edit Order" page.
	 */
	private static $requires_update = array(
		'next_billing_date' => array(),
		'trial_expiration'  => array(),
		'expiration_date'   => array(),
	);

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 * 
	 * @since 1.0
	 */
	public static function init() {
		add_filter( 'woocommerce_get_order_item_totals', __CLASS__ . '::get_order_item_totals', 10, 2 );
		add_filter( 'woocommerce_get_formatted_order_total', __CLASS__ . '::get_formatted_order_total', 10, 2 );
		add_filter( 'woocommerce_order_formatted_line_subtotal', __CLASS__ . '::get_formatted_line_total', 10, 3 );
		add_filter( 'woocommerce_order_subtotal_to_display', __CLASS__ . '::get_subtotal_to_display', 10, 3 );
		add_filter( 'woocommerce_order_cart_discount_to_display', __CLASS__ . '::get_cart_discount_to_display', 10, 3 );
		add_filter( 'woocommerce_order_discount_to_display', __CLASS__ . '::get_order_discount_to_display', 10, 3 );
		add_filter( 'woocommerce_order_shipping_to_display', __CLASS__ . '::get_shipping_to_display', 10, 2 );

		add_filter( 'woocommerce_admin_order_totals_after_shipping', __CLASS__ . '::recurring_order_totals_meta_box_section', 100, 1 );

		add_action( 'woocommerce_thankyou', __CLASS__ . '::subscription_thank_you' );

		add_action( 'manage_shop_order_posts_custom_column', __CLASS__ . '::add_contains_subscription_hidden_field', 10, 1 );
		add_action( 'woocommerce_admin_order_data_after_order_details', __CLASS__ . '::contains_subscription_hidden_field', 10, 1 );

		add_action( 'woocommerce_process_shop_order_meta', __CLASS__ . '::pre_process_shop_order_meta', 0, 2 ); // Need to fire before WooCommerce
		add_action( 'woocommerce_process_shop_order_meta', __CLASS__ . '::process_shop_order_item_meta', 11, 2 ); // Then fire after WooCommerce

		// Record initial payment against the subscription
		add_action( 'woocommerce_payment_complete', __CLASS__ . '::maybe_record_order_payment', 10, 1 );
		add_action( 'woocommerce_order_status_processing', __CLASS__ . '::maybe_record_order_payment', 10, 1 );
		add_action( 'woocommerce_order_status_completed', __CLASS__ . '::maybe_record_order_payment', 10, 1 );

		// Prefill subscription item meta when manually adding a subscription to an order
		add_action( 'woocommerce_new_order_item', __CLASS__ . '::prefill_order_item_meta', 10, 1 ); // WC 2.0+
		add_action( 'wp_ajax_woocommerce_subscriptions_prefill_order_item_meta', __CLASS__ . '::prefill_order_item_meta_old', 10 ); // WC 1.x
		add_action( 'wp_ajax_woocommerce_subscriptions_calculate_line_taxes', __CLASS__ . '::calculate_recurring_line_taxes', 10 );
		add_action( 'wp_ajax_woocommerce_subscriptions_remove_line_tax', __CLASS__ . '::remove_line_tax', 10 );
		add_action( 'wp_ajax_woocommerce_subscriptions_add_line_tax', __CLASS__ . '::add_line_tax' );

		// Don't allow downloads for inactive subscriptions
		add_action( 'woocommerce_order_is_download_permitted', __CLASS__ . '::is_download_permitted', 10, 2 );
	}

	/*
	 * Helper functions for extracting the details of subscriptions in an order
	 */

	/**
	 * Checks an order to see if it contains a subscription.
	 *
	 * @param $order mixed A WC_Order object or the ID of the order which the subscription was purchased in.
	 * @return bool True if the order contains a subscription, otherwise false.
	 * @version 1.2
	 * @since 1.0
	 */
	public static function order_contains_subscription( $order ) {

		if ( ! is_object( $order ) )
			$order = new WC_Order( $order );

		$contains_subscription = false;

		foreach ( $order->get_items() as $order_item ) {
			if ( self::is_item_subscription( $order, $order_item ) ) {
				$contains_subscription = true;
				break;
			}
		}

		return $contains_subscription;
	}

	/**
	 * Checks if a subscription requires manual payment because the payment gateway used to purchase the subscription
	 * did not support automatic payments at the time of the subscription sign up. Or because we're on a staging site.
	 *
	 * @param $order mixed A WC_Order object or the ID of the order which the subscription was purchased in.
	 * @return bool True if the subscription exists and requires manual payments, false if the subscription uses automatic payments (defaults to false for backward compatibility).
	 * @since 1.2
	 */
	public static function requires_manual_renewal( $order ) {

		if ( 'true' == self::get_meta( $order, '_wcs_requires_manual_renewal', 'false' ) || WC_Subscriptions::is_duplicate_site() )
			$requires_manual_renewal = true;
		else
			$requires_manual_renewal = false;

		return $requires_manual_renewal;
	}

	/**
	 * Returns the total amount to be charged at the outset of the Subscription.
	 * 
	 * This may return 0 if there is a free trial period and no sign up fee, otherwise it will be the sum of the sign up 
	 * fee and price per period. This function should be used by payment gateways for the initial payment.
	 * 
	 * @param $order mixed A WC_Order object or the ID of the order which the subscription was purchased in.
	 * @return float The total initial amount charged when the subscription product in the order was first purchased, if any.
	 * @since 1.1
	 */
	public static function get_total_initial_payment( $order ) {

		if ( ! is_object( $order ) )
			$order = new WC_Order( $order );

		$order_total     = $order->get_total();
		$recurring_total = self::get_recurring_total( $order );
		$trial_length    = self::get_subscription_trial_length( $order );

		// If there is a free trial period and no sign up fee, the initial payment is 0
		if ( $trial_length > 0 && $order_total == $recurring_total )
			$initial_payment = 0;
		else
			$initial_payment = $order_total; // Order total already accounts for sign up fees when there is no trial period

		return $initial_payment;
	}

	/**
	 * Returns the total license fee for a subscription product in an order.
	 * 
	 * @param $order mixed A WC_Order object or the ID of the order which the subscription was purchased in.
	 * @param $product_id int (optional) The post ID of the subscription WC_Product object purchased in the order. Defaults to the ID of the first product purchased in the order.
	 * @return float The initial license fee charged when the subscription product in the order was first purchased, if any.
	 * @since 1.0
	 */
	public static function get_sign_up_fee( $order, $product_id = '' ) {

		$recurring_total = self::get_recurring_total( $order );
		$initial_payment = self::get_total_initial_payment( $order );

		if ( self::get_subscription_trial_length( $order ) > 0 )
			$sign_up_fee = $initial_payment;
		elseif ( $recurring_total != $initial_payment )
			$sign_up_fee = $initial_payment - $recurring_total;
		else
			$sign_up_fee = 0;

		return $sign_up_fee;
	}

	/**
	 * Returns the period (e.g. month) for a each subscription product in an order.
	 * 
	 * @param $order mixed A WC_Order object or the ID of the order which the subscription was purchased in.
	 * @param $product_id int (optional) The post ID of the subscription WC_Product object purchased in the order. Defaults to the ID of the first product purchased in the order.
	 * @return string A string representation of the period for the subscription, i.e. day, week, month or year.
	 * @since 1.0
	 */
	public static function get_subscription_period( $order, $product_id = '' ) {
		return self::get_item_meta( $order, '_subscription_period', $product_id );
	}

	/**
	 * Returns the billing interval for a each subscription product in an order.
	 *
	 * For example, this would return 3 for a subscription charged every 3 months or 1 for a subscription charged every month.
	 *
	 * @param $order mixed A WC_Order object or the ID of the order which the subscription was purchased in.
	 * @param $product_id int (optional) The post ID of the subscription WC_Product object purchased in the order. Defaults to the ID of the first product purchased in the order.
	 * @return int The billing interval for a each subscription product in an order.
	 * @since 1.0
	 */
	public static function get_subscription_interval( $order, $product_id = '' ) {
		return self::get_item_meta( $order, '_subscription_interval', $product_id, 1 );
	}

	/**
	 * Returns the length for a subscription in an order.
	 * 
	 * There must be only one subscription in an order for this to be accurate. 
	 * 
	 * @param $order mixed A WC_Order object or the ID of the order which the subscription was purchased in.
	 * @param $product_id int (optional) The post ID of the subscription WC_Product object purchased in the order. Defaults to the ID of the first product purchased in the order.
	 * @return int The number of periods for which the subscription will recur. For example, a $5/month subscription for one year would return 12. A $10 every 3 month subscription for one year would also return 12.
	 * @since 1.0
	 */
	public static function get_subscription_length( $order, $product_id = '' ) {
		return self::get_item_meta( $order, '_subscription_length', $product_id, 0 );
	}

	/**
	 * Returns the length for a subscription product's trial period as set when added to an order.
	 *
	 * The trial period is the same as the subscription period, as derived from @see self::get_subscription_period().
	 *
	 * For now, there must be only one subscription in an order for this to be accurate. 
	 *
	 * @param $order mixed A WC_Order object or the ID of the order which the subscription was purchased in.
	 * @param $product_id int (optional) The post ID of the subscription WC_Product object purchased in the order. Defaults to the ID of the first product purchased in the order.
	 * @return int The number of periods the trial period lasts for. For no trial, this will return 0, for a 3 period trial, it will return 3.
	 * @since 1.1
	 */
	public static function get_subscription_trial_length( $order, $product_id = '' ) {
		return self::get_item_meta( $order, '_subscription_trial_length', $product_id, 0 );
	}

	/**
	 * Returns the period (e.g. month)  for a subscription product's trial as set when added to an order.
	 *
	 * As of 1.2.x, a subscriptions trial period may be different than the recurring period
	 *
	 * For now, there must be only one subscription in an order for this to be accurate.
	 *
	 * @param $order mixed A WC_Order object or the ID of the order which the subscription was purchased in.
	 * @param $product_id int (optional) The post ID of the subscription WC_Product object purchased in the order. Defaults to the ID of the first product purchased in the order.
	 * @return string A string representation of the period for the subscription, i.e. day, week, month or year.
	 * @since 1.2
	 */
	public static function get_subscription_trial_period( $order, $product_id = '' ) {

		$period = self::get_item_meta( $order, '_subscription_trial_period', $product_id, '' );

		// Backward compatibility
		if ( empty( $period ) )
			$period = self::get_subscription_period( $order, $product_id );

		return $period;
	}


	/**
	 * Returns the recurring amount for an item
	 *
	 * @param $order WC_Order A WC_Order object  
	 * @param $product_id int The product/post ID of a subscription
	 * @return float The total amount to be charged for each billing period, if any, not including failed payments.
	 * @since 1.2
	 */
	public static function get_item_recurring_amount( $order, $product_id ) {
		return self::get_item_meta( $order, '_subscription_recurring_amount', $product_id, 0 );
	}

	/**
	 * Returns the sign up fee for an item
	 *
	 * @param $order WC_Order A WC_Order object  
	 * @param $product_id int The product/post ID of a subscription
	 * @since 1.2
	 */
	public static function get_item_sign_up_fee( $order, $product_id = '' ) {

		$item = self::get_item_by_product_id( $order, $product_id );

		$line_subtotal           = $order->get_line_subtotal( $item );
		$recurring_line_subtotal = self::get_item_recurring_amount( $order, $product_id );

		if ( self::get_subscription_trial_length( $order, $product_id ) > 0 )
			$sign_up_fee = $line_subtotal;
		else if ( $line_subtotal != $recurring_line_subtotal )
			$sign_up_fee = $line_subtotal - self::get_item_recurring_amount( $order, $product_id );
		else
			$sign_up_fee = 0;

		return $sign_up_fee;
	}

	/**
	 * Takes a subscription product's ID and returns the timestamp on which the next payment is due.
	 *
	 * A convenience wrapper for @see WC_Subscriptions_Manager::get_next_payment_date() to get the
	 * next payment date for a subscription when all you have is the order and product.
	 *
	 * @param $order mixed A WC_Order object or the ID of the order which the subscription was purchased in.
	 * @param $product_id int The product/post ID of the subscription
	 * @param mixed $deprecated Never used.
	 * @return int If no more payments are due, returns 0, otherwise returns a timestamp of the date the next payment is due.
	 * @version 1.2
	 * @since 1.0
	 */
	public static function get_next_payment_timestamp( $order, $product_id, $deprecated = null ) {

		if ( null != $deprecated ) { // We want to calculate a date
			_deprecated_argument( __CLASS__ . '::' . __FUNCTION__, '1.2' );
			$next_payment_timestamp = self::calculate_next_payment_date( $order, $product_id, 'timestamp', $deprecated );
		} else {

			if ( ! is_object( $order ) )
				$order = new WC_Order( $order );

			$subscription_key       = WC_Subscriptions_Manager::get_subscription_key( $order->id, $product_id );
			$next_payment_timestamp = WC_Subscriptions_Manager::get_next_payment_date( $subscription_key, $order->user_id, 'timestamp' );
		}

		return $next_payment_timestamp;
	}

	/**
	 * Takes a subscription product's ID and the order it was purchased in and returns the date on 
	 * which the next payment is due.
	 *
	 * A convenience wrapper for @see WC_Subscriptions_Manager::get_next_payment_date() to get the next
	 * payment date for a subscription when all you have is the order and product.
	 *
	 * @param $order mixed A WC_Order object or the ID of the order which the subscription was purchased in.
	 * @param $product_id int The product/post ID of the subscription
	 * @param mixed $deprecated Never used.
	 * @return mixed If no more payments are due, returns 0, otherwise it returns the MySQL formatted date/time string for the next payment date.
	 * @version 1.2
	 * @since 1.0
	 */
	public static function get_next_payment_date( $order, $product_id, $deprecated = null ) {

		if ( null != $deprecated ) { // We want to calculate a date
			_deprecated_argument( __CLASS__ . '::' . __FUNCTION__, '1.2' );
			$next_payment_date = self::calculate_next_payment_date( $order, $product_id, 'mysql', $deprecated );
		} else {
			if ( ! is_object( $order ) )
				$order = new WC_Order( $order );

			$subscription_key  = WC_Subscriptions_Manager::get_subscription_key( $order->id, $product_id );
			$next_payment_date = WC_Subscriptions_Manager::get_next_payment_date( $subscription_key, $order->user_id, 'mysql' );
		}

		return $next_payment_date;
	}

	/**
	 * Takes a subscription product's ID and the order it was purchased in and returns the date on 
	 * which the last payment was made.
	 *
	 * A convenience wrapper for @see WC_Subscriptions_Manager::get_next_payment_date() to get the next
	 * payment date for a subscription when all you have is the order and product.
	 *
	 * @param $order mixed A WC_Order object or the ID of the order which the subscription was purchased in.
	 * @param $product_id int The product/post ID of the subscription
	 * @param mixed $deprecated Never used.
	 * @return mixed If no more payments are due, returns 0, otherwise it returns the MySQL formatted date/time string for the next payment date.
	 * @version 1.2.1
	 * @since 1.0
	 */
	public static function get_last_payment_date( $order, $product_id ) {

		if ( ! is_object( $order ) )
			$order = new WC_Order( $order );

		$subscription_key  = WC_Subscriptions_Manager::get_subscription_key( $order->id, $product_id );
		$next_payment_date = WC_Subscriptions_Manager::get_last_payment_date( $subscription_key, $order->user_id );

		return $next_payment_date;
	}

	/**
	 * Takes a subscription product's ID and calculates the date on which the next payment is due.
	 *
	 * Calculation is based on $from_date if specified, otherwise it will fall back to the last
	 * completed payment, the subscription's start time, or the current date/time, in that order.
	 *
	 * The next payment date will occur after any free trial period and up to any expiration date.
	 *
	 * @param $order mixed A WC_Order object or the ID of the order which the subscription was purchased in.
	 * @param $product_id int The product/post ID of the subscription
	 * @param $type string (optional) The format for the Either 'mysql' or 'timestamp'.
	 * @param $from_date mixed A MySQL formatted date/time string from which to calculate the next payment date, or empty (default), which will use the last payment on the subscription, or today's date/time if no previous payments have been made.
	 * @return mixed If there is no future payment set, returns 0, otherwise it will return a date of the next payment in the form specified by $type
	 * @since 1.0
	 */
	public static function calculate_next_payment_date( $order, $product_id, $type = 'mysql', $from_date = '' ) {

		if ( ! is_object( $order ) )
			$order = new WC_Order( $order );

		$from_date_arg = $from_date;

		$subscription              = WC_Subscriptions_Manager::get_users_subscription( $order->user_id, WC_Subscriptions_Manager::get_subscription_key( $order->id, $product_id ) );
		$subscription_period       = self::get_subscription_period( $order, $product_id );
		$subscription_interval     = self::get_subscription_interval( $order, $product_id );
		$subscription_trial_length = self::get_subscription_trial_length( $order, $product_id );
		$subscription_trial_period = self::get_subscription_trial_period( $order, $product_id );

		$trial_end_time   = ( ! empty( $subscription['trial_expiry_date'] ) ) ? $subscription['trial_expiry_date'] : WC_Subscriptions_Product::get_trial_expiration_date( $product_id, get_gmt_from_date( $order->order_date ) );
		$trial_end_time   = strtotime( $trial_end_time );

		// If the subscription is not active, there is no next payment date
		if ( $subscription['status'] != 'active' ) {

			$next_payment_timestamp = 0;

		// If the subscription has a free trial period, and we're still in the free trial period, the next payment is due at the end of the free trial
		} elseif ( $subscription_trial_length > 0 && $trial_end_time > ( gmdate( 'U' ) + 60 * 60 * 23 + 120 ) ) { // Make sure trial expiry is more than 23+ hours in the future to account for trial expiration dates incorrectly stored in non-UTC/GMT timezone (and also for any potential changes to the site's timezone)

			$next_payment_timestamp = $trial_end_time;

		// The next payment date is {interval} billing periods from the from date
		} else {

			// We have a timestamp
			if ( ! empty( $from_date ) && is_numeric( $from_date ) )
				$from_date = date( 'Y-m-d H:i:s', $from_date );

			if ( empty( $from_date ) ) {

				if ( ! empty( $subscription['completed_payments'] ) ) {
					$from_date = array_pop( $subscription['completed_payments'] );
					$add_failed_payments = true;
				} else if ( ! empty ( $subscription['start_date'] ) ) {
					$from_date = $subscription['start_date'];
					$add_failed_payments = true;
				} else {
					$from_date = gmdate( 'Y-m-d H:i:s' );
					$add_failed_payments = false;
				}

				$failed_payment_count = self::get_failed_payment_count( $order, $product_id );

				// Maybe take into account any failed payments
				if ( true === $add_failed_payments && $failed_payment_count > 0 ) {
					$failed_payment_periods = $failed_payment_count * $subscription_interval;
					$from_timestamp = strtotime( $from_date );

					if ( 'month' == $subscription_period )
						$from_date = date( 'Y-m-d H:i:s', WC_Subscriptions::add_months( $from_timestamp, $failed_payment_periods ) );
					else // Safe to just add the billing periods
						$from_date = date( 'Y-m-d H:i:s', strtotime( "+ {$failed_payment_periods} {$subscription_period}", $from_timestamp ) );
				}
			}

			$from_timestamp = strtotime( $from_date );

			if ( 'month' == $subscription_period ) // Workaround potential PHP issue
				$next_payment_timestamp = WC_Subscriptions::add_months( $from_timestamp, $subscription_interval );
			else
				$next_payment_timestamp = strtotime( "+ {$subscription_interval} {$subscription_period}", $from_timestamp );

			// Make sure the next payment is in the future
			$i = 1;
			while ( $next_payment_timestamp < gmdate( 'U' ) && $i < 30 ) {
				if ( 'month' == $subscription_period ) {
					$next_payment_timestamp = WC_Subscriptions::add_months( $next_payment_timestamp, $subscription_interval );
				} else { // Safe to just add the billing periods
					$next_payment_timestamp = strtotime( "+ {$subscription_interval} {$subscription_period}", $next_payment_timestamp );
				}
				$i = $i + 1;
			}

		}

		// If the subscription has an expiry date and the next billing period comes after the expiration, return 0
		if ( isset( $subscription['expiry_date'] ) && 0 != $subscription['expiry_date'] && ( $next_payment_timestamp + 120 ) > strtotime( $subscription['expiry_date'] ) )
			$next_payment_timestamp =  0;

		$next_payment = ( 'mysql' == $type && 0 != $next_payment_timestamp ) ? date( 'Y-m-d H:i:s', $next_payment_timestamp ) : $next_payment_timestamp;

		return apply_filters( 'woocommerce_subscriptions_calculated_next_payment_date', $next_payment, $order, $product_id, $type, $from_date, $from_date_arg );
	}

	/**
	 * Gets the product ID for an order item in a way that is backwards compatible with WC 1.x.
	 *
	 * Version 2.0 of WooCommerce changed the ID of an order item from its product ID to a unique ID for that particular item. 
	 * This function checks if the 'product_id' field exists on an order item before falling back to 'id'.
	 *
	 * @param $order_item Array An order item in the structure returned by WC_Order::get_items()
	 * @since 1.2.5
	 */
	public static function get_items_product_id( $order_item ) {
		return ( isset( $order_item['product_id'] ) ) ? $order_item['product_id'] : $order_item['id'];
	}

	/**
	 * Gets an item by product id from an order.
	 *
	 * @param $order WC_Order | int The WC_Order object or ID of the order for which the meta should be sought. 
	 * @param $product_id int The product/post ID of a subscription product.
	 * @since 1.2.5
	 */
	public static function get_item_by_product_id( $order, $product_id = '' ) {

		if ( ! is_object( $order ) )
			$order = new WC_Order( $order );

		foreach ( $order->get_items() as $item )
			if ( self::get_items_product_id( $item ) == $product_id || empty( $product_id ) )
				return $item;

		return array();
	}

	/**
	 * Gets an individual order item by ID without requiring the order ID associated with it.
	 *
	 * @param $order WC_Order | int The WC_Order object or ID of the order for which the meta should be sought. 
	 * @param $item_id int The product/post ID of a subscription. Option - if no product id is provided, the first item's meta will be returned
	 * @return $item array An array containing the order_item_id, order_item_name, order_item_type, order_id and any item_meta. Array structure matches that returned by WC_Order::get_items()
	 * @since 1.2.5
	 */
	public static function get_item_by_id( $order_item_id ) {
		global $wpdb;

		$item = $wpdb->get_row( $wpdb->prepare( "
			SELECT order_item_id, order_item_name, order_item_type, order_id
			FROM   {$wpdb->prefix}woocommerce_order_items
			WHERE  order_item_id = %d
		", $order_item_id ) );

		$order = new WC_Order( $order_id );

		$item['name']      = $item->order_item_name;
		$item['type']      = $item->order_item_type;
		$item['item_meta'] = $order->get_item_meta( $item->order_item_id );

		// Put meta into item array
		foreach( $item['item_meta'] as $meta_name => $meta_value ) {
			$key = substr( $meta_name, 0, 1 ) == '_' ? substr( $meta_name, 1 ) : $meta_name;
			$item[$key] = $meta_value[0];
		}

		return $item;
	}

	/**
	 * A unified API for accessing product specific meta on an order.
	 * 
	 * @param $order WC_Order | int The WC_Order object or ID of the order for which the meta should be sought. 
	 * @param $meta_key string The key as stored in the post meta table for the meta item. 
	 * @param $product_id int The product/post ID of a subscription. Option - if no product id is provided, it is expected that only one item exists and the last item's meta will be returned
	 * @param $default mixed (optional) The default value to return if the meta key does not exist. Default 0.
	 * @since 1.2
	 */
	public static function get_item_meta( $order, $meta_key, $product_id = '', $default = 0 ) {

		$meta_value = $default;

		$item = self::get_item_by_product_id( $order, $product_id );

		if ( ! empty ( $item ) ) {

			foreach ( $item['item_meta'] as $key => $value ) {

				// WC 1.x
				if ( isset( $value['meta_name'] ) ) {
					$key   = $value['meta_name'];
					$value = $value['meta_value'];
				} else {
					$value = $value[0];
				}

				if ( $key == $meta_key )
					$meta_value = $value;

			}
		}

		return $meta_value;
	}

	/**
	 * Access an individual piece of item metadata (@see woocommerce_get_order_item_meta returns all metadata for an item)
	 *
	 * You may think it would make sense if this function was called "get_item_meta", and you would be correct, but a function
	 * with that name was created before the item meta data API of WC 2.0, so it needs to persist with it's own different
	 * set of parameters.
	 *
	 * @param $meta_id int The order item meta data ID of the item you want to get.
	 * @since 1.2.5
	 */
	public static function get_item_meta_data( $meta_id ) {
		global $wpdb;

		$item_meta = $wpdb->get_row( $wpdb->prepare( "
			SELECT *
			FROM   {$wpdb->prefix}woocommerce_order_itemmeta
			WHERE  meta_id = %d
		", $meta_id ) );

		return $item_meta;
	}

	/**
	 * Gets the name of a subscription item by product ID from an order.
	 * 
	 * @param $order WC_Order | int The WC_Order object or ID of the order for which the meta should be sought. 
	 * @param $product_id int The product/post ID of a subscription. Option - if no product id is provided, it is expected that only one item exists and the last item's meta will be returned
	 * @since 1.2
	 */
	public static function get_item_name( $order, $product_id = '' ) {

		$item = self::get_item_by_product_id( $order, $product_id );

		if ( isset( $item['name'] ) )
			return $item['name'];
		else
			return '';
	}

	/**
	 * A unified API for accessing subscription order meta, especially for license fee related order meta.
	 * 
	 * @param $order WC_Order | int The WC_Order object or ID of the order for which the meta should be sought. 
	 * @param $meta_key string The key as stored in the post meta table for the meta item. 
	 * @param $default mixed (optional) The default value to return if the meta key does not exist. Default 0.
	 * @since 1.0
	 */
	public static function get_meta( $order, $meta_key, $default = 0 ) {

		if ( ! is_object( $order ) )
			$order = new WC_Order( $order );

		if ( isset( $order->order_custom_fields[$meta_key] ) ) {
			$meta_value = maybe_unserialize( $order->order_custom_fields[$meta_key][0] );
		} else {
			$meta_value = get_post_meta( $order->id, $meta_key, true );

			if ( empty( $meta_value ) )
				$meta_value = $default;
		}

		return $meta_value;
	}

	/* 
	 * Functions to customise the way WooCommerce displays order prices.
	 */

	/**
	 * Appends the subscription period/duration string to order total
	 *
	 * @since 1.0
	 */
	public static function get_formatted_line_total( $formatted_total, $item, $order ) {

		if ( WC_Subscriptions_Order::is_item_subscription( $order, $item ) ) {

			$item_id = self::get_items_product_id( $item );

			$subscription_details = array(
				'subscription_interval' => self::get_subscription_interval( $order, $item_id ),
				'subscription_period'   => self::get_subscription_period( $order, $item_id ),
				'subscription_length'   => self::get_subscription_length( $order, $item_id ),
				'trial_length'          => self::get_subscription_trial_length( $order, $item_id ),
				'trial_period'          => self::get_subscription_trial_period( $order, $item_id )
			);

			$sign_up_fee  = self::get_sign_up_fee( $order );
			$trial_length = self::get_subscription_trial_length( $order );

			if ( $sign_up_fee > 0 && $trial_length == 0 && $subscription_details['subscription_interval'] != $subscription_details['subscription_length'] )
				$subscription_details['initial_amount'] = $formatted_total;
			elseif ( $sign_up_fee > 0 && $trial_length > 0 )
				$subscription_details['initial_amount'] = $formatted_total;
			else
				$subscription_details['initial_amount'] = '';

			// Use the core WC_Order::get_formatted_line_subtotal() WC function to get the recurring total
			remove_filter( 'woocommerce_order_formatted_line_subtotal', __CLASS__ . '::' . __FUNCTION__, 10, 3 ); // Avoid getting into an infinite loop

			foreach ( self::get_recurring_items( $order ) as $recurring_item )
				if ( self::get_items_product_id( $recurring_item ) == $item_id )
					$subscription_details['recurring_amount'] = $order->get_formatted_line_subtotal( $recurring_item );

			add_filter( 'woocommerce_order_formatted_line_subtotal', __CLASS__ . '::' . __FUNCTION__, 10, 3 );

			$formatted_total = WC_Subscriptions_Manager::get_subscription_price_string( $subscription_details );
		}

		return $formatted_total;
	}

	/**
	 * Appends the subscription period/duration string to order subtotal
	 *
	 * @since 1.0
	 */
	public static function get_subtotal_to_display( $subtotal, $compound, $order ) {
		global $woocommerce;

		if( self::order_contains_subscription( $order ) ) {

			$subscription_details = array(
				'subscription_interval' => self::get_subscription_interval( $order ),
				'subscription_period'   => self::get_subscription_period( $order )
			);

			$sign_up_fee  = self::get_sign_up_fee( $order );
			$trial_length = self::get_subscription_trial_length( $order );

			// If there is a free trial period and no sign up fee, just show this amount recurring
			if ( $trial_length > 0 && $sign_up_fee == 0 ) {

				$subscription_details['recurring_amount'] = $subtotal;

				$subtotal = WC_Subscriptions_Manager::get_subscription_price_string( $subscription_details );

			} else {

				if ( $sign_up_fee > 0 && $trial_length == 0 && $subscription_details['subscription_interval'] != self::get_subscription_length( $order ) )
					$subscription_details['initial_amount'] = $subtotal;
				elseif ( $sign_up_fee > 0 && $trial_length > 0 )
					$subscription_details['initial_amount'] = $subtotal;
				else
					$subscription_details['initial_amount'] = '';

				$recurring_subtotal = 0;

				if ( ! $compound ) {

					foreach ( self::get_recurring_items( $order ) as $item ) {
						$recurring_subtotal += $order->get_line_subtotal( $item ); // Can use the $order function here as we pass it the recurring item amounts

						if ( ! $order->display_cart_ex_tax )
							$recurring_subtotal += $item['line_subtotal_tax'];
					}

					$subscription_details['recurring_amount'] = $recurring_subtotal;

					$subtotal = WC_Subscriptions_Manager::get_subscription_price_string( $subscription_details );

					if ( $order->display_cart_ex_tax && $order->prices_include_tax )
						$subtotal .= ' <small>' . $woocommerce->countries->ex_tax_or_vat() . '</small>';

				} else {

					foreach ( self::get_recurring_items( $order ) as $item )
						$recurring_subtotal += $item['line_subtotal'];

					// Add Shipping Costs
					$recurring_subtotal += self::get_recurring_shipping_total( $order );

					// Remove non-compound taxes
					foreach ( self::get_recurring_taxes( $order ) as $tax ) {
						if ( isset( $tax['compound'] ) && $tax['compound'] )
							continue;

						$recurring_subtotal = $recurring_subtotal + $tax['cart_tax'] + $tax['shipping_tax'];
					}

					// Remove discounts
					$recurring_subtotal = $recurring_subtotal - self::get_recurring_cart_discount( $order );

					$subscription_details['recurring_amount'] = $recurring_subtotal;

					$subtotal = WC_Subscriptions_Manager::get_subscription_price_string( $subscription_details );

				}

			}
		}

		return $subtotal;
	}

	/**
	 * Appends the subscription period/duration string to order total
	 *
	 * @since 1.0
	 */
	public static function get_cart_discount_to_display( $discount, $order ) {

		if( self::order_contains_subscription( $order ) ) {

			$subscription_details = array(
				'recurring_amount'      => self::get_recurring_discount_cart( $order ),
				'subscription_interval' => self::get_subscription_interval( $order ),
				'subscription_period'   => self::get_subscription_period( $order )
			);

			$sign_up_fee  = self::get_sign_up_fee( $order );
			$trial_length = self::get_subscription_trial_length( $order );

			if ( $sign_up_fee > 0 && $trial_length == 0 && $subscription_details['subscription_interval'] != self::get_subscription_length( $order ) )
				$subscription_details['initial_amount'] = $discount;
			elseif ( $sign_up_fee > 0 && $trial_length > 0 )
				$subscription_details['initial_amount'] = $discount;
			elseif ( $discount !== woocommerce_price( $subscription_details['recurring_amount'] ) ) // Applying initial payment only discount
				$subscription_details['initial_amount'] = $discount;
			else
				$subscription_details['initial_amount'] = '';

			$discount = WC_Subscriptions_Manager::get_subscription_price_string( $subscription_details );
			$discount = sprintf( __( '%s discount', WC_Subscriptions::$text_domain ), $discount );
		}

		return $discount;
	}

	/**
	 * Appends the subscription period/duration string to order total
	 *
	 * @since 1.0
	 */
	public static function get_order_discount_to_display( $discount, $order ) {

		if( self::order_contains_subscription( $order ) ) {

			$subscription_details = array(
				'recurring_amount'      => self::get_recurring_discount_total( $order ),
				'subscription_interval' => self::get_subscription_interval( $order ),
				'subscription_period'   => self::get_subscription_period( $order )
			);

			$sign_up_fee  = self::get_sign_up_fee( $order );
			$trial_length = self::get_subscription_trial_length( $order );

			if ( $sign_up_fee > 0 && $trial_length == 0 && $subscription_details['subscription_interval'] != self::get_subscription_length( $order ) )
				$subscription_details['initial_amount'] = $discount;
			elseif ( $sign_up_fee > 0 && $trial_length > 0 )
				$subscription_details['initial_amount'] = $discount;
			elseif ( $discount !== woocommerce_price( $subscription_details['recurring_amount'] ) ) // Applying initial payment only discount
				$subscription_details['initial_amount'] = $discount;
			else
				$subscription_details['initial_amount'] = '';

			$discount = WC_Subscriptions_Manager::get_subscription_price_string( $subscription_details );
			$discount = sprintf( __( '%s discount', WC_Subscriptions::$text_domain ), $discount );
		}

		return $discount;
	}

	/**
	 * Appends the subscription period/duration string to order total
	 *
	 * @since 1.0
	 */
	public static function get_formatted_order_total( $formatted_total, $order ) {

		if( self::order_contains_subscription( $order ) ) {

			$subscription_details = array(
				'recurring_amount'      => self::get_recurring_total( $order ),
				'subscription_interval' => self::get_subscription_interval( $order ),
				'subscription_period'   => self::get_subscription_period( $order ),
				'subscription_length'   => self::get_subscription_length( $order ),
				'trial_length'          => self::get_subscription_trial_length( $order ),
				'trial_period'          => self::get_subscription_trial_period( $order )
			);

			$sign_up_fee  = self::get_sign_up_fee( $order );
			$trial_length = self::get_subscription_trial_length( $order );

			if ( $sign_up_fee > 0 && $trial_length == 0 && $subscription_details['subscription_interval'] != $subscription_details['subscription_length'] )
				$subscription_details['initial_amount'] = $formatted_total;
			elseif ( $sign_up_fee > 0 && $trial_length > 0 )
				$subscription_details['initial_amount'] = $formatted_total;
			elseif ( $formatted_total !== woocommerce_price( $subscription_details['recurring_amount'] ) ) // Applying initial payment only discount
				$subscription_details['initial_amount'] = $formatted_total;
			else
				$subscription_details['initial_amount'] = '';

			$formatted_total = WC_Subscriptions_Manager::get_subscription_price_string( $subscription_details );
		}

		return $formatted_total;
	}

	/**
	 * Appends the subscription period/duration string to shipping fee
	 *
	 * @since 1.0
	 */
	public static function get_shipping_to_display( $shipping_to_display, $order ) {

		if ( self::order_contains_subscription( $order ) && self::get_recurring_shipping_total( $order ) > 0 ) {

			$subscription_details = array(
				'recurring_amount'      => $shipping_to_display,
				'subscription_interval' => self::get_subscription_interval( $order ),
				'subscription_period'   => self::get_subscription_period( $order ),
				'trial_length'          => self::get_subscription_trial_length( $order ),
				'trial_period'          => self::get_subscription_trial_period( $order )
			);

			$shipping_to_display = WC_Subscriptions_Manager::get_subscription_price_string( $subscription_details );
		}

		return $shipping_to_display;
	}

	/**
	 * Individual totals are taken care of by filters, but taxes are not, so we need to override them here.
	 * 
	 * @since 1.0
	 */
	public static function get_order_item_totals( $total_rows, $order ) {
		global $woocommerce;

		if ( self::order_contains_subscription( $order ) && self::get_recurring_total_tax( $order ) > 0 && 'incl' !== $order->tax_display_cart ) {

			$order_taxes         = $order->get_taxes();
			$recurring_taxes     = self::get_recurring_taxes( $order );
			$subscription_length = self::get_subscription_length( $order );
			$sign_up_fee         = self::get_sign_up_fee( $order );
			$trial_length        = self::get_subscription_trial_length( $order );

			// Only want to display recurring amounts for taxes, no need for trial period, length etc.
			$subscription_details = array(
				'subscription_interval' => self::get_subscription_interval( $order ),
				'subscription_period'   => self::get_subscription_period( $order )
			);

			if ( count( $order_taxes ) > 0 || count( $recurring_taxes ) > 0 ) {

				foreach ( $recurring_taxes as $index => $tax ) {

					if ( $tax['compound'] )
						continue;

					if ( isset( $tax['name'] ) ) { // WC 2.0+

						$tax_key      = sanitize_title( $tax['name'] );
						$tax_name     = $tax['name'];
						$tax_amount   = $tax['tax_amount'];
						$shipping_tax = $tax['shipping_tax_amount'];

						if ( $tax['tax_amount'] > 0 ) {

							foreach ( $order_taxes as $order_tax )
								if ( $tax_name == $order_tax['name'] )
									$order_tax_amount = isset( $order_tax['tax_amount'] ) ? $order_tax['tax_amount'] + $order_tax['shipping_tax_amount'] : '';

							$recurring_tax = isset( $tax['tax_amount'] ) ? $tax['tax_amount'] + $tax['shipping_tax_amount'] : '';
						}

					} else { // WC 1.x structure

						$tax_key      = sanitize_title( $tax['label'] );
						$tax_name     = $tax['label'];
						$tax_amount   = $tax['cart_tax'];
						$shipping_tax = $tax['shipping_tax'];

						if ( $tax_amount > 0 ) {

							foreach ( $order_taxes as $order_tax )
								if ( $tax_name == $order_tax['label'] )
									$order_tax_amount = isset( $order_tax['cart_tax'] ) ? $order_tax['cart_tax'] + $order_tax['shipping_tax'] : '';

							$recurring_tax = isset( $recurring_taxes[$index]['cart_tax'] ) ? $recurring_taxes[$index]['cart_tax'] + $recurring_taxes[$index]['shipping_tax'] : '';
						}
					}

					if ( $tax_amount > 0 ) {

						$subscription_details['recurring_amount'] = $recurring_tax;

						if ( $sign_up_fee > 0 && $trial_length == 0 && $subscription_details['subscription_interval'] != $subscription_length )
							$subscription_details['initial_amount'] = $order_tax_amount;
						elseif ( $sign_up_fee > 0 && $trial_length > 0 )
							$subscription_details['initial_amount'] = $order_tax_amount;
						elseif ( $order_tax_amount !== $subscription_details['recurring_amount'] ) // Applying initial payment only discount
							$subscription_details['initial_amount'] = $order_tax_amount;
						else
							$subscription_details['initial_amount'] = '';

						$total_rows[$tax_key]['value'] = WC_Subscriptions_Manager::get_subscription_price_string( $subscription_details );

					} elseif ( $shipping_tax > 0  ) { // Just a recurring shipping tax

						$subscription_details['recurring_amount'] = $shipping_tax;

						if ( $sign_up_fee > 0 && $trial_length == 0 && $subscription_details['subscription_interval'] != $subscription_length )
							$subscription_details['initial_amount'] = $shipping_tax;
						elseif ( $sign_up_fee > 0 && $trial_length > 0 )
							$subscription_details['initial_amount'] = $shipping_tax;
						elseif ( $shipping_tax !== $subscription_details['recurring_amount'] ) // Applying initial payment only discount
							$subscription_details['initial_amount'] = $shipping_tax;
						else
							$subscription_details['initial_amount'] = '';

						$shipping_tax_row = array(
							$tax_key . '_shipping' => array(
								'label' => $tax_name,
								'value' => WC_Subscriptions_Manager::get_subscription_price_string( $subscription_details )
							)
						);

						// Insert the tax just before the order total
						$total_rows = array_splice( $total_rows, 0, -1 ) + $shipping_tax_row + array_splice( $total_rows, -1 );
					}

				}

				foreach ( $recurring_taxes as $index => $tax ) {

					if ( ! $tax['compound'] )
						continue;

					if ( isset( $tax['name'] ) ) { // WC 2.0+

						$tax_key      = sanitize_title( $tax['name'] );
						$tax_name     = $tax['name'];
						$tax_amount   = $tax['tax_amount'];
						$shipping_tax = $tax['shipping_tax_amount'];

						if ( $tax['tax_amount'] > 0 ) {

							foreach ( $order_taxes as $order_tax )
								if ( $tax_name == $order_tax['name'] )
									$order_tax_amount = isset( $order_tax['tax_amount'] ) ? $order_tax['tax_amount'] + $order_tax['shipping_tax_amount'] : '';

							$recurring_tax = isset( $tax['tax_amount'] ) ? $tax['tax_amount'] + $tax['shipping_tax_amount'] : '';
						}

					} else { // WC 1.x structure

						$tax_key      = sanitize_title( $tax['label'] );
						$tax_name     = $tax['label'];
						$tax_amount   = $tax['cart_tax'];
						$shipping_tax = $tax['shipping_tax'];

						if ( $tax_amount > 0 ) {

							foreach ( $order_taxes as $order_tax )
								if ( $tax_name == $order_tax['label'] )
									$order_tax_amount = isset( $order_tax['cart_tax'] ) ? $order_tax['cart_tax'] + $order_tax['shipping_tax'] : '';

							$recurring_tax = isset( $tax['cart_tax'] ) ? $tax['cart_tax'] + $tax['shipping_tax'] : '';
						}
					}

					if ( $tax_amount > 0 ) {

						if ( $sign_up_fee > 0 && $trial_length == 0 && $subscription_details['subscription_interval'] != $subscription_length )
							$subscription_details['initial_amount'] = $order_tax_amount;
						elseif ( $sign_up_fee > 0 && $trial_length > 0 )
							$subscription_details['initial_amount'] = $order_tax_amount;
						elseif ( $order_tax_amount !== woocommerce_price( $subscription_details['recurring_amount'] ) ) // Applying initial payment only discount
							$subscription_details['initial_amount'] = $order_tax_amount;
						else
							$subscription_details['initial_amount'] = '';

						$subscription_details['recurring_amount'] = $recurring_tax;

						$total_rows[$tax_key]['value'] = WC_Subscriptions_Manager::get_subscription_price_string( $subscription_details );

					} elseif ( $shipping_tax > 0  ) { // Just a recurring shipping tax

						$subscription_details['recurring_amount'] = $shipping_tax;

						if ( $sign_up_fee > 0 && $trial_length == 0 && $subscription_details['subscription_interval'] != $subscription_length )
							$subscription_details['initial_amount'] = $shipping_tax;
						elseif ( $sign_up_fee > 0 && $trial_length > 0 )
							$subscription_details['initial_amount'] = $shipping_tax;
						elseif ( $shipping_tax !== woocommerce_price( $subscription_details['recurring_amount'] ) ) // Applying initial payment only discount
							$subscription_details['initial_amount'] = $shipping_tax;
						else
							$subscription_details['initial_amount'] = '';

						$shipping_tax_row = array(
							$tax_key . '_shipping' => array(
								'label' => $tax_name,
								'value' => WC_Subscriptions_Manager::get_subscription_price_string( $subscription_details )
							)
						);

						// Insert the tax just before the order total
						$total_rows = array_splice( $total_rows, 0, -1 ) + $shipping_tax_row + array_splice( $total_rows, -1, 0 );
					}
				}

			} else {

				if ( isset( $total_rows['tax'] ) ) {

					$subscription_details['recurring_amount'] = self::get_recurring_total_tax( $order );
					$order_total_tax = woocommerce_price( $order->get_total_tax() );

					if ( $sign_up_fee > 0 && $trial_length == 0 && $subscription_details['subscription_interval'] != $subscription_length )
						$subscription_details['initial_amount'] = $order_total_tax;
					elseif ( $sign_up_fee > 0 && $trial_length > 0 )
						$subscription_details['initial_amount'] = $order_total_tax;
					elseif ( $order_total_tax !== woocommerce_price( $subscription_details['recurring_amount'] ) ) // Applying initial payment only discount
						$subscription_details['initial_amount'] = $order_total_tax;
					else
						$subscription_details['initial_amount'] = '';

					$total_rows['tax']['value'] = WC_Subscriptions_Manager::get_subscription_price_string( $subscription_details );
				}
			}
		}

		return $total_rows;
	}

	/**
	 * Displays a few details about what happens to their subscription. Hooked
	 * to the thank you page. 
	 *
	 * @since 1.0
	 */
	public static function subscription_thank_you( $order_id ){

		if( self::order_contains_subscription( $order_id ) ) {
			$thank_you_message = '<p>' . __( 'Your subscription will be activated when payment clears.', WC_Subscriptions::$text_domain ) . '</p>';
			$thank_you_message = sprintf( __( '%sView the status of your subscription in %syour account%s.%s', WC_Subscriptions::$text_domain ), '<p>', '<a href="' . get_permalink( woocommerce_get_page_id( 'myaccount' ) ) . '">', '</a>','</p>' );
			echo apply_filters( 'woocommerce_subscriptions_thank_you_message', $thank_you_message, $order_id );
		}

	}

	/**
	 * Returns the number of failed payments for a given subscription.
	 * 
	 * @param $order WC_Order The WC_Order object of the order for which you want to determine the number of failed payments.
	 * @param product_id int The ID of the subscription product.
	 * @return string The key representing the given subscription.
	 * @since 1.0
	 */
	public static function get_failed_payment_count( $order, $product_id ) {

		$failed_payment_count = WC_Subscriptions_Manager::get_subscriptions_failed_payment_count( WC_Subscriptions_Manager::get_subscription_key( $order->id, $product_id ), $order->customer_user );

		return $failed_payment_count;
	}

	/**
	 * Returns the amount outstanding on a subscription product.
	 * 
	 * @param $order WC_Order The WC_Order object of the order for which you want to determine the number of failed payments.
	 * @param product_id int The ID of the subscription product.
	 * @return string The key representing the given subscription.
	 * @since 1.0
	 */
	public static function get_outstanding_balance( $order, $product_id ) {

		$failed_payment_count = self::get_failed_payment_count( $order, $product_id );

		$oustanding_balance = $failed_payment_count * self::get_recurring_total( $order, $product_id );

		return $oustanding_balance;
	}

	/**
	 * Output a hidden element in the order status of the orders list table to provide information about whether
	 * the order displayed in that row contains a subscription or not.
	 * 
	 * @param $column String The string of the current column.
	 * @since 1.1
	 */
	public static function add_contains_subscription_hidden_field( $column ) {
		global $post;

		if ( $column == 'order_status' )
			self::contains_subscription_hidden_field( $post->ID );
	}

	/**
	 * Output a hidden element in the order status of the orders list table to provide information about whether
	 * the order displayed in that row contains a subscription or not.
	 * 
	 * @param $column String The string of the current column.
	 * @since 1.1
	 */
	public static function contains_subscription_hidden_field( $order_id ) {

		$has_subscription = WC_Subscriptions_Order::order_contains_subscription( $order_id ) ? 'true' : 'false';

		echo '<input type="hidden" name="contains_subscription" value="' . $has_subscription . '">';
	}

	/**
	 * When an order is added or updated from the admin interface, check if a subscription product
	 * has been manually added to the order or the details of the subscription have been modified, 
	 * and create/update the subscription as required.
	 *
	 * Save subscription order meta items
	 *
	 * @param $post_id int The ID of the post which is the WC_Order object.
	 * @param $post Object The post object of the order.
	 * @since 1.1
	 */
	public static function pre_process_shop_order_meta( $post_id, $post ) {
		global $woocommerce, $wpdb;

		$order_contains_subscription = false;

		$order = new WC_Order( $post_id );

		$existing_product_ids = array();

		foreach ( $order->get_items() as $existing_item )
			$existing_product_ids[] = self::get_items_product_id( $existing_item );

		$product_ids = array();

		// WC <> 2.0 compatible posted product IDs
		if ( isset( $_POST['order_item_id'] ) )
			foreach ( $_POST['order_item_id'] as $order_item_id ) // WC 2.0+ has unique order item IDs and the product ID is a piece of meta
				$product_ids[$order_item_id] = woocommerce_get_order_item_meta( $order_item_id, '_product_id' );
		elseif ( isset( $_POST['item_id'] ) )
			$product_ids = $_POST['item_id']; // WC 1.x treated order item IDs as product IDs

		// Check if there are new subscription products to be added, or the order already has a subscription item
		foreach ( array_merge( $product_ids, $existing_product_ids ) as $order_item_id => $product_id ) {

			$is_existing_item = false;

			if ( in_array( $product_id, $existing_product_ids ) )
				$is_existing_item = true;

			// If this is a new item and it's a subscription product, we have a subscription
			if ( ! $is_existing_item && WC_Subscriptions_Product::is_subscription( $product_id ) )
				$order_contains_subscription = true;

			// If this is an existing item and it's a subscription item, we have a subscription
			if ( $is_existing_item && WC_Subscriptions_Order::is_item_subscription( $order, $product_id ) )
				$order_contains_subscription = true;
		}

		if ( ! $order_contains_subscription )
			return $post_id;

		// If the payment method is changing, make sure we have correct manual payment flag set
		$chosen_payment_method   = stripslashes( $_POST['_payment_method'] );
		$existing_payment_method = get_post_meta( $post_id, '_payment_method', true );

		if ( $chosen_payment_method != $existing_payment_method || empty( $chosen_payment_method ) ) {

			$payment_gateways = $woocommerce->payment_gateways->payment_gateways();

			if ( isset( $payment_gateways[$chosen_payment_method] ) && $payment_gateways[$chosen_payment_method]->supports( 'subscriptions' ) )
				$manual_renewal = 'false';
			else
				$manual_renewal = 'true';

			update_post_meta( $post_id, '_wcs_requires_manual_renewal', $manual_renewal );
		}

		// Make sure the recurring order totals are correct
		update_post_meta( $post_id, '_order_recurring_discount_cart', stripslashes( $_POST['_order_recurring_discount_cart'] ) );
		update_post_meta( $post_id, '_order_recurring_discount_total', stripslashes( $_POST['_order_recurring_discount_total'] ) );
		update_post_meta( $post_id, '_order_recurring_tax_total', stripslashes( $_POST['_order_recurring_tax_total'] ) );
		update_post_meta( $post_id, '_order_recurring_total', stripslashes( $_POST['_order_recurring_total'] ) );

		if ( isset( $_POST['recurring_order_taxes_id'] ) ) { // WC 2.0+

			$tax_keys = array( 'recurring_order_taxes_id', 'recurring_order_taxes_rate_id', 'recurring_order_taxes_amount', 'recurring_order_taxes_shipping_amount' );

			foreach( $tax_keys as $tax_key )
				$$tax_key = isset( $_POST[ $tax_key ] ) ? $_POST[ $tax_key ] : array();

			foreach( $recurring_order_taxes_id as $item_id ) {

				$item_id  = absint( $item_id );
				$rate_id  = absint( $recurring_order_taxes_rate_id[ $item_id ] );

				if ( $rate_id ) {
					$rate     = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id = %s", $rate_id ) );
					$label    = $rate->tax_rate_name ? $rate->tax_rate_name : $woocommerce->countries->tax_or_vat();
					$compound = $rate->tax_rate_compound ? 1 : 0;

					$code = array();

					$code[] = $rate->tax_rate_country;
					$code[] = $rate->tax_rate_state;
					$code[] = $rate->tax_rate_name ? $rate->tax_rate_name : 'TAX';
					$code[] = absint( $rate->tax_rate_priority );
					$code   = strtoupper( implode( '-', array_filter( $code ) ) );
				} else {
					$code  = '';
					$label = $woocommerce->countries->tax_or_vat();
				}

				$wpdb->update(
					$wpdb->prefix . "woocommerce_order_items",
					array( 'order_item_name' => woocommerce_clean( $code ) ),
					array( 'order_item_id' => $item_id ),
					array( '%s' ),
					array( '%d' )
				);

				woocommerce_update_order_item_meta( $item_id, 'rate_id', $rate_id );
				woocommerce_update_order_item_meta( $item_id, 'label', $label );
				woocommerce_update_order_item_meta( $item_id, 'compound', $compound );

				if ( isset( $recurring_order_taxes_amount[ $item_id ] ) )
					woocommerce_update_order_item_meta( $item_id, 'tax_amount', woocommerce_clean( $recurring_order_taxes_amount[ $item_id ] ) );

				if ( isset( $recurring_order_taxes_shipping_amount[ $item_id ] ) )
					woocommerce_update_order_item_meta( $item_id, 'shipping_tax_amount', woocommerce_clean( $recurring_order_taxes_shipping_amount[ $item_id ] ) );
			}

		} else { // WC 1.x

			if( ! isset( $_POST['_order_recurring_taxes'] ) )
				$_POST['_order_recurring_taxes'] = array();

			foreach ( $_POST['_order_recurring_taxes'] as $index => $tax_details )
				if ( ! isset( $tax_details['compound'] ) )
					$_POST['_order_recurring_taxes'][$index]['compound'] = 0;

			update_post_meta( $post_id, '_order_recurring_taxes', $_POST['_order_recurring_taxes'] );
		}

		// Check if all the subscription products on the order have associated subscriptions on the user's account, and if not, add a new one
		foreach ( $product_ids as $order_item_id => $product_id ) {

			$is_existing_item = false;

			if ( in_array( $product_id, $existing_product_ids ) )
				$is_existing_item = true;

			// If this is a new item and it's not a subscription product, ignore it
			if ( ! $is_existing_item && ! WC_Subscriptions_Product::is_subscription( $product_id ) )
				continue;

			// If this is an existing item and it's not a subscription, ignore it
			if ( $is_existing_item && ! WC_Subscriptions_Order::is_item_subscription( $order, $product_id ) )
				continue;

			$subscription_key = WC_Subscriptions_Manager::get_subscription_key( $post_id, $product_id );

			$subscription = array();

			// If order customer changed, move the subscription from the old customer's account to the new customer
			if ( ! empty( $order->customer_user ) && $order->customer_user != (int)$_POST['customer_user'] ) {

				$subscription = WC_Subscriptions_Manager::remove_users_subscription( $order->customer_user, $subscription_key );

				if ( ! empty( $subscription ) ) {

					$subscriptions = WC_Subscriptions_Manager::get_users_subscriptions( (int)$_POST['customer_user'] );

					$subscriptions[$subscription_key] = $subscription;

					WC_Subscriptions_Manager::update_users_subscriptions( (int)$_POST['customer_user'], $subscriptions );
				}
			}

			// In case it's a new order or the customer has changed
			$order->customer_user = $order->user_id = (int)$_POST['customer_user'];

			$subscription = WC_Subscriptions_Manager::get_users_subscription( $order->customer_user, $subscription_key );

			if ( empty( $subscription ) ) { // Add a new subscription

				// The order may not exist yet, so we need to set a few things ourselves
				if ( empty ( $order->order_key ) ) {
					$order->order_key = uniqid( 'order_' );
					add_post_meta( $post_id, '_order_key', $order->order_key, true );
				}

				if ( empty( $_POST['order_date'] ) )
					$start_date = gmdate( 'Y-m-d H:i:s' );
				else
					$start_date = get_gmt_from_date( $_POST['order_date'] . ' ' . (int) $_POST['order_date_hour'] . ':' . (int) $_POST['order_date_minute'] . ':00' );

				WC_Subscriptions_Manager::create_pending_subscription_for_order( $order, $product_id, array( 'start_date' => $start_date ) );

				// Add the subscription meta for this item to the order
				$functions_and_meta = array( 'get_period' => '_order_subscription_periods', 'get_interval' => '_order_subscription_intervals', 'get_length' => '_order_subscription_lengths' );

				foreach ( $functions_and_meta as $function_name => $meta_key ) {
					$subscription_meta = self::get_meta( $order, $meta_key, array() );
					$subscription_meta[$product_id] = WC_Subscriptions_Product::$function_name( $product_id );
					update_post_meta( $order->id, $meta_key, $subscription_meta );
				}

				// This works because process_shop_order_item_meta saves item meta to workaround a WC 1.x bug and in WC 2.0+ meta is added when the item is added via Ajax
				self::process_shop_order_item_meta( $post_id, $post );

				// If the order's existing status is something other than pending and the order status is not being changed, manually set the subscription's status (otherwise, it will be handled when WC transitions the order's status)
				if ( $order->status == $_POST['order_status'] && 'pending' != $order->status ) {
					switch( $order->status ) {
						case 'completed' :
						case 'processing' :
							WC_Subscriptions_Manager::activate_subscription( $order->customer_user, $subscription_key );
							break;
						case 'refunded' :
						case 'cancelled' :
							WC_Subscriptions_Manager::cancel_subscription( $order->customer_user, $subscription_key );
							break;
						case 'failed' :
							WC_Subscriptions_Manager::failed_subscription_signup( $order->customer_user, $subscription_key );
							break;
					}
				}
			}
		}

		// Determine whether we need to update any subscription dates for existing subscriptions (before the item meta is updated)
		if ( ! empty( $product_ids ) ) {

			$start_date = $_POST['order_date'] . ' ' . (int) $_POST['order_date_hour'] . ':' . (int) $_POST['order_date_minute'] . ':00';

			// Start date changed for an existing order
			if ( ! empty( $order->order_date ) && $order->order_date != $start_date ) {

				self::$requires_update['expiration_date']   = array_values( $product_ids );
				self::$requires_update['trial_expiration']  = array_values( $product_ids );
				self::$requires_update['next_billing_date'] = array_values( $product_ids );

			} elseif ( isset( $_POST['meta_key'] ) ) { // WC 2.0+

				$item_meta_keys  = ( isset( $_POST['meta_key'] ) ) ? $_POST['meta_key'] : array();
				$new_meta_values = ( isset( $_POST['meta_value'] ) ) ? $_POST['meta_value'] : array();

				foreach ( $item_meta_keys as $item_meta_id => $meta_key ) {

					$meta_data  = self::get_item_meta_data( $item_meta_id );
					$product_id = woocommerce_get_order_item_meta( $meta_data->order_item_id, '_product_id' );

					// Set flags to update payment dates if required
					switch( $meta_key ) {
						case '_subscription_period':
						case '_subscription_interval':
							if ( $new_meta_values[$item_meta_id] != $meta_data->meta_value ) {
								self::$requires_update['next_billing_date'][] = $product_id;
							}
							break;
						case '_subscription_trial_length':
						case '_subscription_trial_period':
							if ( $new_meta_values[$item_meta_id] != $meta_data->meta_value ) {
								self::$requires_update['expiration_date'][]   = $product_id;
								self::$requires_update['trial_expiration'][]  = $product_id;
								self::$requires_update['next_billing_date'][] = $product_id;
							}
							break;
						case '_subscription_length':
							if ( $new_meta_values[$item_meta_id] != $meta_data->meta_value ) {
								self::$requires_update['expiration_date'][]   = $product_id;
								self::$requires_update['next_billing_date'][] = $product_id;
							}
							break;
					}
				}

			} elseif ( isset( $_POST['meta_name'] ) ) { // WC 1.x

				$item_meta_names  = ( isset( $_POST['meta_name'] ) ) ? $_POST['meta_name'] : '';
				$item_meta_values = ( isset( $_POST['meta_value'] ) ) ? $_POST['meta_value'] : '';

				$item_id_count = count( $item_ids );

				for ( $i=0; $i < $item_id_count; $i++ ) {

					if ( ! isset( $item_ids[$i] ) || ! $item_ids[$i] )
						continue;
					elseif( ! in_array( $item_ids[$i], $existing_product_ids ) ) // New subscriptions throw a false positive
						continue;

					// Meta
					$item_meta = new WC_Order_Item_Meta();

					if ( isset( $item_meta_names[$i] ) && isset( $item_meta_values[$i] ) ) {
						$meta_names       = $item_meta_names[$i];
						$meta_values      = $item_meta_values[$i];
						$meta_names_count = count( $meta_names );

						for ( $ii = 0; $ii < $meta_names_count; $ii++ ) {
							$meta_name  = esc_attr( $meta_names[$ii] );
							$meta_value = esc_attr( $meta_values[$ii] );

							if ( ! isset( $meta_name ) || ! isset( $meta_value ) )
								continue;

							// Set flags to update payment dates if required
							switch( $meta_name ) {
								case '_subscription_period':
								case '_subscription_interval':
									if ( $meta_value != self::get_item_meta( $order, $meta_name, $item_ids[$i] ) ){
										self::$requires_update['next_billing_date'][] = $item_ids[$i];
									}
									break;
								case '_subscription_trial_length':
								case '_subscription_trial_period':
									if ( $meta_value != self::get_item_meta( $order, $meta_name, $item_ids[$i] ) ) {
										self::$requires_update['expiration_date'][] = $item_ids[$i];
										self::$requires_update['trial_expiration'][] = $item_ids[$i];
										self::$requires_update['next_billing_date'][] = $item_ids[$i];
									}
									break;
								case '_subscription_length':
									if ( $meta_value != self::get_item_meta( $order, $meta_name, $item_ids[$i] ) ) {
										self::$requires_update['expiration_date'][] = $item_ids[$i];
										self::$requires_update['next_billing_date'][] = $item_ids[$i];
									}
									break;
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Work around a bug in WooCommerce which ignores order item meta values of 0.
	 *
	 * Code in this function is identical to a section of the @see woocommerce_process_shop_order_meta() function, except
	 * that it doesn't include the bug which ignores item meta with a 0 value.
	 *
	 * @param $post_id int The ID of the post which is the WC_Order object.
	 * @param $post Object The post object of the order.
	 * @since 1.2.4
	 */
	public static function process_shop_order_item_meta( $post_id, $post ) {
		global $woocommerce;

		// Only needs to function on WC 1.x where the bug existed
		if (isset($_POST['item_id'])) :

			$order_items = array();

			$item_id			= $_POST['item_id'];
			$item_variation	= $_POST['item_variation'];
			$item_name 		= $_POST['item_name'];
			$item_quantity 	= $_POST['item_quantity'];

			$line_subtotal		= $_POST['line_subtotal'];
			$line_subtotal_tax	= $_POST['line_subtotal_tax'];

			$line_total 		= $_POST['line_total'];
			$line_tax		 	= $_POST['line_tax'];

			$item_meta_names 	= (isset($_POST['meta_name'])) ? $_POST['meta_name'] : '';
			$item_meta_values 	= (isset($_POST['meta_value'])) ? $_POST['meta_value'] : '';

			$item_tax_class	= $_POST['item_tax_class'];

			$item_id_count = sizeof( $item_id );

			for ($i=0; $i<$item_id_count; $i++) :

				if (!isset($item_id[$i]) || !$item_id[$i]) continue;
				if (!isset($item_name[$i])) continue;
				if (!isset($item_quantity[$i]) || $item_quantity[$i] < 1) continue;
				if (!isset($line_total[$i])) continue;
				if (!isset($line_tax[$i])) continue;

				// Meta
				$item_meta 		= new WC_Order_Item_Meta();

				if (isset($item_meta_names[$i]) && isset($item_meta_values[$i])) :
			 	$meta_names 	= $item_meta_names[$i];
			 	$meta_values 	= $item_meta_values[$i];
			 	$meta_names_count = sizeof( $meta_names );

			 	for ($ii=0; $ii<$meta_names_count; $ii++) :
			 		$meta_name 		= esc_attr( $meta_names[$ii] );
			 		$meta_value 	= esc_attr( $meta_values[$ii] );
			 		if ( isset( $meta_name ) && isset( $meta_value ) ) :
			 			$item_meta->add( $meta_name, $meta_value );
			 		endif;
			 	endfor;
				endif;

				// Add to array
				$order_items[] = apply_filters('update_order_item', array(
					'id' 				=> htmlspecialchars(stripslashes($item_id[$i])),
					'variation_id' 		=> (int) $item_variation[$i],
					'name' 				=> htmlspecialchars(stripslashes($item_name[$i])),
					'qty' 				=> (int) $item_quantity[$i],
					'line_total' 		=> rtrim(rtrim(number_format(woocommerce_clean($line_total[$i]), 4, '.', ''), '0'), '.'),
					'line_tax'			=> rtrim(rtrim(number_format(woocommerce_clean($line_tax[$i]), 4, '.', ''), '0'), '.'),
					'line_subtotal'		=> rtrim(rtrim(number_format(woocommerce_clean($line_subtotal[$i]), 4, '.', ''), '0'), '.'),
					'line_subtotal_tax' => rtrim(rtrim(number_format(woocommerce_clean($line_subtotal_tax[$i]), 4, '.', ''), '0'), '.'),
					'item_meta'			=> $item_meta->meta,
					'tax_class'			=> woocommerce_clean($item_tax_class[$i])
				));

			 endfor;

			update_post_meta( $post_id, '_order_items', $order_items );
		endif;

		// WC <> 2.0 compatible posted product IDs
		$product_ids = array();

		if ( isset( $_POST['order_item_id'] ) )
			foreach ( $_POST['order_item_id'] as $order_item_id ) // WC 2.0+ has unique order item IDs and the product ID is a piece of meta
				$product_ids[$order_item_id] = woocommerce_get_order_item_meta( $order_item_id, '_product_id' );
		elseif ( isset( $_POST['item_id'] ) )
			$product_ids = $_POST['item_id']; // WC 1.x treated order item IDs as product IDs

		// Now that meta has been updated, we can update the schedules (if there were any changes to schedule related meta)
		if ( ! empty( $product_ids ) ) {

			$user_id = (int)$_POST['customer_user'];

			foreach ( $product_ids as $product_id ) {
				$subscription_key = WC_Subscriptions_Manager::get_subscription_key( $post_id, $product_id );

				// Order is important here, expiration date takes into account trial expriation date and next payment date takes into account expiration date
				if ( in_array( $product_id, self::$requires_update['trial_expiration'] ) )
					WC_Subscriptions_Manager::set_trial_expiration_date( $subscription_key, $user_id );

				if ( in_array( $product_id, self::$requires_update['expiration_date'] ) )
					WC_Subscriptions_Manager::set_expiration_date( $subscription_key, $user_id );

				if ( in_array( $product_id, self::$requires_update['next_billing_date'] ) )
					WC_Subscriptions_Manager::set_next_payment_date( $subscription_key, $user_id );
			}
		}
	}

	/**
	 * Once payment is completed on an order, set a lock on payments until the next subscription payment period.
	 * 
	 * @param $user_id int The id of the user who purchased the subscription
	 * @param $subscription_key string A subscription key of the form created by @see self::get_subscription_key()
	 * @since 1.1.2
	 */
	public static function safeguard_scheduled_payments( $order_id ) {

		$order = new WC_Order( $order_id );

		if ( self::order_contains_subscription( $order ) ) {

			$subscription_key = WC_Subscriptions_Manager::get_subscription_key( $order_id );

			WC_Subscriptions_Manager::safeguard_scheduled_payments( $order->customer_user, $subscription_key );

		}
	}

	/**
	 * Records the initial payment against a subscription. 
	 *
	 * This function is called when a gateway calls @see WC_Order::payment_complete() and payment
	 * is completed on an order. It is also called when an orders status is changed to completed or
	 * processing for those gateways which never call @see WC_Order::payment_complete(), like the 
	 * core WooCommerce Cheque and Bank Transfer gateways.
	 *
	 * @param $order WC_Order | int A WC_Order object or ID of a WC_Order order.
	 * @since 1.1.2
	 */
	public static function maybe_record_order_payment( $order ) {

		if ( ! is_object( $order ) )
			$order = new WC_Order( $order );

		$subscriptions_in_order = self::get_recurring_items( $order );

		foreach ( $subscriptions_in_order as $subscription_item ) {

			$subscription_key = WC_Subscriptions_Manager::get_subscription_key( $order->id, self::get_items_product_id( $subscription_item ) );
			$subscription     = WC_Subscriptions_Manager::get_subscription( $subscription_key, $order->customer_user );

			// No payments have been recorded yet
			if ( empty( $subscription['completed_payments'] ) ) {

				// Don't duplicate orders
				remove_action( 'processed_subscription_payment', 'WC_Subscriptions_Renewal_Order::generate_paid_renewal_order', 10, 2 );

				WC_Subscriptions_Manager::process_subscription_payments_on_order( $order->id );

				WC_Subscriptions_Manager::safeguard_scheduled_payments( $order->customer_user, $subscription_key );

				// Make sure orders are still generated for other payments in the same request
				add_action( 'processed_subscription_payment', 'WC_Subscriptions_Renewal_Order::generate_paid_renewal_order', 10, 2 );
			}
		}
	}

	/* Order Price Getters */

	/**
	 * Returns the proportion of cart discount that is recurring for the product specified with $product_id
	 *
	 * @param $order WC_Order | int A WC_Order object or ID of a WC_Order order.
	 * @since 1.2
	 */
	public static function get_recurring_discount_cart( $order, $product_id = '' ) {
		return self::get_meta( $order, '_order_recurring_discount_cart', 0 );
	}

	/**
	 * Returns the proportion of total discount that is recurring for the product specified with $product_id
	 *
	 * @param $order WC_Order | int A WC_Order object or ID of a WC_Order order.
	 * @since 1.2
	 */
	public static function get_recurring_discount_total( $order, $product_id = '' ) {
		return self::get_meta( $order, '_order_recurring_discount_total', 0 );
	}

	/**
	 * Returns the amount of shipping tax that is recurring. As shipping only applies
	 * to recurring payments, and only 1 subscription can be purchased at a time, 
	 * this is equal to @see WC_Order::get_shipping()
	 *
	 * @param $order WC_Order | int A WC_Order object or ID of a WC_Order order.
	 * @since 1.2
	 */
	public static function get_recurring_shipping_tax_total( $order, $product_id = '' ) {
		return $order->get_shipping_tax();
	}

	/**
	 * Returns the recurring shipping price . As shipping only applies to recurring
	 * payments, and only 1 subscription can be purchased at a time, this is
	 * equal to @see WC_Order::get_shipping()
	 *
	 * @param $order WC_Order | int A WC_Order object or ID of a WC_Order order.
	 * @since 1.2
	 */
	public static function get_recurring_shipping_total( $order, $product_id = '' ) {
		return $order->get_shipping();
	}

	/**
	 * Returns an array of items in an order which are recurring along with their recurring totals.
	 *
	 * @param $order WC_Order | int A WC_Order object or ID of a WC_Order order.
	 * @since 1.2
	 */
	public static function get_recurring_items( $order ) {

		if ( ! is_object( $order ) )
			$order = new WC_Order( $order );

		$items = array();

		foreach ( $order->get_items() as $item_id => $item_details ) {

			if ( ! self::is_item_subscription( $order, $item_details ) )
				continue;

			$items[$item_id] = $item_details;

			foreach ( $item_details['item_meta'] as $meta_key => $meta_value ) {

				// WC 1.x
				if ( isset( $meta_value['meta_name'] ) ) {
					$meta_key   = $meta_value['meta_name'];
					$meta_value = $meta_value['meta_value'];
				} else {
					$meta_value = $meta_value[0];
				}

				switch ( $meta_key ) {
					case '_recurring_line_subtotal' :
						$items[$item_id]['line_subtotal'] = $meta_value;
						break;
					case '_recurring_line_subtotal_tax' :
						$items[$item_id]['line_subtotal_tax'] = $meta_value;
						break;
					case '_recurring_line_total' :
						$items[$item_id]['line_total'] = $meta_value;
						break;
					case '_recurring_line_tax' :
						$items[$item_id]['line_tax'] = $meta_value;
						break;
				}

			}

		}

		return $items;
	}

	/**
	 * Checks if a given order item is a subscription. A subscription with will have a piece of meta
	 * with the 'meta_name' starting with 'recurring' or 'subscription'.
	 *
	 * @param $order WC_Order | int A WC_Order object or ID of a WC_Order order.
	 * @param $item Array | int An array representing an order item or a product ID of an item in an order (not an order item ID)
	 * @since 1.2
	 */
	public static function is_item_subscription( $order, $item ) {

		if ( ! is_array( $item ) )
			$item = self::get_item_by_product_id( $order, $item );

		$item_is_subscription = false;

		if ( isset( $item['item_meta'] ) && is_array( $item['item_meta'] ) ) {
			foreach ( $item['item_meta'] as $item_key => $item_meta ) {

				// WC 1.x compatibility
				if ( isset( $item_meta['meta_name'] ) )
					$item_key = $item_meta['meta_name'];

				if ( 0 === strncmp( $item_key, '_subscription', strlen( '_subscription' ) ) || 0 === strncmp( $item_key, '_recurring', strlen( '_recurring' ) ) ) {
					$item_is_subscription = true;
					break;
				}
			}
		}

		return $item_is_subscription;
	}

	/**
	 * Returns an array of taxes on an order with their recurring totals.
	 *
	 * @param $order WC_Order | int A WC_Order object or ID of a WC_Order order.
	 * @since 1.2
	 */
	public static function get_recurring_taxes( $order ) {

		if ( function_exists( 'woocommerce_add_order_item_meta' ) ) { // WC 2.0+

			if ( ! is_object( $order ) )
				$order = new WC_Order( $order );

			$recurring_taxes = $order->get_items( 'recurring_tax' );

		} else {
			$recurring_taxes = self::get_meta( $order, '_order_recurring_taxes', array() );
		}

		return $recurring_taxes;
	}

	/**
	 * Returns the proportion of total tax on an order that is recurring for the product specified with $product_id
	 *
	 * @param $order WC_Order | int A WC_Order object or ID of a WC_Order order.
	 * @since 1.2
	 */
	public static function get_recurring_total_tax( $order, $product_id = '' ) {
		return self::get_meta( $order, '_order_recurring_tax_total', 0 );
	}

	/**
	 * Returns the proportion of total before tax on an order that is recurring for the product specified with $product_id
	 *
	 * @param $order WC_Order | int A WC_Order object or ID of a WC_Order order.
	 * @since 1.2
	 */
	public static function get_recurring_total_ex_tax( $order, $product_id = '' ) {
		return self::get_recurring_total( $order, $product_id ) - self::get_recurring_total_tax( $order, $product_id );
	}

	/**
	 * Returns the price per period for a subscription in an order.
	 * 
	 * @param $order mixed A WC_Order object or the ID of the order which the subscription was purchased in.
	 * @param $product_id int (optional) The post ID of the subscription WC_Product object purchased in the order. Defaults to the ID of the first product purchased in the order.
	 * @since 1.2
	 */
	public static function get_recurring_total( $order ) {
		return self::get_meta( $order, '_order_recurring_total', 0 );
	}

	/**
	 * Determines the proportion of the order total that a recurring amount accounts for and
	 * returns that proportion.
	 *
	 * If there is only one subscription in the order and no sign up fee for the subscription, 
	 * this function will return 1 (i.e. 100%).
	 *
	 * Shipping only applies to recurring amounts so is deducted from both the order total and 
	 * recurring amount so it does not distort the proportion.
	 *
	 * @param $order WC_Order | int A WC_Order object or ID of a WC_Order order.
	 * @return float The proportion of the order total which the recurring amount accounts for
	 * @since 1.2
	 */
	public static function get_recurring_total_proportion( $order, $product_id = '' ) {

		$order_shipping_total          = $order->get_shipping() + $order->get_shipping_tax();
		$order_total_sans_shipping     = $order->get_total() - $order_shipping_total;
		$recurring_total_sans_shipping = self::get_recurring_total( $order, $product_id ) - $order_shipping_total;

		return $recurring_total_sans_shipping / $order_total_sans_shipping;
	}

	/**
	 * Creates a string representation of the subscription period/term for each item in the cart
	 * 
	 * @param $order WC_Order A WC_Order object.
	 * @param mixed $deprecated Never used.
	 * @param mixed $deprecated Never used.
	 * @since 1.0
	 */
	public static function get_order_subscription_string( $order, $deprecated_price = '', $deprecated_sign_up_fee = '' ) {

		if ( ! empty( $deprecated_price ) || ! empty( $deprecated_sign_up_fee ) )
			_deprecated_argument( __CLASS__ . '::' . __FUNCTION__, '1.2' );

		$initial_amount = woocommerce_price( self::get_total_initial_payment( $order ) );

		$subscription_string = self::get_formatted_order_total( $initial_amount, $order );

		return $subscription_string;
	}


	/* Edit Order Ajax Handlers */

	/**
	 * Add subscription related order item meta when a subscription product is added as an item to an order via Ajax.
	 *
	 * @param item_id int An order_item_id as returned by the insert statement of @see woocommerce_add_order_item()
	 * @since 1.2.5
	 * @return void
	 */
	public static function prefill_order_item_meta( $item_id ) {

		// We only want to operate on ajax requests to add an order item
		if ( ! isset( $_POST['action'] ) || 'woocommerce_add_order_item' != $_POST['action'] || ! is_ajax() )
			return;

		$order_id   = $_POST['order_id'];
		$product_id = $_POST['item_to_add'];

		if ( $item_id && WC_Subscriptions_Product::is_subscription( $product_id ) ) {

			$recurring_amount = WC_Subscriptions_Product::get_price( $product_id );

			woocommerce_add_order_item_meta( $item_id, '_subscription_period', WC_Subscriptions_Product::get_period( $product_id ) );
			woocommerce_add_order_item_meta( $item_id, '_subscription_interval', WC_Subscriptions_Product::get_interval( $product_id ) );
			woocommerce_add_order_item_meta( $item_id, '_subscription_length', WC_Subscriptions_Product::get_length( $product_id ) );
			woocommerce_add_order_item_meta( $item_id, '_subscription_trial_length', WC_Subscriptions_Product::get_trial_length( $product_id ) );
			woocommerce_add_order_item_meta( $item_id, '_subscription_trial_period', WC_Subscriptions_Product::get_trial_period( $product_id ) );
			woocommerce_add_order_item_meta( $item_id, '_subscription_recurring_amount', $recurring_amount );
			woocommerce_add_order_item_meta( $item_id, '_subscription_sign_up_fee', WC_Subscriptions_Product::get_sign_up_fee( $product_id ) );

			woocommerce_add_order_item_meta( $item_id, '_recurring_line_total', $recurring_amount );
			woocommerce_add_order_item_meta( $item_id, '_recurring_line_tax', 0 );
			woocommerce_add_order_item_meta( $item_id, '_recurring_line_subtotal', $recurring_amount );
			woocommerce_add_order_item_meta( $item_id, '_recurring_line_subtotal_tax', 0 );
	 	}
	}

	/**
	 * Add subscription related order item meta via Ajax when a subscription product is added as an item to an order.
	 *
	 * This function is hooked to the 'wp_ajax_woocommerce_subscriptions_prefill_order_item_meta' hook which should only fire
	 * on WC 1.x (because the admin.js uses a selector which was changed in WC 2.0). For WC 2.0, order item meta is pre-filled
	 * via the 'woocommerce_new_order_item' hook in the new @see self::prefill_order_item().
	 *
	 * @since 1.2.4
	 * @return void
	 */
	public static function prefill_order_item_meta_old() {

		if ( function_exists( 'woocommerce_add_order_item_meta' ) ) // Meta added on the 'woocommerce_new_order_item' hook
			return;

		check_ajax_referer( WC_Subscriptions::$text_domain, 'security' );

		$product_id = trim( stripslashes( $_POST['item_to_add'] ) );

		$index = trim( stripslashes( $_POST['index'] ) );

		$response = array(
			'item_index'  => $index,
			'html'        => '',
			'line_totals' => array()
		);

		if ( WC_Subscriptions_Product::is_subscription( $product_id ) ) {

			$recurring_amount = WC_Subscriptions_Product::get_price( $product_id );

			$item_meta = new WC_Order_Item_Meta();

			// Subscription details so order state persists even when a product is changed
			$item_meta->add( '_subscription_period', WC_Subscriptions_Product::get_period( $product_id ) );
			$item_meta->add( '_subscription_interval', WC_Subscriptions_Product::get_interval( $product_id ) );
			$item_meta->add( '_subscription_length', WC_Subscriptions_Product::get_length( $product_id ) );
			$item_meta->add( '_subscription_trial_length', WC_Subscriptions_Product::get_trial_length( $product_id ) );
			$item_meta->add( '_subscription_trial_period', WC_Subscriptions_Product::get_trial_period( $product_id ) );
			$item_meta->add( '_subscription_recurring_amount', $recurring_amount );
			$item_meta->add( '_subscription_sign_up_fee', WC_Subscriptions_Product::get_sign_up_fee( $product_id ) );

			// Recurring totals need to be calcualted
			$item_meta->add( '_recurring_line_total', $recurring_amount );
			$item_meta->add( '_recurring_line_tax', 0 );
			$item_meta->add( '_recurring_line_subtotal', $recurring_amount );
			$item_meta->add( '_recurring_line_subtotal_tax', 0 );

			$item_meta = $item_meta->meta;

			if ( isset( $item_meta ) && is_array( $item_meta ) && sizeof( $item_meta ) > 0 ) {
				foreach ( $item_meta as $key => $meta ) {
					// Backwards compatibility
					if ( is_array( $meta ) && isset( $meta['meta_name'] ) ) {
						$meta_name  = $meta['meta_name'];
						$meta_value = $meta['meta_value'];
					} else {
						$meta_name  = $key;
						$meta_value = $meta;
					}

					$response['html'] .= '<tr><td><input type="text" name="meta_name['.$index.'][]" value="'.esc_attr( $meta_name ).'" /></td><td><input type="text" name="meta_value['.$index.'][]" value="'.esc_attr( $meta_value ).'" /></td><td width="1%"></td></tr>';
				}
			}

			// Calculate line totals for this item
			if ( $sign_up_fee > 0 ) {
				$line_subtotal = $sign_up_fee;
				$line_total    = $sign_up_fee;

				// If there is no free trial, add the recuring amounts
				if ( $trial_length == 0 ) {
					$line_subtotal += $recurring_amount;
					$line_total    += $recurring_amount;
				}

				$response['line_totals']['line_subtotal'] = esc_attr( number_format( (double) $line_subtotal, 2, '.', '' ) );
				$response['line_totals']['line_total']    = esc_attr( number_format( (double) $line_total, 2, '.', '' ) );

			}

		}

		echo json_encode( $response );

		die();
	}

	/**
	 * Calculate recurring line taxes when a store manager clicks the "Calc Line Tax" button on the "Edit Order" page.
	 *
	 * Based on the @see woocommerce_calc_line_taxes() function.
	 * @since 1.2.4
	 * @return void
	 */
	public static function calculate_recurring_line_taxes() {
		global $woocommerce, $wpdb;

		check_ajax_referer( WC_Subscriptions::$text_domain, 'security' );

		$tax = new WC_Tax();

		$taxes = $tax_rows = $item_taxes = $shipping_taxes = $return = array();

		$item_tax = 0;

		$order_id      = absint( $_POST['order_id'] );
		$country       = strtoupper( esc_attr( $_POST['country'] ) );
		$state         = strtoupper( esc_attr( $_POST['state'] ) );
		$postcode      = strtoupper( esc_attr( $_POST['postcode'] ) );
		$tax_class     = esc_attr( $_POST['tax_class'] );

		if ( isset( $_POST['city'] ) )
			$city = sanitize_title( esc_attr( $_POST['city'] ) );

		$shipping = $_POST['shipping'];

		$line_subtotal = isset( $_POST['line_subtotal'] ) ? esc_attr( $_POST['line_subtotal'] ) : 0;
		$line_total    = isset( $_POST['line_total'] ) ? esc_attr( $_POST['line_total'] ) : 0;

		$product_id = '';

		if ( isset( $_POST['order_item_id'] ) )
			$product_id = woocommerce_get_order_item_meta( $_POST['order_item_id'], '_product_id' );
		elseif ( isset( $_POST['product_id'] ) )
			$product_id = esc_attr( $_POST['product_id'] );

		if ( ! empty( $product_id ) && WC_Subscriptions_Product::is_subscription( $product_id ) ) {

			// Get product details
			$product         = WC_Subscriptions::get_product( $product_id );
			$item_tax_status = $product->get_tax_status();

			if ( $item_tax_status == 'taxable' ) {

				if ( function_exists( 'woocommerce_add_order_item_meta' ) ) { // WC 2.0+
					$tax_rates = $tax->find_rates( array(
						'country'   => $country,
						'state'     => $state,
						'postcode'  => $postcode,
						'city'      => $city,
						'tax_class' => $tax_class
					) );
				} else { // WC 1.x
					$tax_rates = $tax->find_rates( $country, $state, $postcode, $tax_class );
				}

				$line_subtotal_taxes = $tax->calc_tax( $line_subtotal, $tax_rates, false );
				$line_taxes = $tax->calc_tax( $line_total, $tax_rates, false );

				$line_subtotal_tax = $tax->round( array_sum( $line_subtotal_taxes ) );
				$line_tax = $tax->round( array_sum( $line_taxes ) );

				if ( $line_subtotal_tax < 0 )
					$line_subtotal_tax = 0;

				if ( $line_tax < 0 )
					$line_tax = 0;

				$return = array(
					'recurring_line_subtotal_tax' => $line_subtotal_tax,
					'recurring_line_tax'          => $line_tax
				);

				// Sum the item taxes
				foreach ( array_keys( $taxes + $line_taxes ) as $key )
					$taxes[ $key ] = ( isset( $line_taxes[ $key ] ) ? $line_taxes[ $key ] : 0 ) + ( isset( $taxes[ $key ] ) ? $taxes[ $key ] : 0 );
			}

			// WC 2.0+, create the tax row HTML
			if ( function_exists( 'woocommerce_add_order_item_meta' ) ) {

				// Now calculate shipping tax
				$matched_tax_rates = array();

				$tax_rates = $tax->find_rates( array(
					'country' 	=> $country,
					'state' 	=> $state,
					'postcode' 	=> $postcode,
					'city'		=> $city,
					'tax_class' => ''
				) );

				if ( $tax_rates )
					foreach ( $tax_rates as $key => $rate )
						if ( isset( $rate['shipping'] ) && $rate['shipping'] == 'yes' )
							$matched_tax_rates[ $key ] = $rate;

				$shipping_taxes = $tax->calc_shipping_tax( $shipping, $matched_tax_rates );
				$shipping_tax = $tax->round( array_sum( $shipping_taxes ) );

				// Remove old tax rows
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id IN ( SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d AND order_item_type = 'recurring_tax' )", $order_id ) );

				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d AND order_item_type = 'recurring_tax'", $order_id ) );

			 	// Get tax rates
				$rates = $wpdb->get_results( "SELECT tax_rate_id, tax_rate_country, tax_rate_state, tax_rate_name, tax_rate_priority FROM {$wpdb->prefix}woocommerce_tax_rates ORDER BY tax_rate_name" );

				$tax_codes = array();

				foreach( $rates as $rate ) {
					$code = array();

					$code[] = $rate->tax_rate_country;
					$code[] = $rate->tax_rate_state;
					$code[] = $rate->tax_rate_name ? sanitize_title( $rate->tax_rate_name ) : 'TAX';
					$code[] = absint( $rate->tax_rate_priority );

					$tax_codes[ $rate->tax_rate_id ] = strtoupper( implode( '-', array_filter( $code ) ) );
				}

				// Now merge to keep tax rows
				ob_start();

				foreach ( array_keys( $taxes + $shipping_taxes ) as $key ) {

					$item                        = array();
					$item['rate_id']             = $key;
					$item['name']                = $tax_codes[ $key ];
					$item['label']               = $tax->get_rate_label( $key );
					$item['compound']            = $tax->is_compound( $key ) ? 1 : 0;
					$item['tax_amount']          = $tax->round( isset( $taxes[ $key ] ) ? $taxes[ $key ] : 0 );
					$item['shipping_tax_amount'] = $tax->round( isset( $shipping_taxes[ $key ] ) ? $shipping_taxes[ $key ] : 0 );

					if ( ! $item['label'] )
						$item['label'] = $woocommerce->countries->tax_or_vat();

					// Add line item
					$item_id = woocommerce_add_order_item( $order_id, array(
						'order_item_name' => $item['name'],
						'order_item_type' => 'recurring_tax'
					) );

					// Add line item meta
					if ( $item_id ) {
						woocommerce_add_order_item_meta( $item_id, 'rate_id', $item['rate_id'] );
						woocommerce_add_order_item_meta( $item_id, 'label', $item['label'] );
						woocommerce_add_order_item_meta( $item_id, 'compound', $item['compound'] );
						woocommerce_add_order_item_meta( $item_id, 'tax_amount', $item['tax_amount'] );
						woocommerce_add_order_item_meta( $item_id, 'shipping_tax_amount', $item['shipping_tax_amount'] );
					}

					include( plugin_dir_path( WC_Subscriptions::$plugin_file ) . 'templates/admin/post-types/writepanels/order-tax-html.php' );
				}

				$return['tax_row_html'] = ob_get_clean();

			}

			echo json_encode( $return );

		}

		die();
	}


	/* Edit Order Page Content */

	/**
	 * Display recurring order totals on the "Edit Order" page.
	 *
	 * @param $post_id int The post ID of the shop_order post object.
	 * @since 1.2.4
	 * @return void
	 */
	public static function recurring_order_totals_meta_box_section( $post_id ) {
		global $woocommerce, $wpdb;

		$order = new WC_Order( $post_id );

		$display_none = ' style="display: none"';

		$contains_subscription = ( WC_Subscriptions_Order::order_contains_subscription( $order ) ) ? true : false;

		$chosen_gateway = WC_Subscriptions_Payment_Gateways::get_payment_gateway( get_post_meta( $post_id, '_payment_method', true ) );

		$manual_renewal = self::requires_manual_renewal( $post_id );

		$changes_supported = ( $chosen_gateway === false || $manual_renewal == 'true' || $chosen_gateway->supports( 'subscription_amount_changes' ) ) ? 'true' : 'false';
?>
	<p id="recurring_shipping_description"<?php if ( ! $contains_subscription ) echo $display_none; ?>>
		<?php _e( 'Shipping cost is included in the recurring total.', WC_Subscriptions::$text_domain ); ?>
	</p>
	<div class="clear"></div>
</div>
<div id="gateway_support"<?php if ( ! $contains_subscription ) echo $display_none; ?>>
	<input type="hidden" name="gateway_supports_subscription_changes" value="<?php echo $changes_supported; ?>">
	<div class="error"<?php if ( ! $contains_subscription || $changes_supported == 'true' ) echo $display_none; ?>>
		<p><?php printf( __( 'The %s payment gateway is used to charge automatic subscription payments for this order. This gateway <strong>does not</strong> support changing a subscription\'s details.', WC_Subscriptions::$text_domain ), get_post_meta( $post_id, '_payment_method_title', true ) ); ?></p>
		<p>
			<?php _e( 'It is strongly recommended you <strong>do not change</strong> any of the recurring totals or subscription item\'s details.', WC_Subscriptions::$text_domain ); ?>
			<a href="http://docs.woothemes.com/document/add-or-modify-a-subscription/#section-4"><?php _e( 'Learn More', WC_Subscriptions::$text_domain ); ?> &raquo;</a>
		</p>
	</div>
</div>
<div id="recurring_order_totals"<?php if ( ! $contains_subscription ) echo $display_none; ?>>
	<h3><?php _e( 'Recurring Totals', WC_Subscriptions::$text_domain); ?></h3>
	<div class="totals_group">
		<h4><?php _e( 'Recurring Discounts', WC_Subscriptions::$text_domain); ?> <a class="tips" data-tip="<?php _e( 'The discounts applied to each recurring payment charged in the future.', WC_Subscriptions::$text_domain ); ?>" href="#">[?]</a></h4>
		<ul class="totals">

			<li class="left">
				<label><?php _e( 'Cart Discount:', WC_Subscriptions::$text_domain ); ?></label>
				<input type="number" step="any" min="0" id="_order_recurring_discount_cart" name="_order_recurring_discount_cart" placeholder="0.00" value="<?php echo self::get_recurring_discount_cart( $order ); ?>" class="calculated" />
			</li>

			<li class="right">
				<label><?php _e( 'Order Discount:', WC_Subscriptions::$text_domain ); ?></label>
				<input type="number" step="any" min="0" id="_order_recurring_discount_total" name="_order_recurring_discount_total" placeholder="0.00" value="<?php echo self::get_recurring_discount_total( $order ); ?>" />
			</li>

		</ul>
		<div class="clear"></div>
	</div>
	<div class="totals_group tax_rows_group">
		<h4><?php _e( 'Recurring Tax Rows', WC_Subscriptions::$text_domain ); ?> <a class="tips" data-tip="<?php _e( 'These rows contain taxes included in each recurring amount for this subscription. This allows you to display multiple or compound taxes rather than a single total on future subscription renewal orders.', WC_Subscriptions::$text_domain ); ?>" href="#">[?]</a></h4>
		<div id="recurring_tax_rows">
			<?php
				$loop = 0;
				$taxes = self::get_recurring_taxes( $order );
				if ( is_array( $taxes ) && sizeof( $taxes ) > 0 ) :

					if ( function_exists( 'woocommerce_add_order_item_meta' ) ) { // WC 2.0+

						$rates = $wpdb->get_results( "SELECT tax_rate_id, tax_rate_country, tax_rate_state, tax_rate_name, tax_rate_priority FROM {$wpdb->prefix}woocommerce_tax_rates ORDER BY tax_rate_name" );

						$tax_codes = array();

						foreach( $rates as $rate ) {
							$code = array();

							$code[] = $rate->tax_rate_country;
							$code[] = $rate->tax_rate_state;
							$code[] = $rate->tax_rate_name ? sanitize_title( $rate->tax_rate_name ) : 'TAX';
							$code[] = absint( $rate->tax_rate_priority );

							$tax_codes[ $rate->tax_rate_id ] = strtoupper( implode( '-', array_filter( $code ) ) );
						}
					}

					foreach ( $taxes as $item_id => $item ) : ?>
						<?php if ( isset( $item['name'] ) ) : // WC 2.0+ ?>

							<?php include( plugin_dir_path( WC_Subscriptions::$plugin_file ) . 'templates/admin/post-types/writepanels/order-tax-html.php' ); ?>

						<?php else : // WC 1.x ?>
						<div class="tax_row">
							<p class="first">
								<label><?php _e( 'Recurring Tax Label:', WC_Subscriptions::$text_domain ); ?></label>
								<input type="text" name="_order_recurring_taxes[<?php echo $loop; ?>][label]" placeholder="<?php echo $woocommerce->countries->tax_or_vat(); ?>" value="<?php echo $item['label']; ?>" />
							</p>
							<p class="last">
								<label><?php _e( 'Compound:', WC_Subscriptions::$text_domain ); ?>
								<input type="checkbox" name="_order_recurring_taxes[<?php echo $loop; ?>][compound]" <?php checked( $item['compound'], 1 ); ?> /></label>
							</p>
							<p class="first">
								<label><?php _e( 'Recurring Cart Tax:', WC_Subscriptions::$text_domain ); ?></label>
								<input type="text" name="_order_recurring_taxes[<?php echo $loop; ?>][cart_tax]" placeholder="0.00" value="<?php echo $item['cart_tax']; ?>" />
							</p>
							<p class="last">
								<label><?php _e( 'Shipping Tax:', WC_Subscriptions::$text_domain ); ?></label>
								<input type="text" name="_order_recurring_taxes[<?php echo $loop; ?>][shipping_tax]" placeholder="0.00" value="<?php echo $item['shipping_tax']; ?>" />
							</p>
							<a href="#" class="delete_tax_row">&times;</a>
							<div class="clear"></div>
						</div>
						<?php endif; ?>
						<?php
						$loop++;
					endforeach;
				endif;
			?>
		</div>
		<h4 style="padding-bottom: 10px;"><a href="#" class="add_recurring_tax_row"><?php _e( '+ Add tax row', WC_Subscriptions::$text_domain ); ?></a></h4>
		<div class="clear"></div>
	</div>
	<div class="totals_group">
		<h4><?php _e( 'Recurring Totals', WC_Subscriptions::$text_domain ); ?> <a class="tips" data-tip="<?php _e( 'The total amounts charged for each future recurring payment.', WC_Subscriptions::$text_domain ); ?>" href="#">[?]</a></h4>
		<ul class="totals">

			<li class="left">
				<label><?php _e( 'Recurring Tax Total:', WC_Subscriptions::$text_domain ); ?></label>
				<input type="number" step="any" min="0" id="_order_recurring_tax_total" name="_order_recurring_tax_total" placeholder="0.00" value="<?php echo self::get_recurring_total_tax( $order ); ?>" class="calculated" />
			</li>

			<li class="right">
				<label><?php _e( 'Recurring Order Total:', WC_Subscriptions::$text_domain ); ?></label>
				<input type="number" step="any" min="0" id="_order_recurring_total" name="_order_recurring_total" placeholder="0.00" value="<?php echo self::get_recurring_total( $order ); ?>" class="calculated" />
			</li>

		</ul>
		<div class="clear"></div>
	</div>
<?php
	}

	/**
	 * Adds a line tax item from an order by ID. Hooked to
	 * an Ajax call from the "Edit Order" page and mirrors the
	 * @see woocommerce_add_line_tax() function.
	 *
	 * @return void
	 */
	public static function add_line_tax() {
		global $woocommerce, $wpdb;

		check_ajax_referer( WC_Subscriptions::$text_domain, 'security' );

		if ( function_exists( 'woocommerce_add_order_item_meta' ) ) {
			$order_id = absint( $_POST['order_id'] );
			$order    = new WC_Order( $order_id );

		 	// Get tax rates
			$rates = $wpdb->get_results( "SELECT tax_rate_id, tax_rate_country, tax_rate_state, tax_rate_name, tax_rate_priority FROM {$wpdb->prefix}woocommerce_tax_rates ORDER BY tax_rate_name" );

			$tax_codes = array();

			foreach( $rates as $rate ) {
				$code = array();

				$code[] = $rate->tax_rate_country;
				$code[] = $rate->tax_rate_state;
				$code[] = $rate->tax_rate_name ? sanitize_title( $rate->tax_rate_name ) : 'TAX';
				$code[] = absint( $rate->tax_rate_priority );

				$tax_codes[ $rate->tax_rate_id ] = strtoupper( implode( '-', array_filter( $code ) ) );
			}

			// Add line item
			$item_id = woocommerce_add_order_item( $order_id, array(
				'order_item_name' => '',
				'order_item_type' => 'recurring_tax'
			) );

			// Add line item meta
			if ( $item_id ) {
				woocommerce_add_order_item_meta( $item_id, 'rate_id', '' );
				woocommerce_add_order_item_meta( $item_id, 'label', '' );
				woocommerce_add_order_item_meta( $item_id, 'compound', '' );
				woocommerce_add_order_item_meta( $item_id, 'tax_amount', '' );
				woocommerce_add_order_item_meta( $item_id, 'shipping_tax_amount', '' );
			}

			include( plugin_dir_path( WC_Subscriptions::$plugin_file ) . 'templates/admin/post-types/writepanels/order-tax-html.php' );

		} else { // WC 1.x
			$size = absint( $_POST['size'] );
?>
			<div class="tax_row">
				<p class="first">
					<label><?php _e( 'Recurring Tax Label:', WC_Subscriptions::$text_domain ); ?></label>
					<input type="text" name="_order_recurring_taxes[<?php echo $size; ?>][label]" placeholder="<?php echo $woocommerce->countries->tax_or_vat(); ?>" value="" />
				</p>
				<p class="last">
					<label><?php _e( 'Compound:', WC_Subscriptions::$text_domain ); ?>
					<input type="checkbox" name="_order_recurring_taxes[<?php echo $size; ?>][compound]" /></label>
				</p>
				<p class="first">
					<label><?php _e( 'Recurring Cart Tax:', WC_Subscriptions::$text_domain ); ?></label>
					<input type="text" name="_order_recurring_taxes[<?php echo $size; ?>][cart_tax]" placeholder="0.00" value="" />
				</p>
				<p class="last">
					<label><?php _e( 'Shipping Tax:', WC_Subscriptions::$text_domain ); ?></label>
					<input type="text" name="_order_recurring_taxes[<?php echo $size; ?>][shipping_tax]" placeholder="0.00" value="" />
				</p>
				<a href="#" class="delete_tax_row">&times;</a>
				<div class="clear"></div>
			</div>
<?php
		}

		die();
	}

	/**
	 * Removes a line tax item from an order by ID. Hooked to
	 * an Ajax call from the "Edit Order" page and mirrors the
	 * @see woocommerce_remove_line_tax() function.
	 *
	 * @return void
	 */
	public static function remove_line_tax() {

		check_ajax_referer( WC_Subscriptions::$text_domain, 'security' );

		$tax_row_id = absint( $_POST['tax_row_id'] );

		woocommerce_delete_order_item( $tax_row_id );

		die();
	}

	/**
	 * Checks if an order contains an in active subscription and if it does, denies download acces
	 * to files purchased on the order.
	 *
	 * @return bool False if the order contains a subscription that has expired or is cancelled/on-hold, otherwise, the original value of $download_permitted
	 * @since 1.3
	 */
	public static function is_download_permitted( $download_permitted, $order ) {

		if ( self::order_contains_subscription( $order ) ) {

			foreach ( $order->get_items() as $order_item ) {

				$subscription_key = WC_Subscriptions_Manager::get_subscription_key( $order->id, self::get_items_product_id( $order_item ) );
				$subscription     = WC_Subscriptions_Manager::get_users_subscription( $order->customer_user, $subscription_key );

				if ( ! isset( $subscription['status'] ) || 'active' !== $subscription['status'] ) {
					$download_permitted = false;
					break;
				}
			}

		}

		return $download_permitted;
	}

	/* Deprecated Functions */

	/**
	 * Returned the recurring amount for a subscription in an order.
	 * 
	 * @deprecated 1.2
	 * @since 1.0
	 */
	public static function get_price_per_period( $order, $product_id = '' ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2', __CLASS__ . '::get_recurring_total( $order, $product_id )' );
		return self::get_recurring_total( $order, $product_id );
	}

	/**
	 * Creates a new order for renewing a subscription product based on the details of a previous order.
	 *
	 * @param $order WC_Order | int The WC_Order object or ID of the order for which the a new order should be created.
	 * @param $product_id string The ID of the subscription product in the order which needs to be added to the new order.
	 * @param $new_order_role string A flag to indicate whether the new order should become the master order for the subscription. Accepts either 'parent' or 'child'. Defaults to 'parent' - replace the existing order.
	 * @deprecated 1.2
	 * @since 1.0
	 */
	public static function generate_renewal_order( $original_order, $product_id, $new_order_role = 'parent' ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2', 'WC_Subscriptions_Renewal_Order::generate_renewal_order( $original_order, $product_id, array( "new_order_role" => $new_order_role ) )' );
		return WC_Subscriptions_Renewal_Order::generate_renewal_order( $original_order, $product_id, array( 'new_order_role' => $new_order_role ) );
	}

	/**
	 * Hooks to the renewal order created action to determine if the order should be emailed to the customer. 
	 *
	 * @param $order WC_Order | int The WC_Order object or ID of a WC_Order order.
	 * @deprecated 1.2
	 * @since 1.0
	 */
	public static function maybe_send_customer_renewal_order_email( $order ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2', 'WC_Subscriptions_Renewal_Order::maybe_send_customer_renewal_order_email( $order )' );
		WC_Subscriptions_Renewal_Order::maybe_send_customer_renewal_order_email( $order );
	}

	/**
	 * Processing Order
	 * 
	 * @param $order WC_Order | int The WC_Order object or ID of a WC_Order order.
	 * @deprecated 1.2
	 * @since 1.0
	 */
	public static function send_customer_renewal_order_email( $order ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2', 'WC_Subscriptions_Renewal_Order::send_customer_renewal_order_email( $order )' );
		WC_Subscriptions_Renewal_Order::send_customer_renewal_order_email( $order );
	}

	/**
	 * Check if a given order is a subscription renewal order
	 * 
	 * @param $order WC_Order | int The WC_Order object or ID of a WC_Order order.
	 * @deprecated 1.2
	 * @since 1.0
	 */
	public static function is_renewal( $order ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2', 'WC_Subscriptions_Renewal_Order::is_renewal( $order )' );
		return WC_Subscriptions_Renewal_Order::is_renewal( $order );
	}

	/**
	 * Once payment is completed on an order, record the payment against the subscription automatically so that
	 * payment gateway extension developers don't have to do this.
	 * 
	 * @param $order_id int The id of the order to record payment against
	 * @deprecated 1.2
	 * @since 1.1.2
	 */
	public static function record_order_payment( $order_id ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2', __CLASS__ . '::maybe_record_order_payment( $order_id )' );
		return self::maybe_record_order_payment( $order_id );
	}

	/**
	 * Checks an order item to see if it is a subscription. The item needs to exist and have been a subscription
	 * product at the time of purchase for the function to return true.
	 *
	 * @param $order mixed A WC_Order object or the ID of the order which the subscription was purchased in.
	 * @param $product_id int The ID of a WC_Product object purchased in the order.
	 * @return bool True if the order contains a subscription, otherwise false.
	 * @deprecated 1.2.4
	 */
	public static function is_item_a_subscription( $order, $product_id ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2.4', __CLASS__ . '::is_item_subscription( $order, $product_id )' );
		return self::is_item_subscription( $order, $product_id );
	}

	/**
	 * Deprecated due to change of order item ID/API in WC 2.0.
	 *
	 * @param $order WC_Order | int The WC_Order object or ID of the order for which the meta should be sought. 
	 * @param $item_id int The product/post ID of a subscription. Option - if no product id is provided, the first item's meta will be returned
	 * @since 1.2
	 * @deprecated 1.2.5
	 */
	public static function get_item( $order, $product_id = '' ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2.5', __CLASS__ . '::get_item_by_product_id( $order, $product_id )' );
		return self::get_item_by_product_id( $order, $product_id );
	}

}

WC_Subscriptions_Order::init();
