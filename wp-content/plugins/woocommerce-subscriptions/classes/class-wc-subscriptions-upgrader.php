<?php
/**
 * A timeout resistant, single-serve upgrader for WC Subscriptions.
 *
 * This class is used to make all reasonable attempts to neatly upgrade data between versions of Subscriptions.
 *
 * For example, the subscription meta data associated with an order significantly changed between 1.1.n and 1.2.
 * It was imperative the data be upgraded to the new schema without hassle. A hassle could easily occur if 100,000
 * orders were being modified - memory exhaustion, script time out etc.
 *
 * @package		WooCommerce Subscriptions
 * @subpackage	WC_Subscriptions_Checkout
 * @category	Class
 * @author		Brent Shepherd
 * @since		1.2
 */
class WC_Subscriptions_Upgrader {

	private static $active_version;

	private static $updated_to_wc_2_0;

	private static $is_wc_version_2 = false;

	/**
	 * Hooks upgrade function to init.
	 *
	 * @since 1.2
	 */
	public static function init(){

		self::$active_version = get_option( WC_Subscriptions_Admin::$option_prefix . '_active_version', '0' );

		if ( isset( $_GET['wcs_upgrade_step'] ) || ( version_compare( self::$active_version, WC_Subscriptions::$version, '<' ) && 'true' != get_transient( 'wc_subscriptions_is_upgrading' ) ) ) {

			// If an admin hits the front of the site, run the updates, otherwise, run it as soon as someone hits the backend
			if ( current_user_can( 'update_plugins' ) )
				add_action( 'init', __CLASS__ . '::upgrade', 1 );
			else
				add_action( 'admin_init', __CLASS__ . '::upgrade', 1 );

		}

		// Maybe upgrade to WC 2.0 data structure (after WC has run it's upgrader)
		self::$is_wc_version_2   = version_compare( get_option( 'woocommerce_db_version' ), '2.0', '>=' );
		self::$updated_to_wc_2_0 = get_option( 'wcs_updated_to_wc_2_0', 'false' );

		if ( self::$is_wc_version_2 && 'true' != self::$updated_to_wc_2_0 ) {

			if ( current_user_can( 'update_plugins' ) )
				add_action( 'init', __CLASS__ . '::upgrade_to_latest_wc', 2 );
			else
				add_action( 'admin_init', __CLASS__ . '::upgrade_to_latest_wc', 2 );
		}

	}

	/**
	 * Checks which upgrades need to run and calls the necessary functions for that upgrade.
	 *
	 * @since 1.2
	 */
	public static function upgrade(){
		global $wpdb;

		@set_time_limit( 600 );

		// Update meta keys for 1.1 to 1.1.1 multisite changes
		if ( version_compare( self::$active_version, '1.1.1', '<' ) )
			$wpdb->update( $wpdb->usermeta, array( 'meta_key' => $wpdb->get_blog_prefix() . WC_Subscriptions_Manager::$users_meta_key ), array( 'meta_key' => WC_Subscriptions_Manager::$users_meta_key ) );

		// Fix any products that were incorrectly added as a subscription for a user in 1.1.2
		if ( '0' != self::$active_version && version_compare( self::$active_version, '1.1.3', '<' ) )
			self::upgrade_to_version_1_1_3();

		// Upgrade order and subscription meta data to new format
		if ( '0' != self::$active_version && version_compare( self::$active_version, '1.2', '<' ) )
			self::upgrade_to_version_1_2();

		// Fix renewal order dates & remove duplicate orders
		if ( '0' != self::$active_version && version_compare( self::$active_version, '1.2.1', '<' ) )
			self::upgrade_to_version_1_2_1();

		// Fix paypal renewal orders
		if ( '0' != self::$active_version && version_compare( self::$active_version, '1.2.2', '<' ) )
			self::upgrade_to_version_1_2_2();

		// Add Variable Subscription product type term
		if ( version_compare( self::$active_version, '1.2.5', '<=' ) )
			self::upgrade_to_version_1_3();

		// Keep track of site url to prevent duplicate payments from staging sites
		if ( version_compare( self::$active_version, '1.3.8', '<=' ) )
			update_option( 'wc_subscriptions_siteurl', get_option( 'siteurl' ) );

		self::upgrade_complete();
	}

	/**
	 * Runs necessary database updates for working with WooCommerce updates. This function should be called
	 * immediately after WooCommerce has run it's own updates.
	 *
	 * @since 1.2.5
	 */
	public static function upgrade_to_latest_wc() {
		global $wpdb;

		// Update recurring tax structure to 2.0 format - item meta
		if ( 'true' != self::$updated_to_wc_2_0 ) {

			$upgraded_orders = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_order_recurring_taxes_old'" );

			$query = "SELECT * FROM {$wpdb->postmeta} WHERE meta_key = '_order_recurring_taxes'";

			if ( ! empty( $upgraded_orders ) )
				$query .= "AND post_id NOT IN (" . implode( ',', $upgraded_orders ) . ")";

			$order_tax_rows = $wpdb->get_results( $query );

			foreach ( $order_tax_rows as $order_tax_row ) {

				$order_taxes = (array) maybe_unserialize( $order_tax_row->meta_value );

				if ( $order_taxes ) {
					foreach( $order_taxes as $order_tax ) {

						if ( ! isset( $order_tax['label'] ) || ! isset( $order_tax['cart_tax'] ) || ! isset( $order_tax['shipping_tax'] ) )
							continue;

						$item_id = woocommerce_add_order_item( $order_tax_row->post_id, array(
							'order_item_name' => $order_tax['label'],
							'order_item_type' => 'recurring_tax'
						) );

						// Add line item meta
						if ( $item_id ) {
							woocommerce_add_order_item_meta( $item_id, 'compound', absint( isset( $order_tax['compound'] ) ? $order_tax['compound'] : 0 ) );
							woocommerce_add_order_item_meta( $item_id, 'tax_amount', woocommerce_clean( $order_tax['cart_tax'] ) );
							woocommerce_add_order_item_meta( $item_id, 'shipping_tax_amount', woocommerce_clean( $order_tax['shipping_tax'] ) );
						}

						// Delete from DB (rename)
						$wpdb->query( $wpdb->prepare( "
							UPDATE {$wpdb->postmeta}
							SET meta_key = '_order_recurring_taxes_old'
							WHERE meta_key = '_order_recurring_taxes'
							AND post_id = %d
						", $order_tax_row->post_id ) );

					}
				}
			}

			// Maybe add the "Variable Subscriptions" product type if it wasn't added in the v1.3 upgrade
			if ( ! get_term_by( 'slug', 'variable-subscription', 'product_type' ) )
				wp_insert_term( __( 'Variable Subscription', WC_Subscriptions::$text_domain ), 'product_type' );

			update_option( 'wcs_updated_to_wc_2_0', 'true' );
		}

	}

	/**
	 * When an upgrade is complete, set the active version, delete the transient locking upgrade and fire a hook.
	 *
	 * @since 1.2
	 */
	public static function upgrade_complete() {
		// Set the new version now that all upgrade routines have completed
		update_option( WC_Subscriptions_Admin::$option_prefix . '_active_version', WC_Subscriptions::$version );

		do_action( 'woocommerce_subscriptions_upgraded', WC_Subscriptions::$version );
	}

	/**
	 * A bug in 1.1.2 was adding products to a users account as a subscription. This fixes that.
	 *
	 * @since 1.2
	 */
	private static function upgrade_to_version_1_1_3() {
		foreach ( get_users() as $user ) {
			$users_subscriptions = WC_Subscriptions_Manager::get_users_subscriptions( $user->ID );
			foreach ( $users_subscriptions as $subscription_key => $subscription_details )
				if ( ! isset ( $subscription_details['order_id'] ) )
					WC_Subscriptions_Manager::remove_users_subscription( $user->ID, $subscription_key );
		}
	}

	/**
	 * Version 1.2 needs to use a 2 step upgrade process to safely upgrade the database.
	 *
	 * @since 1.2
	 */
	private static function upgrade_to_version_1_2() {

		$_GET['wcs_upgrade_step'] = ( ! isset( $_GET['wcs_upgrade_step'] ) ) ? 0 : $_GET['wcs_upgrade_step'];

		switch ( (int)$_GET['wcs_upgrade_step'] ) {
			case 1:
				self::display_database_upgrade_helper();
				break;
			case 2:
				// Run the upgrade
				self::upgrade_database();
				if ( isset( $_GET['wcs_create_orders'] ) )
					self::generate_renewal_orders();
				wp_safe_redirect( admin_url( 'admin.php?wcs_upgrade_step=3' ) );
				break;
			case 3:
				update_option( WC_Subscriptions_Admin::$option_prefix . '_active_version', '1.2' );
				self::display_database_upgrade_helper();
				break;
			case 0:
			default:
				wp_safe_redirect( admin_url( 'admin.php?wcs_upgrade_step=1' ) );
				break;
		}

		exit();
	}

	/**
	 * Set renewal order dates generated by 1.2 upgrade to be in site time & remove any renewal orders which were a
	 * duplicate of the initial order.
	 *
	 * @since 1.2.1
	 */
	private static function upgrade_to_version_1_2_1() {
		global $wpdb;

		// Get all the orders that existed at the time of the 1.2 upgrade
		$orders_to_upgrade = get_option( 'wcs_1_2_upgraded_order_ids', array() );
		$upgraded_orders   = get_option( 'wcs_1_2_1_upgraded_order_ids', array() );
		$orders_to_upgrade = array_diff( $orders_to_upgrade, $upgraded_orders );

		foreach ( $orders_to_upgrade as $order_id ) {

			if ( WC_Subscriptions_Order::order_contains_subscription( $order_id ) ) {

				// More efficient than creating a WC_Order object
				$post = get_post( $order_id );

				// Trash any renewal orders created which are a duplicate of the initial order
				$return = $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->posts WHERE post_date_gmt = %s AND post_parent = %d AND post_type = 'shop_order'", $post->post_date_gmt, $order_id ) );

				// Make sure each generated renewal order's date is in site time
				foreach ( WC_Subscriptions_Renewal_Order::get_renewal_orders( $order_id ) as $renewal_order_id ) {

					$renewal_post = get_post( $renewal_order_id );

					$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET post_date = %s WHERE post_date_gmt = %s AND ID = %d AND post_type = 'shop_order'", get_date_from_gmt( $renewal_post->post_date_gmt ), $renewal_post->post_date_gmt, $renewal_order_id ) );

				}

			}

			// Regardless of whether the order contains a subscription or not we don't want to upgrade it again
			$upgraded_orders[] = $order_id;

			update_option( 'wcs_1_2_1_upgraded_order_ids', $upgraded_orders );

		}
	}

	/**
	 * Set renewal order dates generated by 1.2 upgrade to be in site time & remove any renewal orders which were a
	 * duplicate of the initial order.
	 *
	 * @since 1.2.2
	 */
	private static function upgrade_to_version_1_2_2() {
		global $wpdb;

		// Get all the original renewal order IDs for renewal orders newer than the 1.2 release date of 2012-11-08 13:47:43
		$renewal_orders = $wpdb->get_results(
			"SELECT post_parent, ID, post_date_gmt
			 FROM $wpdb->posts
			 WHERE post_parent != 0
				AND DATE(post_date_gmt) > DATE('2012-11-08 00:00:00')
				AND post_type = 'shop_order'",
			'OBJECT_K' // Uses post_parent as the array key and only returns the first renewal order
		);

		// Get all the original renewal order IDs and their dates
		$parent_orders = $wpdb->get_results(
			"SELECT ID, post_date_gmt
			 FROM $wpdb->posts
			 WHERE post_parent = 0
				AND DATE(post_date_gmt) > DATE('2012-11-08 00:00:00')
				AND post_type = 'shop_order'"
		);

		foreach ( $parent_orders as $parent_order ) {

			// Ignore any parent orders with no renewal orders
			if ( ! isset( $renewal_orders[$parent_order->ID] ) )
				continue;

			$time_diff = strtotime( $parent_order->post_date_gmt ) - strtotime( $renewal_orders[$parent_order->ID]->post_date_gmt );

			// If a renewal order is within one hour of the original order, it's a duplicate
			if ( absint( $time_diff ) < ( 60 * 60 ) )
				$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->posts WHERE ID = %s", $renewal_orders[$parent_order->ID]->ID ) );

		}

	}

	/**
	 * Upgrade cron lock values to be options rather than transients to work around potential early deletion by W3TC
	 * and other caching plugins. Also add the Variable Subscription product type (if it doesn't exist).
	 *
	 * @since 1.3
	 */
	private static function upgrade_to_version_1_3() {
		global $wpdb;

		$current_wc_version = get_option( 'woocommerce_db_version' );

		// Maybe add the "Variable Subscriptions" product type if the site is already running WC 2.0+
		if ( version_compare( $current_wc_version, '2.0', '>=' ) && ! get_term_by( 'slug', 'variable-subscription', 'product_type' ) )
			wp_insert_term( __( 'Variable Subscription', WC_Subscriptions::$text_domain ), 'product_type' );

		// Change transient timeout entries to be a vanilla option
		$wpdb->query( " UPDATE $wpdb->options
						SET option_name = TRIM(LEADING '_transient_timeout_' FROM option_name)
						WHERE option_name LIKE '_transient_timeout_wcs_blocker_%'" );

		// Change transient keys from the < 1.1.5 format to new format
		$wpdb->query( " UPDATE $wpdb->options
						SET option_name = CONCAT('wcs_blocker_', TRIM(LEADING '_transient_timeout_block_scheduled_subscription_payments_' FROM option_name))
						WHERE option_name LIKE '_transient_timeout_block_scheduled_subscription_payments_%'" );

		// Delete old transient values
		$wpdb->query( " DELETE FROM $wpdb->options
						WHERE option_name LIKE '_transient_wcs_blocker_%'
						OR option_name LIKE '_transient_block_scheduled_subscription_payments_%'" );

	}

	/**
	 * Version 1.2 introduced a massive change to the order meta data schema. This function goes
	 * through and upgrades the existing data on all orders to the new schema.
	 *
	 * The upgrade process is timeout safe as it keeps a record of the orders upgraded and only
	 * deletes this record once all orders have been upgraded successfully. If operating on a huge
	 * number of orders and the upgrade process times out, only the orders not already upgraded
	 * will be upgraded in future requests that trigger this function.
	 *
	 * @since 1.2
	 */
	private static function upgrade_database() {
		global $wpdb;

		set_transient( 'wc_subscriptions_is_upgrading', 'true', 60 * 2 );

		// Get IDs only and use a direct DB query for efficiency
		$orders_to_upgrade = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type = 'shop_order' AND post_parent = 0" );

		$upgraded_orders = get_option( 'wcs_1_2_upgraded_order_ids', array() );

		// Transition deprecated subscription status if we aren't in the middle of updating orders
		if ( empty( $upgraded_orders ) ) {
			$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->usermeta SET meta_value = replace( meta_value, 's:9:\"suspended\"', 's:7:\"on-hold\"' ) WHERE meta_key LIKE %s", '%_' . WC_Subscriptions_Manager::$users_meta_key ) );
			$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->usermeta SET meta_value = replace( meta_value, 's:6:\"failed\"', 's:9:\"cancelled\"' ) WHERE meta_key LIKE %s", '%_' . WC_Subscriptions_Manager::$users_meta_key ) );
		}

		$orders_to_upgrade = array_diff( $orders_to_upgrade, $upgraded_orders );

		// Upgrade all _sign_up_{field} order meta to new order data format
		foreach ( $orders_to_upgrade as $order_id ) {

			$order = new WC_Order( $order_id );

			// Manually check if a product in an order is a subscription, we can't use WC_Subscriptions_Order::order_contains_subscription( $order ) because it relies on the new data structure
			$contains_subscription = false;
			foreach ( $order->get_items() as $order_item ) {
				if ( WC_Subscriptions_Product::is_subscription( WC_Subscriptions_Order::get_items_product_id( $order_item ) ) ) {
					$contains_subscription = true;
					break;
				}
			}

			if ( ! $contains_subscription )
				continue;

			$trial_lengths = WC_Subscriptions_Order::get_meta( $order, '_order_subscription_trial_lengths', array() );
			$trial_length = array_pop( $trial_lengths );

			$has_trial = ( ! empty( $trial_length ) && $trial_length > 0 ) ? true : false ;

			$sign_up_fee_total = WC_Subscriptions_Order::get_meta( $order, '_sign_up_fee_total', 0 );

			// Create recurring_* meta data from existing cart totals

			$cart_discount = $order->get_cart_discount();
			update_post_meta( $order_id, '_order_recurring_discount_cart', $cart_discount );

			$order_discount = $order->get_order_discount();
			update_post_meta( $order_id, '_order_recurring_discount_total', $order_discount );

			$order_shipping_tax = get_post_meta( $order_id, '_order_shipping_tax', true );
			update_post_meta( $order_id, '_order_recurring_shipping_tax_total', $order_shipping_tax );

			$order_tax = get_post_meta( $order_id, '_order_tax', true ); // $order->get_total_tax() includes shipping tax
			update_post_meta( $order_id, '_order_recurring_tax_total', $order_tax );

			$order_total = $order->get_total();
			update_post_meta( $order_id, '_order_recurring_total', $order_total );

			// Set order totals to include sign up fee fields, if there was a sign up fee on the order and a trial period (other wise, the recurring totals are correct)
			if ( $sign_up_fee_total > 0 ) {

				// Order totals need to be changed to be equal to sign up fee totals
				if ( $has_trial ) {

					$cart_discount  = WC_Subscriptions_Order::get_meta( $order, '_sign_up_fee_discount_cart', 0 );
					$order_discount = WC_Subscriptions_Order::get_meta( $order, '_sign_up_fee_discount_total', 0 );
					$order_tax      = WC_Subscriptions_Order::get_meta( $order, '_sign_up_fee_tax_total', 0 );
					$order_total    = $sign_up_fee_total;

				} else { // No trial, sign up fees need to be added to order totals

					$cart_discount  += WC_Subscriptions_Order::get_meta( $order, '_sign_up_fee_discount_cart', 0 );
					$order_discount += WC_Subscriptions_Order::get_meta( $order, '_sign_up_fee_discount_total', 0 );
					$order_tax      += WC_Subscriptions_Order::get_meta( $order, '_sign_up_fee_tax_total', 0 );
					$order_total    += $sign_up_fee_total;

				}

				update_post_meta( $order_id, '_order_total', $order_total );
				update_post_meta( $order_id, '_cart_discount', $cart_discount );
				update_post_meta( $order_id, '_order_discount', $order_discount );
				update_post_meta( $order_id, '_order_tax', $order_tax );

			}

			// Make sure we get order taxes in WC 1.x format
			if ( false == self::$is_wc_version_2 ) {

				$order_taxes = $order->get_taxes();

			} else {

				$order_tax_row = $wpdb->get_row( $wpdb->prepare( "
					SELECT * FROM {$wpdb->postmeta}
					WHERE meta_key = '_order_taxes_old'
					AND post_id = %s
					", $order_id )
				);

				$order_taxes = (array) maybe_unserialize( $order_tax_row->meta_value );
			}

			// Set recurring taxes to order taxes, if using WC 2.0, this will be migrated to the new format in @see self::upgrade_to_latest_wc()
			update_post_meta( $order_id, '_order_recurring_taxes', $order_taxes );

			$sign_up_fee_taxes = WC_Subscriptions_Order::get_meta( $order, '_sign_up_fee_taxes', array() );

			// Update order taxes to include sign up fee taxes
			foreach ( $sign_up_fee_taxes as $index => $sign_up_tax ) {

				if ( $has_trial && $sign_up_fee_total > 0 ) { // Order taxes need to be set to the same as the sign up fee taxes

					if ( isset( $sign_up_tax['cart_tax'] ) && $sign_up_tax['cart_tax'] > 0 )
						$order_taxes[$index]['cart_tax'] = $sign_up_tax['cart_tax'];

				} elseif ( ! $has_trial && $sign_up_fee_total > 0 ) { // Sign up fee taxes need to be added to order taxes

					if ( isset( $sign_up_tax['cart_tax'] ) && $sign_up_tax['cart_tax'] > 0 )
						$order_taxes[$index]['cart_tax'] += $sign_up_tax['cart_tax'];

				}

			}

			if ( false == self::$is_wc_version_2 ) { // Doing it right: updated Subs *before* updating WooCommerce, the WooCommerce updater will take care of data migration

				update_post_meta( $order_id, '_order_taxes', $order_taxes );

			} else { // Doing it wrong: updated Subs *after* updating WooCommerce, need to store in WC2.0 tax structure

				$index = 0;
				$new_order_taxes = $order->get_taxes();

				foreach( $new_order_taxes as $item_id => $order_tax ) {

					$index = $index + 1;

					if ( ! isset( $order_taxes[$index]['label'] ) || ! isset( $order_taxes[$index]['cart_tax'] ) || ! isset( $order_taxes[$index]['shipping_tax'] ) )
						continue;

					// Add line item meta
					if ( $item_id ) {
						woocommerce_update_order_item_meta( $item_id, 'compound', absint( isset( $order_taxes[$index]['compound'] ) ? $order_taxes[$index]['compound'] : 0 ) );
						woocommerce_update_order_item_meta( $item_id, 'tax_amount', woocommerce_clean( $order_taxes[$index]['cart_tax'] ) );
						woocommerce_update_order_item_meta( $item_id, 'shipping_tax_amount', woocommerce_clean( $order_taxes[$index]['shipping_tax'] ) );
					}
				}

			}

			/* Upgrade each order item to use new Item Meta schema */
			$order_subscription_periods       = WC_Subscriptions_Order::get_meta( $order_id, '_order_subscription_periods', array() );
			$order_subscription_intervals     = WC_Subscriptions_Order::get_meta( $order_id, '_order_subscription_intervals', array() );
			$order_subscription_lengths       = WC_Subscriptions_Order::get_meta( $order_id, '_order_subscription_lengths', array() );
			$order_subscription_trial_lengths = WC_Subscriptions_Order::get_meta( $order_id, '_order_subscription_trial_lengths', array() );

			$order_items = $order->get_items();

			foreach ( $order_items as $index => $order_item ) {

				$product_id = WC_Subscriptions_Order::get_items_product_id( $order_item );
				$item_meta  = new WC_Order_Item_Meta( $order_item['item_meta'] );

				$subscription_interval     = ( isset( $order_subscription_intervals[$product_id] ) ) ? $order_subscription_intervals[$product_id] : 1;
				$subscription_length       = ( isset( $order_subscription_lengths[$product_id] ) ) ? $order_subscription_lengths[$product_id] : 0;
				$subscription_trial_length = ( isset( $order_subscription_trial_lengths[$product_id] ) ) ? $order_subscription_trial_lengths[$product_id] : 0;

				$subscription_sign_up_fee  = WC_Subscriptions_Order::get_meta( $order, '_cart_contents_sign_up_fee_total', 0 );

				if ( $sign_up_fee_total > 0 ) {

					// Discounted price * Quantity
					$sign_up_fee_line_total = WC_Subscriptions_Order::get_meta( $order, '_cart_contents_sign_up_fee_total', 0 );
					$sign_up_fee_line_tax   = WC_Subscriptions_Order::get_meta( $order, '_sign_up_fee_tax_total', 0 );

					// Base price * Quantity
					$sign_up_fee_line_subtotal     = WC_Subscriptions_Order::get_meta( $order, '_cart_contents_sign_up_fee_total', 0 ) + WC_Subscriptions_Order::get_meta( $order, '_sign_up_fee_discount_cart', 0 );
					$sign_up_fee_propotion         = ( $sign_up_fee_line_total > 0 ) ? $sign_up_fee_line_subtotal / $sign_up_fee_line_total : 0;
					$sign_up_fee_line_subtotal_tax = WC_Subscriptions_Manager::get_amount_from_proportion( WC_Subscriptions_Order::get_meta( $order, '_sign_up_fee_tax_total', 0 ), $sign_up_fee_propotion );

					if ( $has_trial ) { // Set line item totals equal to sign up fee totals

						$order_item['line_subtotal']     = $sign_up_fee_line_subtotal;
						$order_item['line_subtotal_tax'] = $sign_up_fee_line_subtotal_tax;
						$order_item['line_total']        = $sign_up_fee_line_total;
						$order_item['line_tax']          = $sign_up_fee_line_tax;

					} else { // No trial period, sign up fees need to be added to order totals

						$order_item['line_subtotal']     += $sign_up_fee_line_subtotal;
						$order_item['line_subtotal_tax'] += $sign_up_fee_line_subtotal_tax;
						$order_item['line_total']        += $sign_up_fee_line_total;
						$order_item['line_tax']          += $sign_up_fee_line_tax;

					}
				}

				// Upgrading with WC 1.x
				if ( method_exists( $item_meta, 'add' ) ) {

					$item_meta->add( '_subscription_period', $order_subscription_periods[$product_id] );
					$item_meta->add( '_subscription_interval', $subscription_interval );
					$item_meta->add( '_subscription_length', $subscription_length );
					$item_meta->add( '_subscription_trial_length', $subscription_trial_length );

					$item_meta->add( '_subscription_recurring_amount', $order_item['line_subtotal'] ); // WC_Subscriptions_Product::get_price() would return a price without filters applied
					$item_meta->add( '_subscription_sign_up_fee', $subscription_sign_up_fee );

					// Set recurring amounts for the item
					$item_meta->add( '_recurring_line_total', $order_item['line_total'] );
					$item_meta->add( '_recurring_line_tax', $order_item['line_tax'] );
					$item_meta->add( '_recurring_line_subtotal', $order_item['line_subtotal'] );
					$item_meta->add( '_recurring_line_subtotal_tax', $order_item['line_subtotal_tax'] );

					$order_item['item_meta'] = $item_meta->meta;

					$order_items[$index] = $order_item;

				} else { // Ignoring all advice, upgrading 4 months after version 1.2 was released, and doing it with WC 2.0 installed

					woocommerce_add_order_item_meta( $index, '_subscription_period', $order_subscription_periods[$product_id] );
					woocommerce_add_order_item_meta( $index, '_subscription_interval', $subscription_interval );
					woocommerce_add_order_item_meta( $index, '_subscription_length', $subscription_length );
					woocommerce_add_order_item_meta( $index, '_subscription_trial_length', $subscription_trial_length );
					woocommerce_add_order_item_meta( $index, '_subscription_trial_period', $order_subscription_periods[$product_id] );

					woocommerce_add_order_item_meta( $index, '_subscription_recurring_amount', $order_item['line_subtotal'] );
					woocommerce_add_order_item_meta( $index, '_subscription_sign_up_fee', $subscription_sign_up_fee );

					// Calculated recurring amounts for the item
					woocommerce_add_order_item_meta( $index, '_recurring_line_total', $order_item['line_total'] );
					woocommerce_add_order_item_meta( $index, '_recurring_line_tax', $order_item['line_tax'] );
					woocommerce_add_order_item_meta( $index, '_recurring_line_subtotal', $order_item['line_subtotal'] );
					woocommerce_add_order_item_meta( $index, '_recurring_line_subtotal_tax', $order_item['line_subtotal_tax'] );

					if ( $sign_up_fee_total > 0 ) { // Order totals have changed
						woocommerce_update_order_item_meta( $index, '_line_subtotal', woocommerce_format_decimal( $order_item['line_subtotal'] ) );
						woocommerce_update_order_item_meta( $index, '_line_subtotal_tax', woocommerce_format_decimal( $order_item['line_subtotal_tax'] ) );
						woocommerce_update_order_item_meta( $index, '_line_total', woocommerce_format_decimal( $order_item['line_total'] ) );
						woocommerce_update_order_item_meta( $index, '_line_tax', woocommerce_format_decimal( $order_item['line_tax'] ) );
					}

				}
			}

			// Save the new meta on the order items for WC 1.x (the API functions already saved the data for WC2.x)
			if ( false == self::$is_wc_version_2 )
				update_post_meta( $order_id, '_order_items', $order_items );

			$upgraded_orders[] = $order_id;

			update_option( 'wcs_1_2_upgraded_order_ids', $upgraded_orders );

		}

		// Remove the lock on upgrading
		delete_transient( 'wc_subscriptions_is_upgrading' );
	}

	/**
	 * Version 1.2 introduced child renewal orders to keep a record of each completed subscription
	 * payment. Before 1.2, these orders did not exist, so this function creates them.
	 *
	 * @since 1.2
	 */
	private static function generate_renewal_orders() {
		global $woocommerce, $wpdb;

		$subscriptions_grouped_by_user = WC_Subscriptions_Manager::get_all_users_subscriptions();

		// Don't send any order emails
		$email_actions = array( 'woocommerce_low_stock', 'woocommerce_no_stock', 'woocommerce_product_on_backorder', 'woocommerce_order_status_pending_to_processing', 'woocommerce_order_status_pending_to_completed', 'woocommerce_order_status_pending_to_on-hold', 'woocommerce_order_status_failed_to_processing', 'woocommerce_order_status_failed_to_completed', 'woocommerce_order_status_pending_to_processing', 'woocommerce_order_status_pending_to_on-hold', 'woocommerce_order_status_completed', 'woocommerce_new_customer_note' );
		foreach ( $email_actions as $action )
			remove_action( $action, array( &$woocommerce, 'send_transactional_email') );

		remove_action( 'woocommerce_subscriptions_renewal_order_created', 'WC_Subscriptions_Renewal_Order::maybe_send_customer_renewal_order_email', 10, 1 );
		remove_action( 'woocommerce_payment_complete', 'WC_Subscriptions_Renewal_Order::maybe_record_renewal_order_payment', 10, 1 );

		foreach ( $subscriptions_grouped_by_user as $user_id => $users_subscriptions ) {
			foreach ( $users_subscriptions as $subscription_key => $subscription ) {
				$order_post = get_post( $subscription['order_id'] );

				if ( isset( $subscription['completed_payments'] ) && count( $subscription['completed_payments'] ) > 0 && $order_post != null ) {
					foreach ( $subscription['completed_payments'] as $payment_date ) {

						$existing_renewal_order = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_date_gmt = %s AND post_parent = %d AND post_type = 'shop_order'", $payment_date, $subscription['order_id'] ) );

						// If a renewal order exists on this date, don't generate another one
						if ( NULL !== $existing_renewal_order )
							continue;

						$renewal_order_id = WC_Subscriptions_Renewal_Order::generate_renewal_order( $subscription['order_id'], $subscription['product_id'], array( 'new_order_role' => 'child' ) );

						if ( $renewal_order_id ) {

							// Mark the order as paid
							$renewal_order = new WC_Order( $renewal_order_id );

							$renewal_order->payment_complete();

							// Avoid creating 100s "processing" orders
							$renewal_order->update_status( 'completed' );

							// Set correct dates on the order
							$renewal_order = array(
								'ID'            => $renewal_order_id,
								'post_date'     => $payment_date,
								'post_date_gmt' => $payment_date
							);
							wp_update_post( $renewal_order );

							update_post_meta( $renewal_order_id, '_paid_date', $payment_date );
							update_post_meta( $renewal_order_id, '_completed_date', $payment_date );

						}

					}
				}
			}
		}
	}

	/**
	 * Let the site administrator know we are upgrading the database and provide a confirmation is complete.
	 *
	 * This is important to avoid the possibility of a database not upgrading correctly, but the site continuing
	 * to function without any remedy.
	 *
	 * @since 1.2
	 */
	public static function display_database_upgrade_helper() {
		global $woocommerce;

		$upgrade_step = ( isset( $_GET['wcs_upgrade_step'] ) ) ? (int)$_GET['wcs_upgrade_step'] : 1;

@header( 'Content-Type: ' . get_option( 'html_type' ) . '; charset=' . get_option( 'blog_charset' ) ); ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
	<meta http-equiv="Content-Type" content="<?php bloginfo( 'html_type' ); ?>; charset=<?php echo get_option( 'blog_charset' ); ?>" />
	<title><?php _e( 'WooCommerce Subscriptions Update', WC_Subscriptions::$text_domain ); ?></title>
	<?php wp_admin_css( 'install', true ); ?>
	<?php wp_admin_css( 'ie', true ); ?>
	<style type="text/css">
		.help_tip .tooltip {
			display: none;
		}
		.help_tip:hover .tooltip {
			display: block;
		}
		.tooltip {
			position: absolute;
			width: 480px;
			height: 270px;
			line-height: 1.5em;
			padding: 10px;
			font-size: 14px;
			text-align: left;
			color: rgb(18, 18, 18);
			background: rgb(249, 249, 249);
			border: 4px solid rgb(249, 249, 249);
			border-radius: 5px;
			text-shadow: none;
			box-shadow: rgba(0, 0, 0, 0.2) 0px 0px 4px 1px;
			margin-top: -332px;
			margin-left: 100px;
		}
		.tooltip:after {
			content: "";
			position: absolute;
			width: 0;
			height: 0;
			border-width: 14px;
			border-style: solid;
			border-color: #F9F9F9 transparent transparent transparent;
			top: 292px;
			left: 366px;
		}
	</style>
</head>
<body>
<h1 id="logo"><img alt="WordPress" src="<?php echo plugins_url( 'images/woocommerce_subscriptions_logo.png', WC_Subscriptions::$plugin_file ); ?>" /></h1>

<?php
switch ( $upgrade_step ) :
	case 1:
?>
<h2><?php _e( 'Database Update Required', WC_Subscriptions::$text_domain ); ?></h2>
<p><?php _e( 'WooCommerce Subscriptions has been updated!', WC_Subscriptions::$text_domain ); ?></p>
<p><?php _e( 'Before we send you on your way, we need to update your database to the newest version.', WC_Subscriptions::$text_domain ); ?></p>
<p><?php _e( 'The update process may take a little while, so please be patient.', WC_Subscriptions::$text_domain ); ?></p>
<form id="subscriptions-upgrade" method="get" action="<?php echo admin_url( 'admin.php' ); ?>">
	<p>
		<label for="wcs_create_orders">
			<input type="checkbox" id="wcs_create_orders" name="wcs_create_orders" checked="checked">
			<?php _e( 'Also generate an order to record each completed subscription payment', WC_Subscriptions::$text_domain ); ?>
		</label>
		<a class="help_tip" href="#">
			<span class="tooltip">
				<?php _e( 'To improve record keeping and subscription management, this version of Subscriptions records each recurring payment in a new order, instead of creating an order only for the initial payment.', WC_Subscriptions::$text_domain ); ?><br/><br/>
				<?php _e( 'Keeping this box checked will create an order for all subscription payments made before now.', WC_Subscriptions::$text_domain ); ?><br/><br/>
				<?php _e( 'For example, if a customer signed-up for a monthly subscription on July 21st, you will only have one order for this subscription from that date. If you keep this box checked, two new orders will be created for the recurring payments on August 21st and September 21st.', WC_Subscriptions::$text_domain ); ?><br/><br/>
				<em><?php _e( 'Not sure what to do?', WC_Subscriptions::$text_domain ); ?></em>
				<?php _e( 'Keep the box checked.', WC_Subscriptions::$text_domain ); ?>
			</span>
			<img src="<?php echo $woocommerce->plugin_url() . '/assets/images/help.png'; ?>" />
		</a>
	</p>
	<input type="hidden" name="wcs_upgrade_step" value="2">
	<input type="submit" class="button" value="<?php _e( 'Update Database', WC_Subscriptions::$text_domain ); ?>">
</form>
<?php
		break;

	case 3:
?>
<h2><?php _e( 'Update Complete', WC_Subscriptions::$text_domain ); ?></h2>
	<p><?php _e( 'Your database has been successfully updated!', WC_Subscriptions::$text_domain ); ?></p>
	<p class="step"><a class="button" href="<?php echo esc_url( admin_url() ); ?>"><?php _e( 'Continue', WC_Subscriptions::$text_domain ); ?></a></p>

<?php
		break;
endswitch;
?>
</body>
</html>
<?php
	}
}
add_action( 'plugins_loaded', 'WC_Subscriptions_Upgrader::init', 10 );
