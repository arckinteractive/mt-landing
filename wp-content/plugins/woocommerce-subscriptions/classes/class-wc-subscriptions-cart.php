<?php
/**
 * Subscriptions Cart Class
 * 
 * Mirrors a few functions in the WC_Cart class to work for subscriptions. 
 * 
 * @package		WooCommerce Subscriptions
 * @subpackage	WC_Subscriptions_Cart
 * @category	Class
 * @author		Brent Shepherd
 * @since		1.0
 */
class WC_Subscriptions_Cart {

	/**
	 * A flag to control how to modify the calculation of totals by WC_Cart::calculate_totals()
	 *
	 * Can take one of three values:
	 * - 'none' used to calculate the initial total.
	 * - 'combined_total' used to calculate the total of license fee + recurring amount.
	 * - 'sign_up_fee_total' used to calculate the initial amount when there is a free trial period *and* a license fee. Different to 'combined_total' because shipping is not charged on a license fee.
	 * - 'recurring_total' used to calculate the totals for the recurring amount when the recurring amount differs to to 'combined_total' because of coupons or license fees.
	 *
	 * @since 1.2
	 */
	private static $calculation_type = 'none';

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 * 
	 * @since 1.0
	 */
	public static function init() {

		// Make sure the price per period totals persist in the cart
		add_action( 'init', __CLASS__ . '::get_cart_from_session', 6 );
		add_action( 'woocommerce_cart_updated', __CLASS__ . '::set_session' );
		add_action( 'woocommerce_cart_emptied', __CLASS__ . '::reset' );

		// Make sure WC calculates total on sign up fee + price per period, and keep a record of the price per period
		add_action( 'woocommerce_before_calculate_totals', __CLASS__ . '::add_calculation_price_filter', 10 );
		add_action( 'woocommerce_calculate_totals', __CLASS__ . '::remove_calculation_price_filter', 10 );

		add_filter( 'woocommerce_calculated_total', __CLASS__ . '::calculate_subscription_totals', 100, 1 );
		add_filter( 'woocommerce_calculated_total', __CLASS__ . '::set_calculated_total', 100, 1 );

		// Override Formatted Discount Totals
		add_filter( 'woocommerce_cart_discounts_before_tax', __CLASS__ . '::get_formatted_discounts_before_tax', 11, 2 );
		add_filter( 'woocommerce_cart_discounts_after_tax', __CLASS__ . '::get_formatted_discounts_after_tax', 11, 2 );

		// Override Formatted Cart Tax
		add_filter( 'woocommerce_cart_tax_totals', __CLASS__ . '::get_recurring_tax_totals', 11, 2 );

		// Include billing period on shipping total
		add_filter( 'woocommerce_cart_shipping_method_full_label', __CLASS__ . '::get_cart_shipping_method_full_label', 11, 2 );

		// Display Formatted Totals
		add_filter( 'woocommerce_cart_product_subtotal', __CLASS__ . '::get_formatted_product_subtotal', 11, 4 );
		add_filter( 'woocommerce_cart_subtotal', __CLASS__ . '::get_formatted_cart_subtotal', 11, 3 );

		add_filter( 'woocommerce_cart_total_ex_tax', __CLASS__ . '::get_formatted_total_ex_tax', 11, 2 );
		add_filter( 'woocommerce_cart_total', __CLASS__ . '::get_formatted_total', 11, 2 );

		// Renewal order via cart/checkout related
		add_filter( 'woocommerce_get_cart_item_from_session', __CLASS__ . '::get_cart_item_from_session' , 10, 3 );
		add_action( 'woocommerce_before_calculate_totals', __CLASS__ . '::before_calculate_totals', 10 );

		// Sometimes, even if the order total is $0, the cart still needs payment
		add_filter( 'woocommerce_cart_needs_payment', __CLASS__ . '::cart_needs_payment' , 10, 2 );
	}

	/**
	 * Attaches the "set_subscription_prices_for_calculation" filter to the WC Product's woocommerce_get_price hook.
	 *
	 * This function is hooked to "woocommerce_before_calculate_totals" so that WC will calculate a subscription
	 * product's total based on the total of it's price per period and sign up fee (if any).
	 *
	 * @since 1.2
	 */
	public static function add_calculation_price_filter() {

		// Only hook when totals are being calculated completely (on cart & checkout pages)
		if ( ! self::cart_contains_subscription() || ( ! is_checkout() && ! is_cart() && ! defined( 'WOOCOMMERCE_CHECKOUT' ) && ! defined( 'WOOCOMMERCE_CART' ) ) )
			return;

		// Set which price should be used for calculation
		add_filter( 'woocommerce_get_price', __CLASS__ . '::set_subscription_prices_for_calculation', 100, 2 );
	}

	/**
	 * Removes the "set_subscription_prices_for_calculation" filter from the WC Product's woocommerce_get_price hook once
	 * calculations are complete.
	 *
	 * @since 1.2
	 */
	public static function remove_calculation_price_filter() {
		remove_filter( 'woocommerce_get_price', __CLASS__ . '::set_subscription_prices_for_calculation', 100, 2 );
	}

	/**
	 * If we are running a custom calculation, we need to set the price returned by a product
	 * to be the appropriate value. This may include just the license fee, a combination of the
	 * license fee and recurring amount or just the recurring amount (default).
	 *
	 * @since 1.2
	 */
	public static function set_subscription_prices_for_calculation( $price, $product ) {
		global $woocommerce;

		if ( WC_Subscriptions_Product::is_subscription( $product ) ) {

			$product_id = ( $product->is_type( array( 'subscription_variation' ) ) ) ? $product->variation_id : $product->id;

			$woocommerce->cart->base_recurring_prices[$product_id] = $price;

			$sign_up_fee  = WC_Subscriptions_Product::get_sign_up_fee( $product );

			$woocommerce->cart->base_sign_up_fees[$product_id] = $sign_up_fee;

			if ( 'combined_total' == self::$calculation_type ) {

				if ( $sign_up_fee > 0 ) {
					if ( WC_Subscriptions_Product::get_trial_length( $product ) > 0 )
						$price = $sign_up_fee;
					else
						$price += $sign_up_fee;
				}

			} elseif ( 'sign_up_fee_total' == self::$calculation_type ) {

				$price = $sign_up_fee;

			}  // else $price = recurring amount already

		}

		return $price;
	}

	/**
	 * Checks the structure of the subscription price (i.e. whether it includes license fees and/or free trial period)
	 * and calculates the appropriate totals by using the @see self::$calculation_type flag and cloning the cart to
	 * run @see WC_Cart::calculate_totals()
	 *
	 * @since 1.3.5
	 */
	public static function calculate_subscription_totals( $total ) {
		global $woocommerce;

		if ( ! self::cart_contains_subscription() && ! self::cart_contains_subscription_renewal() ) // cart doesn't contain subscription
			return $total;
		elseif ( 'none' != self::$calculation_type ) // We're in the middle of a recalculation, let it run
			return $total;

		$cart_sign_up_fee = self::get_cart_subscription_sign_up_fee();
		$cart_has_trial   = self::cart_contains_free_trial();

		// save the original cart values/totals, as we'll use this when there is no license fee
		if ( $total < 0 )
			$total = 0;
		$woocommerce->cart->total = $total;
		$original_cart = clone $woocommerce->cart;

		// calculate the recurring fee totals in case there are coupons applied
		self::$calculation_type = 'recurring_total';
		$woocommerce->cart->calculate_totals();
		$recurring_cart = clone $woocommerce->cart;

		// if there is a license fee and a free trial, we need to calculate the totals with the license fee only (to account for shipping deductions etc.)
		if ( $cart_sign_up_fee > 0 && $cart_has_trial ) {
			self::$calculation_type = 'sign_up_fee_total';
			$woocommerce->cart->calculate_totals();
			$sign_up_cart = clone $woocommerce->cart;
		}

		// if there is a license fee and NO free trial, we need to calculate totals for combination license fee & price
		if ( $cart_sign_up_fee > 0 && ! $cart_has_trial ) {
			self::$calculation_type = 'combined_total';
			$woocommerce->cart->calculate_totals();
			$initial_cart = clone $woocommerce->cart;
		}

		// Now choose the cart with the appropriate total

		// if there is a license fee and a free trial, the cart used to calculate sign-up totals holds the correct values
		if ( $cart_sign_up_fee > 0 && $cart_has_trial )
			$woocommerce->cart = $sign_up_cart;

		// if there is a license fee and NO free trial, the cart used to combined initial totals holds the correct values
		if ( $cart_sign_up_fee > 0 && ! $cart_has_trial )
			$woocommerce->cart = $initial_cart;

		// if there is NO license fee and NO free trial, the original calculations hold
		if ( 0 == $cart_sign_up_fee && ! $cart_has_trial )
			$woocommerce->cart = $original_cart;

		// if there is NO license fee and a free trial, the original calculations hold, for now, but they will eventually be the license fee only calculations
		if ( 0 == $cart_sign_up_fee && $cart_has_trial )
			$woocommerce->cart = $original_cart;

		// And set the recurring totals for the main cart from the $recurring_cart
		foreach ( $recurring_cart->get_cart() as $cart_item_key => $values ) {
			$woocommerce->cart->recurring_cart_contents[$values['product_id']]['recurring_line_total']        = $values['line_total'];
			$woocommerce->cart->recurring_cart_contents[$values['product_id']]['recurring_line_tax']          = $values['line_tax'];
			$woocommerce->cart->recurring_cart_contents[$values['product_id']]['recurring_line_subtotal']     = $values['line_subtotal'];
			$woocommerce->cart->recurring_cart_contents[$values['product_id']]['recurring_line_subtotal_tax'] = $values['line_subtotal_tax'];
		}

		$woocommerce->cart->recurring_cart_contents_total = $recurring_cart->cart_contents_total;
		$woocommerce->cart->recurring_discount_cart       = $recurring_cart->discount_cart;
		$woocommerce->cart->recurring_discount_total      = $recurring_cart->discount_total;
		$woocommerce->cart->recurring_subtotal            = $recurring_cart->subtotal;
		$woocommerce->cart->recurring_subtotal_ex_tax     = $recurring_cart->subtotal_ex_tax;

		$woocommerce->cart->recurring_taxes     = $recurring_cart->get_taxes();
		$woocommerce->cart->recurring_tax_total = $recurring_cart->tax_total;

		$woocommerce->cart->recurring_total = $recurring_cart->total;

		self::$calculation_type = 'none';

		$total = max( 0, number_format( $woocommerce->cart->cart_contents_total + $woocommerce->cart->tax_total + $woocommerce->cart->shipping_tax_total + $woocommerce->cart->shipping_total - $woocommerce->cart->discount_total + $woocommerce->cart->fee_total, $woocommerce->cart->dp, '.', '' ) );

		return $total;
	}


	/**
	 * Uses the a subscription's combined price total calculated by WooCommerce to determine the 
	 * total price that should be charged per period.
	 *
	 * @since 1.2
	 */
	public static function set_calculated_total( $total ) {
		global $woocommerce;

		if ( 'none' == self::$calculation_type || ( ! self::cart_contains_subscription() && ! self::cart_contains_subscription_renewal() ) )
			return $total;

		if ( 'sign_up_fee_total' == self::$calculation_type ) {

			// We've requested totals be recalculated with sign up fee only, we need to remove anything shipping related from the license fee totals
			$total = $total - $woocommerce->cart->shipping_tax_total - $woocommerce->cart->shipping_total;

			$woocommerce->cart->shipping_taxes = array();
			$woocommerce->cart->shipping_tax_total = 0;

		}

		self::$calculation_type = 'none';

		return $total;
	}

	/* Formatted Totals Functions */

	/**
	 * Returns the subtotal for a cart item including the subscription period and duration details
	 *
	 * @since 1.0
	 */
	public static function get_formatted_product_subtotal( $product_subtotal, $product, $quantity, $cart ) {
		global $woocommerce;

		if ( WC_Subscriptions_Product::is_subscription( $product ) && ! self::cart_contains_subscription_renewal( 'child' ) ) {

			// Avoid infinite loop
			remove_filter( 'woocommerce_cart_product_subtotal', __CLASS__ . '::get_formatted_product_subtotal', 11, 4 );

			add_filter( 'woocommerce_get_price', array( &$product, 'get_sign_up_fee' ), 100, 0 );

			// And get the appropriate sign up fee string
			$sign_up_fee_string = $cart->get_product_subtotal( $product, $quantity );

			remove_filter( 'woocommerce_get_price', array( &$product, 'get_sign_up_fee' ), 100, 0 );

			$product_subtotal = WC_Subscriptions_Product::get_price_string( $product, array(
				'price'       => $product_subtotal,
				'sign_up_fee' => $sign_up_fee_string
				)
			);

			if ( false !== strpos( $product_subtotal, $woocommerce->countries->inc_tax_or_vat() ) )
				$product_subtotal = str_replace( $woocommerce->countries->inc_tax_or_vat(), '', $product_subtotal ) . ' <small class="tax_label">' . $woocommerce->countries->inc_tax_or_vat() . '</small>';
			if ( false !== strpos( $product_subtotal, $woocommerce->countries->ex_tax_or_vat() ) )
				$product_subtotal = str_replace( $woocommerce->countries->ex_tax_or_vat(), '', $product_subtotal ) . ' <small class="tax_label">' .  $woocommerce->countries->ex_tax_or_vat() . '</small>';

			$product_subtotal = '<span class="subscription-price">' . $product_subtotal . '</span>';
		}

		return $product_subtotal;
	}

	/**
	 * Returns a string with the cart discount and subscription period.
	 *
	 * @return mixed formatted price or false if there are none
	 * @since 1.2
	 */
	public static function get_formatted_discounts_before_tax( $discount, $cart ) {
		global $woocommerce;

		if ( self::cart_contains_subscription() && ( $discount !== false || self::get_recurring_discount_cart() > 0 ) )
			$discount = self::get_cart_subscription_string( $discount, self::get_recurring_discount_cart() );

		return $discount;
	}

	/**
	 * Gets the order discount amount - these are applied after tax
	 *
	 * @return mixed formatted price or false if there are none
	 * @since 1.2
	 */
	public static function get_formatted_discounts_after_tax( $discount, $cart ) {

		if ( self::cart_contains_subscription() && ( $discount !== false || self::get_recurring_discount_total() > 0 ) )
			$discount = self::get_cart_subscription_string( $discount, self::get_recurring_discount_total() );

		return $discount;
	}

	/**
	 * Includes the license fee subtotal in the subtotal displayed in the cart.
	 *
	 * @since 1.2
	 */
	public static function get_formatted_cart_subtotal( $cart_subtotal, $compound, $cart ) {
		global $woocommerce;

		if ( self::cart_contains_subscription() && ! self::cart_contains_subscription_renewal( 'child' ) ) {

			// We're in the cart widget and totals haven't been properly calculated yet so just show the product's subscription price string
			if ( $compound ) { // If the cart has compound tax, we want to show the subtotal as cart + non-compound taxes (after discount)

				$cart_subtotal = self::get_cart_subscription_string( $cart_subtotal, self::get_recurring_cart_contents_total() + self::get_recurring_shipping_total() + self::get_recurring_taxes_total( false ) );

			// Otherwise we show cart items totals only (before discount)
			} else {

				// Display varies depending on settings
				if ( $cart->tax_display_cart == 'excl' ) {

					$cart_subtotal = self::get_cart_subscription_string( $cart_subtotal, self::get_recurring_subtotal_ex_tax() );

					if ( $cart->tax_total > 0 && $cart->prices_include_tax )
						$cart_subtotal = str_replace( $woocommerce->countries->ex_tax_or_vat(), '', $cart_subtotal ) . ' <small>' . $woocommerce->countries->ex_tax_or_vat() . '</small>';

				} else {

					$cart_subtotal = self::get_cart_subscription_string( $cart_subtotal, self::get_recurring_subtotal() );

					if ( $cart->tax_total > 0 && ! $cart->prices_include_tax )
						$cart_subtotal = str_replace( $woocommerce->countries->inc_tax_or_vat(), '', $cart_subtotal ) . ' <small>' . $woocommerce->countries->inc_tax_or_vat() . '</small>';

				}
			}
		}

		return $cart_subtotal;
	}

	/**
	 * Displays each cart tax in a subscription string and calculates the license fee taxes (if any)
	 * to display in the string.
	 *
	 * @since 1.2
	 */
	public static function get_formatted_taxes( $formatted_taxes, $cart ) {

		if ( self::cart_contains_subscription() ) {

			$recurring_taxes = self::get_recurring_taxes();

			foreach ( $formatted_taxes as $tax_id => $tax_amount )
				$formatted_taxes[$tax_id] = self::get_cart_subscription_string( $tax_amount, $recurring_taxes[$tax_id] );

			// Add any recurring tax not already handled - when a subscription has a free trial and a license fee, we get a recurring shipping tax with no initial shipping tax
			foreach ( $recurring_taxes as $tax_id => $tax_amount )
				if ( ! array_key_exists( $tax_id, $formatted_taxes ) )
					$formatted_taxes[$tax_id] = self::get_cart_subscription_string( '', $tax_amount );

		}

		return $formatted_taxes;
	}

	/**
	 * Returns an array of taxes merged by code, formatted with recurring amount ready for output.
	 *
	 * @return array Array of tax_id => tax_amounts for items in the cart
	 * @since 1.3.5
	 */
	public static function get_recurring_tax_totals( $tax_totals, $cart ) {

		if ( self::cart_contains_subscription() ) {

			$recurring_taxes = self::get_recurring_taxes();

			// Add any recurring tax not already handled - when a subscription has a free trial and a license fee, we get a recurring shipping tax with no initial shipping tax
			foreach ( $recurring_taxes as $key => $tax ) {

				$code = $cart->tax->get_rate_code( $key );

				if ( ! isset( $tax_totals[ $code ] ) ) {
					$tax_totals[$code] = new stdClass();
					$tax_totals[$code]->is_compound      = $cart->tax->is_compound( $key );
					$tax_totals[$code]->label            = $cart->tax->get_rate_label( $key );
					$tax_totals[$code]->amount           = 0;
				}

				if ( ! isset( $tax_totals[$code]->recurring_amount ) )
					$tax_totals[$code]->recurring_amount = 0;

				$tax_totals[ $code ]->recurring_amount += $tax;
			}

			// Now create the correctly formed subscription price string for each total
			foreach ( $tax_totals as $code => $tax )
				$tax_totals[$code]->formatted_amount = self::get_cart_subscription_string( $tax_totals[$code]->amount, $tax_totals[$code]->recurring_amount );

		}

		return apply_filters( 'woocommerce_cart_recurring_tax_totals', $tax_totals, $cart );
	}

	/**
	 * Appends the cart subscription string to a cart total using the @see self::get_cart_subscription_string and then returns it. 
	 *
	 * @return string Formatted subscription price string for the cart total.
	 * @since 1.2
	 */
	public static function get_formatted_total( $total ) {
		global $woocommerce;

		if ( self::cart_contains_subscription() )
			$total = self::get_cart_subscription_string( $total, self::get_recurring_total(), true );

		return $total;
	}

	/**
	 * Appends the cart's subscription string to the shipping total label using the @see self::get_cart_subscription_string.
	 *
	 * @return string Formatted subscription price string for the cart shipping.
	 * @since 1.3
	 */
	public static function get_cart_shipping_method_full_label( $full_label, $method ) {
		global $woocommerce;

		if ( self::cart_contains_subscription() && $method->cost > 0 )
			$full_label = self::get_cart_subscription_string( '', $full_label );

		return $full_label;
	}

	/**
	 * Appends the cart subscription string to a cart total using the @see self::get_cart_subscription_string and then returns it. 
	 *
	 * @return string Formatted subscription price string for the cart total.
	 * @since 1.2
	 */
	public static function get_formatted_total_ex_tax( $total_ex_tax ) {
		global $woocommerce;

		if ( self::cart_contains_subscription() )
			$total_ex_tax = self::get_cart_subscription_string( $total_ex_tax, self::get_recurring_total_ex_tax(), true );

		return $total_ex_tax;
	}


	/*
	 * Helper functions for extracting the details of subscriptions in the cart
	 */

	/**
	 * Creates a string representation of the subscription period/term for each item in the cart
	 * 
	 * @param $initial_amount string The initial amount to be displayed for the subscription as passed through the @see woocommerce_price() function.
	 * @param $recurring_amount float The price to display in the subscription.
	 * @param $include_lengths bool (optional) Whether to display the trial and length of the subscription. Default to false - don't display. 
	 * @since 1.0
	 */
	public static function get_cart_subscription_string( $initial_amount, $recurring_amount, $include_lengths = false ) {
		global $woocommerce;

		$subscription_details = array(
			'initial_amount'        => $initial_amount,
			'initial_description'   => __( 'now', WC_Subscriptions::$text_domain ),
			'recurring_amount'      => $recurring_amount,
			'subscription_interval' => self::get_cart_subscription_interval(),
			'subscription_period'   => self::get_cart_subscription_period(),
			'trial_length'          => self::get_cart_subscription_trial_length(),
			'trial_period'          => self::get_cart_subscription_trial_period()
		);

		if ( $include_lengths === true ) {
			$subscription_details += array(
				'subscription_length'   => self::get_cart_subscription_length()
			);
		}

		// Override defaults when subscription is for one billing period
		if ( self::get_cart_subscription_length() > 0 && self::get_cart_subscription_length() == self::get_cart_subscription_interval() ) {
			$subscription_details += array(
				'subscription_length' => self::get_cart_subscription_length()
			);
		}

		$initial_amount_string   = ( is_numeric( $subscription_details['initial_amount'] ) ) ? woocommerce_price( $subscription_details['initial_amount'] ) : $subscription_details['initial_amount'];
		$recurring_amount_string = ( is_numeric( $subscription_details['recurring_amount'] ) ) ? woocommerce_price( $subscription_details['recurring_amount'] ) : $subscription_details['recurring_amount'];

		// Don't show up front fees when there is no trial period and no sign up fee and they are the same as the recurring amount
		if ( self::get_cart_subscription_trial_length() == 0 && self::get_cart_subscription_sign_up_fee() == 0 && $initial_amount_string == $recurring_amount_string ) {
			$subscription_details['initial_amount'] = '';
		} elseif ( self::get_cart_subscription_trial_length() > 0 && self::get_cart_subscription_sign_up_fee() == 0 ) {
			/* The order total of a subscription with a free trial is equal to the recurring amount (instead of 0), because an order won't be paid for if it has a total of 0, a patch to allow payment on orders with 0 is coming in WC 1.7 */
			$subscription_details['initial_amount'] = '';
		}

		$subscription_string = WC_Subscriptions_Manager::get_subscription_price_string( $subscription_details );

		return $subscription_string;
	}

	/**
	 * Checks the cart to see if it contains a subscription product. 
	 * 
	 * @since 1.0
	 */
	public static function cart_contains_subscription() {
		global $woocommerce;

		$contains_subscription = false;

		if ( self::cart_contains_subscription_renewal( 'child' ) ) {

			$contains_subscription = false;

		} else if ( ! empty( $woocommerce->cart->cart_contents ) ) {
			foreach ( $woocommerce->cart->cart_contents as $cart_item ) {
				if ( WC_Subscriptions_Product::is_subscription( $cart_item['product_id'] ) ) {
					$contains_subscription = true;
					break;
				}
			}
		}

		return $contains_subscription;
	}

	/**
	 * Checks the cart to see if it contains a subscription product renewal. 
	 * 
	 * Returns the cart_item containing the product renewal, else false.
	 * 
	 * @since 1.3
	 */
	public static function cart_contains_subscription_renewal( $role = '' ) {
		global $woocommerce;

		$contains_renewal = false;

		if ( ! empty( $woocommerce->cart->cart_contents ) ) {
			foreach ( $woocommerce->cart->cart_contents as $cart_item ) {
				if ( isset( $cart_item['subscription_renewal'] ) && ( empty( $role ) || $role === $cart_item['subscription_renewal']['role'] ) ) {
					$contains_renewal = $cart_item;
					break;
				}
			}
		}

		return $contains_renewal;
	}
	
	/**
	 * Checks the cart to see if it contains a subscription product with a free trial
	 *
	 * @since 1.2
	 */
	public static function cart_contains_free_trial() {
		return ( self::get_cart_subscription_trial_length() > 0 ) ? true : false;
	}

	/**
	 * Gets the recalculate flag
	 *
	 * @since 1.2
	 */
	public static function get_calculation_type() {
		return self::$calculation_type;
	}

	/**
	 * Gets the subscription period from the cart and returns it as an array (eg. array( 'month', 'day' ) )
	 * 
	 * @since 1.0
	 */
	public static function get_cart_subscription_period() {
		global $woocommerce;

		foreach ( $woocommerce->cart->cart_contents as $cart_item ) {
			$item_id = empty( $cart_item['variation_id'] ) ? $cart_item['product_id'] : $cart_item['variation_id'];
			if ( isset( $cart_item['data']->subscription_period ) ) {
				$period = $cart_item['data']->subscription_period;
				break;
			} elseif ( WC_Subscriptions_Product::is_subscription( $item_id ) ) {
				$period = WC_Subscriptions_Product::get_period( $item_id );
				break;
			}
		}

		return apply_filters( 'woocommerce_subscriptions_cart_period', $period );
	}

	/**
	 * Gets the subscription period from the cart and returns it as an array (eg. array( 'month', 'day' ) )
	 * 
	 * @since 1.0
	 */
	public static function get_cart_subscription_interval() {
		global $woocommerce;

		foreach ( $woocommerce->cart->cart_contents as $cart_item ) {
			$item_id = empty( $cart_item['variation_id'] ) ? $cart_item['product_id'] : $cart_item['variation_id'];
			if ( WC_Subscriptions_Product::is_subscription( $item_id ) ) {
				$interval = WC_Subscriptions_Product::get_interval( $item_id );
				break;
			}
		}

		return apply_filters( 'woocommerce_subscriptions_cart_interval', $interval );
	}

	/**
	 * Gets the subscription length from the cart and returns it as an array (eg. array( 'month', 'day' ) )
	 * 
	 * @since 1.0
	 */
	public static function get_cart_subscription_length() {
		global $woocommerce;

		$length = 0;

		foreach ( $woocommerce->cart->cart_contents as $cart_item ) {
			$item_id = empty( $cart_item['variation_id'] ) ? $cart_item['product_id'] : $cart_item['variation_id'];
			if ( isset( $cart_item['data']->subscription_length ) ) {
				$length = $cart_item['data']->subscription_length;
				break;
			} elseif ( WC_Subscriptions_Product::is_subscription( $item_id ) ) {
				$length = WC_Subscriptions_Product::get_length( $item_id );
				break;
			}
		}

		return apply_filters( 'woocommerce_subscriptions_cart_length', $length );
	}

	/**
	 * Gets the subscription length from the cart and returns it as an array (eg. array( 'month', 'day' ) )
	 * 
	 * @since 1.1
	 */
	public static function get_cart_subscription_trial_length() {
		global $woocommerce;

		$trial_length = 0;

		foreach ( $woocommerce->cart->cart_contents as $cart_item ) {
			$item_id = empty( $cart_item['variation_id'] ) ? $cart_item['product_id'] : $cart_item['variation_id'];
			if ( isset( $cart_item['data']->subscription_trial_length ) ) {
				$trial_length = $cart_item['data']->subscription_trial_length;
				break;
			} elseif ( WC_Subscriptions_Product::is_subscription( $item_id ) ) {
				$trial_length = WC_Subscriptions_Product::get_trial_length( $item_id );
				break;
			}
		}

		return apply_filters( 'woocommerce_subscriptions_cart_trial_length', $trial_length );
	}

	/**
	 * Gets the subscription trial period from the cart and returns it as an array (eg. array( 'month', 'day' ) )
	 *
	 * @since 1.2
	 */
	public static function get_cart_subscription_trial_period() {
		global $woocommerce;

		$trial_period = '';

		// Get the original trial period
		foreach ( $woocommerce->cart->cart_contents as $cart_item ) {
			$item_id = empty( $cart_item['variation_id'] ) ? $cart_item['product_id'] : $cart_item['variation_id'];
			if ( isset( $cart_item['data']->subscription_trial_period ) ) {
				$trial_period = $cart_item['data']->subscription_trial_period;
				break;
			} elseif ( WC_Subscriptions_Product::is_subscription( $item_id ) ) {
				$trial_period = WC_Subscriptions_Product::get_trial_period( $item_id );
				break;
			}
		}

		return apply_filters( 'woocommerce_subscriptions_cart_trial_period', $trial_period );
	}

	/**
	 * Gets the subscription sign up fee for the cart and returns it
	 * 
	 * Currently short-circuits to return just the license fee of the first subscription, because only
	 * one subscription can be purchased at a time. 
	 * 
	 * @since 1.0
	 */
	public static function get_cart_subscription_sign_up_fee() {
		global $woocommerce;

		$sign_up_fee = 0;

		if ( ! self::cart_contains_subscription_renewal() ) {
			foreach ( $woocommerce->cart->cart_contents as $cart_item ) {
				$item_id = empty( $cart_item['variation_id'] ) ? $cart_item['product_id'] : $cart_item['variation_id'];
				if ( isset( $cart_item['data']->subscription_sign_up_fee ) ) {
					$sign_up_fee = $cart_item['data']->subscription_sign_up_fee;
					break;
				} elseif ( WC_Subscriptions_Product::is_subscription( $item_id ) ) {
					$sign_up_fee = WC_Subscriptions_Product::get_sign_up_fee( $item_id );
					break;
				}
			}
		}

		return apply_filters( 'woocommerce_subscriptions_cart_sign_up_fee', $sign_up_fee );
	}

	/* Total Getters */

	/**
	 * Get tax row amounts with or without compound taxes includes
	 *
	 * @return float price
	 */
	public static function get_recurring_cart_contents_total() {
		global $woocommerce;

		if ( ! $woocommerce->cart->prices_include_tax )
			$cart_contents_total = $woocommerce->cart->recurring_cart_contents_total;
		else
			$cart_contents_total = $woocommerce->cart->recurring_cart_contents_total + $woocommerce->cart->recurring_tax_total;

		return $cart_contents_total;
	}

	/**
	 * Returns the proportion of cart discount that is recurring for the product specified with $product_id
	 *
	 * @return double The total recurring item subtotal amount less tax for items in the cart.
	 * @since 1.2
	 */
	public static function get_recurring_subtotal_ex_tax() {
		global $woocommerce;

		return $woocommerce->cart->recurring_subtotal_ex_tax;
	}

	/**
	 * Returns the proportion of cart discount that is recurring for the product specified with $product_id
	 *
	 * @return double The total recurring item subtotal amount for items in the cart.
	 * @since 1.2
	 */
	public static function get_recurring_subtotal() {
		global $woocommerce;

		return $woocommerce->cart->recurring_subtotal;
	}

	/**
	 * Returns the proportion of cart discount that is recurring for the product specified with $product_id
	 *
	 * @return double The total recurring cart discount amount for items in the cart.
	 * @since 1.2
	 */
	public static function get_recurring_discount_cart() {
		global $woocommerce;

		return $woocommerce->cart->recurring_discount_cart;
	}

	/**
	 * Returns the proportion of total discount that is recurring for the product specified with $product_id
	 *
	 * @return double The total recurring discount amount for items in the cart.
	 * @since 1.2
	 */
	public static function get_recurring_discount_total() {
		global $woocommerce;

		return $woocommerce->cart->recurring_discount_total;
	}

	/**
	 * Returns the amount of shipping tax that is recurring. As shipping only applies
	 * to recurring payments, and only 1 subscription can be purchased at a time, 
	 * this is equal to @see WC_Cart::$shipping_tax_total
	 *
	 * @return double The total recurring shipping tax amount for items in the cart.
	 * @since 1.2
	 */
	public static function get_recurring_shipping_tax_total() {
		global $woocommerce;

		return $woocommerce->cart->shipping_tax_total;
	}

	/**
	 * Returns the recurring shipping price . As shipping only applies to recurring
	 * payments, and only 1 subscription can be purchased at a time, this is
	 * equal to @see WC_Cart::shipping_total
	 *
	 * @return double The total recurring shipping amount for items in the cart.
	 * @since 1.2
	 */
	public static function get_recurring_shipping_total() {
		global $woocommerce;

		return $woocommerce->cart->shipping_total;
	}

	/**
	 * Returns an array of taxes on an order with their recurring totals.
	 *
	 * @return array Array of tax_id => tax_amounts for items in the cart
	 * @since 1.2
	 */
	public static function get_recurring_taxes() {
		global $woocommerce;

		return $woocommerce->cart->recurring_taxes;
	}

	/**
	 * Get tax row amounts with or without compound taxes includes
	 *
	 * @return double The total recurring tax amount tax for items in the cart (maybe not including compound taxes)
	 * @since 1.2
	 */
	public static function get_recurring_taxes_total( $compound = true ) {
		global $woocommerce;

		$recurring_taxes_total = 0;

		foreach ( self::get_recurring_taxes() as $tax_id => $tax_amount ) {

			if ( ! $compound && $woocommerce->cart->tax->is_compound( $tax_id ) )
				continue;

			$recurring_taxes_total += $tax_amount;
		}

		return $recurring_taxes_total;
	}

	/**
	 * Returns the proportion of total tax on an order that is recurring for the product specified with $product_id
	 *
	 * @return double The total recurring tax amount tax for items in the cart.
	 * @since 1.2
	 */
	public static function get_recurring_total_tax() {
		global $woocommerce;

		return $woocommerce->cart->recurring_tax_total;
	}

	/**
	 * Returns the proportion of total before tax on an order that is recurring for the product specified with $product_id
	 *
	 * @return double The total recurring amount less tax for items in the cart.
	 * @since 1.2
	 */
	public static function get_recurring_total_ex_tax() {
		return self::get_recurring_total() - self::get_recurring_total_tax() - self::get_recurring_shipping_tax_total();
	}

	/**
	 * Returns the price per period for a subscription in an order.
	 * 
	 * @return double The total recurring amount for items in the cart.
	 * @since 1.2
	 */
	public static function get_recurring_total() {
		global $woocommerce;

		return $woocommerce->cart->recurring_total;
	}


	/* Session management */

	/**
	 * Get the recurring amounts values from the session
	 * 
	 * @since 1.0
	 */
	public static function get_cart_from_session() {
		global $woocommerce;

		if ( is_object( $woocommerce->cart ) ) {
			foreach ( self::get_recurring_totals_fields() as $field => $default_value ) {
				if ( isset( $woocommerce->session ) ) {
					$woocommerce->cart->{$field} = isset( $woocommerce->session->$field ) ? $woocommerce->session->$field : $default_value;
				} else { // WC 1.x
					$woocommerce->cart->{$field} = isset( $_SESSION[$field] ) ? $_SESSION[$field] : $default_value;
				}
			}
		}
	}

	/**
	 * Store the license fee cart values in the session
	 * 
	 * @since 1.0
	 */
	public static function set_session() {
		global $woocommerce;

		foreach ( self::get_recurring_totals_fields() as $field => $default_value ) {

			$value = isset( $woocommerce->cart->{$field} ) ? $woocommerce->cart->{$field} : $default_value;

			if ( isset( $woocommerce->session ) )
				$woocommerce->session->$field = $value;
			else // WC 1.x
				$_SESSION[$field] = $value;
		}
	}

	/**
	 * Reset the license fee fields in the current session
	 * 
	 * @since 1.0
	 */
	public static function reset() {
		global $woocommerce;

		foreach ( self::get_recurring_totals_fields() as $field => $default_value ) {
			$woocommerce->cart->{$field} = $default_value;

			if ( isset( $woocommerce->session ) )
				unset( $woocommerce->session->$field );
			else // WC 1.x
				unset( $_SESSION[$field] );

		}

	}

	/**
	 * Returns an array of the recurring total fields
	 * 
	 * @since 1.2
	 */
	public static function get_recurring_totals_fields() {
		return array(
			'recurring_cart_contents_total' => 0,
			'recurring_discount_cart'       => 0,
			'recurring_discount_total'      => 0,
			'recurring_subtotal'            => 0,
			'recurring_subtotal_ex_tax'     => 0,
			'recurring_taxes'               => array(),
			'recurring_tax_total'           => 0,
			'recurring_total'               => 0
		);
	}
	
	/**
	 * Restore renewal flag when cart is reset and modify Product object with
	 * renewal order related info
	 *
	 * @since 1.3
	 */
	public static function get_cart_item_from_session( $session_data, $values, $key ) {

		if ( isset( $values['subscription_renewal'] ) ) {

			$session_data['subscription_renewal'] = $values['subscription_renewal'];

			// Need to get the original order price, not the current price
			$original_order_id = $values['subscription_renewal']['original_order'];
			$order_items = WC_Subscriptions_Order::get_recurring_items( $original_order_id );
			$first_order_item = reset( $order_items );
			$price = $first_order_item['subscription_recurring_amount'];

			/*
			 * Modify the Cart $_product object. 
			 * All the cart calculations and cart/checkout/mini-cart displays will use this object.
			 * So by modifying it here, we take care of all those cases.
			 */
			$_product = $session_data['data'];
			$_product->price = $price;

			// Don't carry over any sign up fee
			$_product->subscription_sign_up_fee = $_product->product_custom_fields['_subscription_sign_up_fee'][0] = 0;

			// Make sure the original subscription terms perisist
			if ( 'parent' == $session_data['subscription_renewal']['role'] ) {

				$_product->subscription_price = $_product->product_custom_fields['_subscription_price'][0] = $price;
				$_product->subscription_period = $_product->product_custom_fields['_subscription_period'][0] = $first_order_item['subscription_period'];
				$_product->subscription_period_interval = $_product->product_custom_fields['_subscription_period_interval'][0] = $first_order_item['subscription_interval'];
				$_product->subscription_trial_period = $_product->product_custom_fields['_subscription_trial_period'][0] = $first_order_item['subscription_trial_period'];
				$_product->subscription_length = $_product->product_custom_fields['_subscription_length'][0] = $first_order_item['subscription_length'];

				// Never give a free trial period again
				$_product->subscription_trial_length = $_product->product_custom_fields['_subscription_trial_length'][0] = 0;
			}

			$title = sprintf( __( 'Renewal of "%s"', WC_Subscriptions::$text_domain ), $_product->get_title() );

			$_product->post->post_title = apply_filters( 'woocommerce_subscriptions_renewal_product_title', $title, $_product );
		}

		return $session_data;
	}

	/**
	 * For subscription renewal via cart, use original order discount
	 *
	 * @since 1.3
	 */	
	public static function before_calculate_totals( $cart ) {

		$cart_item = WC_Subscriptions_Cart::cart_contains_subscription_renewal();

		if ( $cart_item ) {

			$original_order_id = $cart_item['subscription_renewal']['original_order'];

			$cart->discount_cart = WC_Subscriptions_Order::get_meta( $original_order_id, '_order_recurring_discount_cart', 0 );
			$cart->discount_total = WC_Subscriptions_Order::get_meta( $original_order_id, '_order_recurring_discount_total', 0 );
		}
	}

	/**
	 * Store how much discount each coupon grants.
	 *
	 * @param mixed $code
	 * @param mixed $amount
	 * @return void
	 */
	public static function increase_coupon_discount_amount( $code, $amount ) {
		global $woocommerce;

		if ( empty( $woocommerce->cart->coupon_discount_amounts[ $code ] ) )
			$woocommerce->cart->coupon_discount_amounts[ $code ] = 0;

		$woocommerce->cart->coupon_discount_amounts[ $code ] += $amount;
	}

	/**
	 * Check whether the cart needs payment even if the order total is $0
	 *
	 * @param book $needs_payment The existing flag for whether the cart needs payment or not.
	 * @param WC_Cart $cart The WooCommerce cart object.
	 * @return bool
	 */
	public static function cart_needs_payment( $needs_payment, $cart ) {

		if ( self::cart_contains_subscription() && $cart->total == 0 && false === $needs_payment && $cart->recurring_total > 0 && 'yes' !== get_option( WC_Subscriptions_Admin::$option_prefix . '_turn_off_automatic_payments', 'no' ) )
			$needs_payment = true;

		return $needs_payment;
	}

	/* Deprecated */

	/**
	 * Returns the formatted subscription price string for an item
	 *
	 * @since 1.0
	 */
	public static function get_cart_item_price_html( $price_string, $cart_item ) {

		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2' );

		return $price_string;
	}

	/**
	 * Returns either the total if prices include tax because this doesn't include tax, or the 
	 * subtotal if prices don't includes tax, because this doesn't include tax. 
	 *
	 * @return string formatted price
	 *
	 * @since 1.0
	 */
	public static function get_cart_contents_total( $cart_contents_total ) {

		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2' );

		return $cart_contents_total;
	}

	/**
	 * Calculate totals for the license fees in the cart, based on @see WC_Cart::calculate_totals()
	 * 
	 * @since 1.0
	 */
	public static function calculate_sign_up_fee_totals() {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2' );
	}

	/**
	 * Function to apply discounts to a product and get the discounted price (before tax is applied)
	 * 
	 * @param mixed $values
	 * @param mixed $price
	 * @param bool $add_totals (default: false)
	 * @return float price
	 * @since 1.0
	 */
	public static function get_discounted_price( $values, $price, $add_totals = false ) {

		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2' );

		return $price;
	}

	/**
	 * Function to apply product discounts after tax
	 * 
	 * @param mixed $values
	 * @param mixed $price
	 * @since 1.0
	 */
	public static function apply_product_discounts_after_tax( $values, $price ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2' );
	}

	/**
	 * Function to apply cart discounts after tax
	 * 
	 * @since 1.0
	 */
	public static function apply_cart_discounts_after_tax() {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2' );
	}

	/**
	 * Get tax row amounts with or without compound taxes includes
	 *
	 * @return float price
	 */
	public static function get_sign_up_taxes_total( $compound = true ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2' );
		return 0;
	}

	public static function get_sign_up_fee_fields() {

		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2' );

		return array(
			'cart_contents_sign_up_fee_total',
			'cart_contents_sign_up_fee_count',
			'sign_up_fee_total',
			'sign_up_fee_subtotal',
			'sign_up_fee_subtotal_ex_tax',
			'sign_up_fee_tax_total',
			'sign_up_fee_taxes',
			'sign_up_fee_discount_cart',
			'sign_up_fee_discount_total'
		);
	}


	/* Ambigious getters replaced with explict get_formatted_x functions */

	/**
	 * Returns the subtotal for a cart item including the subscription period and duration details
	 *
	 * @since 1.0
	 */
	public static function get_product_subtotal( $product_subtotal, $product ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2', __CLASS__ .'::get_formatted_product_subtotal( $product_subtotal, $product )' );
		return self::get_formatted_product_subtotal( $product_subtotal, $product );
	}

	/**
	 * Returns a string with the cart discount and subscription period.
	 *
	 * @deprecated 1.2
	 * @since 1.0
	 */
	public static function get_discounts_before_tax( $discount, $cart ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2', __CLASS__ .'::get_formatted_discounts_before_tax( $discount )' );
		return self::get_formatted_discounts_before_tax( $total );
	}

	/**
	 * Gets the order discount amount - these are applied after tax
	 *
	 * @deprecated 1.2
	 * @since 1.0
	 */
	public static function get_discounts_after_tax( $discount, $cart ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2', __CLASS__ .'::get_formatted_discounts_after_tax( $discount )' );
		return self::get_formatted_discounts_after_tax( $total );
	}

	/**
	 * Includes the license fee subtotal in the subtotal displayed in the cart.
	 *
	 * @deprecated 1.2
	 * @since 1.0
	 */
	public static function get_cart_subtotal( $cart_subtotal, $compound, $cart ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2', __CLASS__ .'::get_formatted_cart_subtotal( $cart_subtotal, $compound, $cart )' );
		return self::get_formatted_cart_subtotal( $total, $compound, $cart );
	}

	/**
	 * Appends the cart subscription string to a cart total using the @see self::get_cart_subscription_string and then returns it. 
	 *
	 * @deprecated 1.2
	 * @since 1.0
	 */
	public static function get_total( $total ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2', __CLASS__ .'::get_formatted_total( $total )' );
		return self::get_formatted_total( $total );
	}

	/**
	 * Appends the cart subscription string to a cart total using the @see self::get_cart_subscription_string and then returns it. 
	 *
	 * @deprecated 1.2
	 * @since 1.0
	 */
	public static function get_total_ex_tax( $total_ex_tax ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2', __CLASS__ .'::get_formatted_total_ex_tax( $total_ex_tax )' );
		return self::get_formatted_total_ex_tax( $total_ex_tax );
	}

	/**
	 * For subscription renewal via cart, preivously adjust item price by original order discount
	 *
	 * No longer required as of 1.3.5 as totals are calculated correctly internally.
	 *
	 * @since 1.3
	 */
	public static function get_discounted_price_for_renewal( $price, $values, $cart ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.3.6' );
		return $price;
	}
}

WC_Subscriptions_Cart::init();
