<?php
/**
 * Subscriptions Checkout
 * 
 * Extends the WooCommerce checkout class to add subscription meta on checkout.
 *
 * @package		WooCommerce Subscriptions
 * @subpackage	WC_Subscriptions_Checkout
 * @category	Class
 * @author		Brent Shepherd
 */
class WC_Subscriptions_Checkout {

	private static $signup_option_changed = false;

	private static $guest_checkout_option_changed = false;

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 * 
	 * @since 1.0
	 */
	public static function init(){

		// Add the order item meta for WC 1.x - 'new_order_item' was removed in WC 2.0
		add_filter( 'new_order_item', __CLASS__ . '::add_order_item_meta_old', 10, 1 );

		// Add the order item meta for WC 2.0+
		add_action( 'woocommerce_add_order_item_meta', __CLASS__ . '::add_order_item_meta', 10, 2 );

		// Add the recurring totals meta
		add_action( 'woocommerce_checkout_update_order_meta', __CLASS__ . '::add_order_meta', 10, 2 );

		// Make sure users can register on checkout (before any other hooks before checkout)
		add_action( 'woocommerce_before_checkout_form', __CLASS__ . '::make_checkout_registration_possible', -1 );

		// Display account fields as required
		add_action( 'woocommerce_checkout_fields', __CLASS__ . '::make_checkout_account_fields_required', 10 );

		// Restore the settings after switching them for the checkout form
		add_action( 'woocommerce_after_checkout_form', __CLASS__ . '::restore_checkout_registration_settings', 100 );

		// Make sure guest checkout is not enabled in option param passed to WC JS 
		add_filter( 'woocommerce_params', __CLASS__ . '::filter_woocommerce_script_paramaters', 10, 1 );

		// Check if we want to create the order ourself (a renewal order)
		add_filter( 'woocommerce_create_order', __CLASS__ . '::filter_woocommerce_create_order', 10, 2 );

		// Force checkout during checkout process
		add_action( 'woocommerce_before_checkout_process', __CLASS__ . '::force_registration_during_checkout', 10 );

		add_filter( 'woocommerce_my_account_my_orders_actions', __CLASS__ . '::filter_woocommerce_my_account_my_orders_actions', 10, 2 );

	}

	/**
	 * Customise which actions are shown against a subscriptions order on the My Account page.
	 *
	 * @since 1.3
	 */
	public static function filter_woocommerce_my_account_my_orders_actions( $actions, $order ) {

		if ( WC_Subscriptions_Order::order_contains_subscription( $order ) || WC_Subscriptions_Renewal_Order::is_renewal( $order ) ) {
			unset( $actions['cancel'] );

			if ( is_numeric( get_post_meta( $order->id, '_failed_order_replaced_by', true ) ) )
				unset( $actions['pay'] );

			$original_order = WC_Subscriptions_Renewal_Order::get_parent_order( $order );

			$order_items = WC_Subscriptions_Order::get_recurring_items( $original_order );
			$first_order_item = reset( $order_items );
			$product_id = WC_Subscriptions_Order::get_items_product_id( $first_order_item );

			$subscription_key = WC_Subscriptions_Manager::get_subscription_key( $original_order->id, $product_id );
			$subscription = WC_Subscriptions_Manager::get_users_subscription( $original_order->customer_user, $subscription_key );

			if ( empty( $subscription ) || ! in_array( $subscription['status'], array( 'on-hold', 'pending' ) ) )
				unset( $actions['pay'] );

		}

		return $actions;
	}

	/**
	 * When creating an order at checkout, if the order is for renewing a subscription from a failed
	 * payment, hijack the order creation to make a renewal order - not a vanilla WooCommerce order.
	 *
	 * @since 1.3
	 */
	public static function filter_woocommerce_create_order( $order_id, $checkout_object ) {
		global $woocommerce;

		$cart_item = WC_Subscriptions_Cart::cart_contains_subscription_renewal();

		if ( $cart_item && 'child' == $cart_item['subscription_renewal']['role'] ) {

			$product_id        = $cart_item['product_id'];
			$failed_order_id   = $cart_item['subscription_renewal']['failed_order'];
			$original_order_id = $cart_item['subscription_renewal']['original_order'];
			$role              = $cart_item['subscription_renewal']['role'];

			remove_action( 'woocommerce_subscriptions_renewal_order_created', 'WC_Subscriptions_Renewal_Order::maybe_send_customer_renewal_order_email', 10, 1 );

			$renewal_order_args = array(
				'new_order_role'   => $role,
				'checkout_renewal' => true,
				'failed_order_id'  => $failed_order_id
			);

			$order_id = WC_Subscriptions_Renewal_Order::generate_renewal_order( $original_order_id, $product_id, $renewal_order_args );

			if ( $checkout_object->posted['payment_method'] ) {

				$available_gateways = $woocommerce->payment_gateways->get_available_payment_gateways();

				if ( isset( $available_gateways[$checkout_object->posted['payment_method']] ) ) {
					$payment_method = $available_gateways[$checkout_object->posted['payment_method']];
					$payment_method->validate_fields();
					update_post_meta( $order_id, '_payment_method', 	  $payment_method->id );
					update_post_meta( $order_id, '_payment_method_title', $payment_method->get_title() );
				}
			}

			if ( $checkout_object->posted['shipping_method'] ) {

				$available_shipping_methods = $woocommerce->shipping->get_available_shipping_methods();

				if ( isset( $available_shipping_methods[$checkout_object->posted['shipping_method']] ) ) {
					$shipping_method = $available_shipping_methods[$checkout_object->posted['shipping_method']];
					update_post_meta( $order_id, '_shipping_method', 	  $shipping_method->id );
					update_post_meta( $order_id, '_shipping_method_title', $shipping_method->label );
				}
			}

			if ( isset( $failed_order_id ) ) {
				$failed_order = new WC_Order( $failed_order_id );
				if ( $failed_order->status == 'failed' ) {
					update_post_meta( $failed_order_id, '_failed_order_replaced_by', $order_id );
				}
			}
		}

		return $order_id;
	}

	/**
	 * When a new order is inserted, add subscriptions related order meta.
	 *
	 * @since 1.0
	 */
	public static function add_order_meta( $order_id, $posted ) {
		global $woocommerce;

		if ( ! WC_Subscriptions_Cart::cart_contains_subscription_renewal( 'child' ) && WC_Subscriptions_Order::order_contains_subscription( $order_id ) ) { // This works because the 'woocommerce_add_order_item_meta' runs before the 'woocommerce_checkout_update_order_meta' hook

			// Set the recurring totals so totals display correctly on order page
			update_post_meta( $order_id, '_order_recurring_discount_cart', WC_Subscriptions_Cart::get_recurring_discount_cart() );
			update_post_meta( $order_id, '_order_recurring_discount_total', WC_Subscriptions_Cart::get_recurring_discount_total() );
			update_post_meta( $order_id, '_order_recurring_shipping_tax_total', WC_Subscriptions_Cart::get_recurring_shipping_tax_total() );
			update_post_meta( $order_id, '_order_recurring_tax_total', WC_Subscriptions_Cart::get_recurring_total_tax() );
			update_post_meta( $order_id, '_order_recurring_total', WC_Subscriptions_Cart::get_recurring_total() );

			// Get recurring taxes into same format as _order_taxes
			$order_recurring_taxes = array();

			foreach ( WC_Subscriptions_Cart::get_recurring_taxes() as $tax_key => $tax_amount ) {

				$is_compound = ( $woocommerce->cart->tax->is_compound( $tax_key ) ) ? 1 : 0;

				if ( isset( $woocommerce->cart->taxes[$tax_key] ) ) {
					$cart_tax     = $tax_amount;
					$shipping_tax = 0;
				} else {
					$cart_tax     = 0;
					$shipping_tax = $tax_amount;
				}

				if ( function_exists( 'woocommerce_add_order_item_meta' ) ) { // WC 2.0+

					$item_id = woocommerce_add_order_item( $order_id, array(
						'order_item_name' => $woocommerce->cart->tax->get_rate_code( $tax_key ),
						'order_item_type' => 'recurring_tax'
					) );

					if ( $item_id ) {
						woocommerce_add_order_item_meta( $item_id, 'rate_id', $tax_key );
						woocommerce_add_order_item_meta( $item_id, 'label', $woocommerce->cart->tax->get_rate_label( $tax_key ) );
						woocommerce_add_order_item_meta( $item_id, 'compound', $is_compound );
						woocommerce_add_order_item_meta( $item_id, 'tax_amount', woocommerce_clean( $cart_tax ) );
						woocommerce_add_order_item_meta( $item_id, 'shipping_tax_amount', woocommerce_clean( $shipping_tax ) );
					}

				} else { // WC 1.x

					$order_recurring_taxes[] = array(
						'label' 		=> $woocommerce->cart->tax->get_rate_label( $tax_key ),
						'compound' 		=> $is_compound,
						'cart_tax' 		=> woocommerce_format_total( $cart_tax ),
						'shipping_tax' 	=> woocommerce_format_total( $shipping_tax )
					);

					// Inefficient but keeps WC 1.x code grouped together
					update_post_meta( $order_id, '_order_recurring_taxes', $order_recurring_taxes );
				}
			}

			$payment_gateways = $woocommerce->payment_gateways->payment_gateways();

			if ( 'yes' == get_option( WC_Subscriptions_Admin::$option_prefix . '_turn_off_automatic_payments', 'no' ) )
				update_post_meta( $order_id, '_wcs_requires_manual_renewal', 'true' );
			elseif ( isset( $payment_gateways[$posted['payment_method']] ) && ! $payment_gateways[$posted['payment_method']]->supports( 'subscriptions' ) )
				update_post_meta( $order_id, '_wcs_requires_manual_renewal', 'true' );

			$cart_item = WC_Subscriptions_Cart::cart_contains_subscription_renewal();

			if ( isset( $cart_item['subscription_renewal'] ) && 'parent' == $cart_item['subscription_renewal']['role'] )
				update_post_meta( $order_id, '_original_order', $cart_item['subscription_renewal']['original_order'] );

		}
	}

	/**
	 * Add each subscription product's details to an order so that the state of the subscription persists even when a product is changed
	 *
	 * For the pre WC 2.0 method, @see self::add_order_item_meta_old()
	 *
	 * @since 1.2.5
	 */
	public static function add_order_item_meta( $item_id, $values ) {
		global $woocommerce;

		if ( ! WC_Subscriptions_Cart::cart_contains_subscription_renewal( 'child' ) && WC_Subscriptions_Product::is_subscription( $values['product_id'] ) ) {

			$cart_item = $values['data'];

			$product_id = empty( $values['variation_id'] ) ? $values['product_id'] : $values['variation_id'];

			// Add subscription details so order state persists even when a product is changed
			$period       = isset( $cart_item->subscription_period ) ? $cart_item->subscription_period : WC_Subscriptions_Product::get_period( $product_id );
			$interval     = isset( $cart_item->subscription_period_interval ) ? $cart_item->subscription_period_interval : WC_Subscriptions_Product::get_interval( $product_id );
			$length       = isset( $cart_item->subscription_length ) ? $cart_item->subscription_length : WC_Subscriptions_Product::get_length( $product_id );
			$trial_length = isset( $cart_item->subscription_trial_length ) ? $cart_item->subscription_trial_length : WC_Subscriptions_Product::get_trial_length( $product_id );
			$trial_period = isset( $cart_item->subscription_trial_period ) ? $cart_item->subscription_trial_period : WC_Subscriptions_Product::get_trial_period( $product_id );
			$sign_up_fee  = isset( $cart_item->subscription_sign_up_fee ) ? $cart_item->subscription_sign_up_fee : WC_Subscriptions_Product::get_sign_up_fee( $product_id );

			woocommerce_add_order_item_meta( $item_id, '_subscription_period', $period );
			woocommerce_add_order_item_meta( $item_id, '_subscription_interval', $interval );
			woocommerce_add_order_item_meta( $item_id, '_subscription_length', $length );
			woocommerce_add_order_item_meta( $item_id, '_subscription_trial_length', $trial_length );
			woocommerce_add_order_item_meta( $item_id, '_subscription_trial_period', $trial_period );
			woocommerce_add_order_item_meta( $item_id, '_subscription_recurring_amount', $woocommerce->cart->base_recurring_prices[$product_id] ); // WC_Subscriptions_Product::get_price() would return a price without filters applied
			woocommerce_add_order_item_meta( $item_id, '_subscription_sign_up_fee', $sign_up_fee );

			// Calculated recurring amounts for the item
			woocommerce_add_order_item_meta( $item_id, '_recurring_line_total', $woocommerce->cart->recurring_cart_contents[$values['product_id']]['recurring_line_total'] );
			woocommerce_add_order_item_meta( $item_id, '_recurring_line_tax', $woocommerce->cart->recurring_cart_contents[$values['product_id']]['recurring_line_tax'] );
			woocommerce_add_order_item_meta( $item_id, '_recurring_line_subtotal', $woocommerce->cart->recurring_cart_contents[$values['product_id']]['recurring_line_subtotal'] );
			woocommerce_add_order_item_meta( $item_id, '_recurring_line_subtotal_tax', $woocommerce->cart->recurring_cart_contents[$values['product_id']]['recurring_line_subtotal_tax'] );
		}
	}

	/**
	 * Add each subscription product's details to an order for versions of WooCommerce 1.x.
	 *
	 * @since 1.2
	 */
	public static function add_order_item_meta_old( $order_item ) {
		global $woocommerce;

		if ( WC_Subscriptions_Product::is_subscription( $order_item['id'] ) ) {

			// Make sure existing meta persists
			$item_meta = new WC_Order_Item_Meta( $order_item['item_meta'] );

			// Add subscription details so order state persists even when a product is changed
			$item_meta->add( '_subscription_period', WC_Subscriptions_Product::get_period( $order_item['id'] ) );
			$item_meta->add( '_subscription_interval', WC_Subscriptions_Product::get_interval( $order_item['id'] ) );
			$item_meta->add( '_subscription_length', WC_Subscriptions_Product::get_length( $order_item['id'] ) );
			$item_meta->add( '_subscription_trial_length', WC_Subscriptions_Product::get_trial_length( $order_item['id'] ) );
			$item_meta->add( '_subscription_trial_period', WC_Subscriptions_Product::get_trial_period( $order_item['id'] ) );
			$item_meta->add( '_subscription_recurring_amount', $woocommerce->cart->base_recurring_prices[$order_item['id']] ); // WC_Subscriptions_Product::get_price() would return a price without filters applied
			$item_meta->add( '_subscription_sign_up_fee', WC_Subscriptions_Product::get_sign_up_fee( $order_item['id'] ) );

			// Calculated recurring amounts for the item
			$item_meta->add( '_recurring_line_total', $woocommerce->cart->recurring_cart_contents[$order_item['id']]['recurring_line_total'] );
			$item_meta->add( '_recurring_line_tax', $woocommerce->cart->recurring_cart_contents[$order_item['id']]['recurring_line_tax'] );
			$item_meta->add( '_recurring_line_subtotal', $woocommerce->cart->recurring_cart_contents[$order_item['id']]['recurring_line_subtotal'] );
			$item_meta->add( '_recurring_line_subtotal_tax', $woocommerce->cart->recurring_cart_contents[$order_item['id']]['recurring_line_subtotal_tax'] );

			$order_item['item_meta'] = $item_meta->meta;
		}

		return $order_item;
	}

	/**
	 * If shopping cart contains subscriptions, make sure a user can register on the checkout page
	 *
	 * @since 1.0
	 */
	public static function make_checkout_registration_possible( $checkout = '' ) {

		if ( WC_Subscriptions_Cart::cart_contains_subscription() && ! is_user_logged_in() ) {

			if ( version_compare( WOOCOMMERCE_VERSION, "2.0.0" ) >= 0 ) { // WC 2.0+

				// Make sure users can sign up
				if ( false === $checkout->enable_signup ) {
					$checkout->enable_signup = true;
					self::$signup_option_changed = true;
				}

				// Make sure users are required to register an account
				if ( true === $checkout->enable_guest_checkout ) {
					$checkout->enable_guest_checkout = false;
					self::$guest_checkout_option_changed = true;

					if ( ! is_user_logged_in() )
						$checkout->must_create_account = true;
				}

			} else { // WC 1.x

				// Make sure users can sign up
				if ( 'no' == get_option( 'woocommerce_enable_signup_and_login_from_checkout' ) ) {
					update_option( 'woocommerce_enable_signup_and_login_from_checkout', 'yes' );
					self::$signup_option_changed = true;
				}

				// Make sure users are required to register an account
				if ( 'yes' == get_option( 'woocommerce_enable_guest_checkout' ) ) {
					update_option( 'woocommerce_enable_guest_checkout', 'no' );
					self::$guest_checkout_option_changed = true;
				}

			}

		}

	}

	/**
	 * Make sure account fields display the required "*" when they are required.
	 *
	 * @since 1.3.5
	 */
	public static function make_checkout_account_fields_required( $checkout_fields ) {

		if ( WC_Subscriptions_Cart::cart_contains_subscription() && ! is_user_logged_in() ) {

			$account_fields = array(
				'account_username',
				'account_password',
				'account_password-2',
			);

			foreach ( $account_fields as $account_field )
				if ( isset( $checkout_fields['account'][$account_field] ) )
					$checkout_fields['account'][$account_field]['required'] = true;

		}

		return $checkout_fields;
	}

	/**
	 * After displaying the checkout form, restore the store's original registration settings.
	 *
	 * @since 1.1
	 */
	public static function restore_checkout_registration_settings( $checkout = '' ) {

		if ( self::$signup_option_changed ) {
			if ( version_compare( WOOCOMMERCE_VERSION, "2.0.0" ) >= 0 ) {
				$checkout->enable_signup = false;
			} else {
				update_option( 'woocommerce_enable_signup_and_login_from_checkout', 'no' );
			}
		}

		if ( self::$guest_checkout_option_changed ) {
			if ( version_compare( WOOCOMMERCE_VERSION, "2.0.0" ) >= 0 ) {
				$checkout->enable_guest_checkout = true;
				if ( ! is_user_logged_in() ) // Also changed must_create_account
					$checkout->must_create_account = false;
			} else {
				update_option( 'woocommerce_enable_guest_checkout', 'yes' );
			}
		}
	}

	/**
	 * Also make sure the guest checkout option value passed to the woocommerce.js forces registration.
	 * Otherwise the registration form is hidden by woocommerce.js.
	 *
	 * @since 1.1
	 */
	public static function filter_woocommerce_script_paramaters( $woocommerce_params ) {

		if ( WC_Subscriptions_Cart::cart_contains_subscription() && ! is_user_logged_in() && $woocommerce_params['option_guest_checkout'] == 'yes' )
			$woocommerce_params['option_guest_checkout'] = 'no';

		return $woocommerce_params;
	}

	/**
	 * During the checkout process, force registration when the cart contains a subscription.
	 *
	 * @since 1.1
	 */
	public static function force_registration_during_checkout( $woocommerce_params ) {

		if ( WC_Subscriptions_Cart::cart_contains_subscription() && ! is_user_logged_in() )
			$_POST['createaccount'] = 1;

	}

}

WC_Subscriptions_Checkout::init();
