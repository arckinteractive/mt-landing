<?php
/**
 * Subscription Product Class
 *
 * The subscription product class is an extension of the simple product class.
 *
 * @class 		WC_Product_Subscription
 * @package		WooCommerce Subscriptions
 * @subpackage	WC_Product_Subscription
 * @category	Class
 * @since		1.3
 * 
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( class_exists( 'WC_Product_Simple' ) ) : // WC 1.x compatibility

class WC_Product_Subscription extends WC_Product_Simple {

	var $subscription_price;

	var $subscription_period;

	var $subscription_period_interval;

	var $subscription_length;

	var $subscription_trial_length;

	var $subscription_trial_period;

	var $subscription_sign_up_fee;

	/**
	 * Create a simple subscription product object.
	 *
	 * @access public
	 * @param mixed $product
	 */
	public function __construct( $product ) {
		parent::__construct( $product );
		$this->product_type = 'subscription';

		// Load all meta fields
		$this->product_custom_fields = get_post_meta( $this->id );

		// Convert selected subscription meta fields for easy access
		if ( ! empty( $this->product_custom_fields['_subscription_price'][0] ) )
			$this->subscription_price = $this->product_custom_fields['_subscription_price'][0];

		if ( ! empty( $this->product_custom_fields['_subscription_period'][0] ) )
			$this->subscription_period = $this->product_custom_fields['_subscription_period'][0];

		if ( ! empty( $this->product_custom_fields['_subscription_period_interval'][0] ) )
			$this->subscription_period_interval = $this->product_custom_fields['_subscription_period_interval'][0];

		if ( ! empty( $this->product_custom_fields['_subscription_length'][0] ) )
			$this->subscription_length = $this->product_custom_fields['_subscription_length'][0];

		if ( ! empty( $this->product_custom_fields['_subscription_trial_length'][0] ) )
			$this->subscription_trial_length = $this->product_custom_fields['_subscription_trial_length'][0];

		if ( ! empty( $this->product_custom_fields['_subscription_trial_period'][0] ) )
			$this->subscription_trial_period = $this->product_custom_fields['_subscription_trial_period'][0];

		if ( ! empty( $this->product_custom_fields['_subscription_sign_up_fee'][0] ) )
			$this->subscription_sign_up_fee = $this->product_custom_fields['_subscription_sign_up_fee'][0];

		$this->limit_subscriptions = ( ! isset( $this->product_custom_fields['_subscription_limit'][0] ) ) ? 'no' : $this->product_custom_fields['_subscription_limit'][0];

	}

	/**
	 * Returns the sign up fee (including tax) by filtering the products price used in
	 * @see WC_Product::get_price_including_tax( $qty )
	 *
	 * @return string
	 */
	public function get_sign_up_fee_including_tax( $qty = 1 ) {

		add_filter( 'woocommerce_get_price', array( &$this, 'get_sign_up_fee' ), 100, 0 );

		$sign_up_fee_including_tax = parent::get_price_including_tax( $qty );

		remove_filter( 'woocommerce_get_price', array( &$this, 'get_sign_up_fee' ), 100, 0 );

		return $sign_up_fee_including_tax;
	}

	/**
	 * Returns the sign up fee (excluding tax) by filtering the products price used in
	 * @see WC_Product::get_price_excluding_tax( $qty )
	 *
	 * @return string
	 */
	public function get_sign_up_fee_excluding_tax( $qty = 1 ) {

		add_filter( 'woocommerce_get_price', array( &$this, 'get_sign_up_fee' ), 100, 0 );

		$sign_up_fee_excluding_tax = parent::get_price_excluding_tax( $qty );

		remove_filter( 'woocommerce_get_price', array( &$this, 'get_sign_up_fee' ), 100, 0 );

		return $sign_up_fee_excluding_tax;
	}

	/**
	 * Return the license fee for this product
	 *
	 * @return string
	 */
	public function get_sign_up_fee() {
		return WC_Subscriptions_Product::get_sign_up_fee( $this );
	}

	/**
	 * Checks if the store manager has requested the current product be limited to one purchase
	 * per customer, and if so, checks whether the customer already has an active subscription to
	 * the product.
	 *
	 * @access public
	 * @return bool
	 */
	function is_purchasable() {

		$purchasable = parent::is_purchasable();

		if ( true === $purchasable && 'yes' == $this->limit_subscriptions )
			if ( WC_Subscriptions_Manager::user_has_subscription( 0, $this->id, 'active' ) )
				$purchasable = false;

		return apply_filters( 'woocommerce_subscription_is_purchasable', $purchasable, $this );
	}
}

endif;