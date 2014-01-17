<?php
/**
 * Subscription Product Variation Class
 *
 * The subscription product variation class extends the WC_Product_Variation product class
 * to create subscription product variations.
 *
 * @class 		WC_Product_Subscription
 * @package		WooCommerce Subscriptions
 * @category	Class
 * @since		1.3
 * 
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( class_exists( 'WC_Product_Variation' ) ) : // WC 1.x compatibility

class WC_Product_Subscription_Variation extends WC_Product_Variation {

	var $subscription_price;

	var $subscription_period;

	var $subscription_period_interval;

	var $subscription_length;

	var $subscription_trial_length;

	var $subscription_trial_period;

	var $subscription_sign_up_fee;

	var $product_type;

	/**
	 * Create a simple subscription product object.
	 *
	 * @access public
	 * @param mixed $product
	 */
	public function __construct( $product, $args = array() ) {

		parent::__construct( $product, $args = array() );

		$this->product_type = 'subscription_variation';

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
	}

	/**
	 * Get variation price HTML. Prices are not inherited from parents.
	 *
	 * @return string containing the formatted price
	 */
	public function get_price_html( $price = '' ) {

		$price = parent::get_price_html( $price = '' );

		return WC_Subscriptions_Product::get_price_string( $this, array( 'price' => $price )  );
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
	 * Checks if the variable product this variation belongs to is purchasable.
	 *
	 * @access public
	 * @return bool
	 */
	function is_purchasable() {

		$purchasable = $this->parent->is_purchasable();

		return apply_filters( 'woocommerce_subscription_variation_is_purchasable', $purchasable, $this );
	}
}

endif;