<?php
/**
 * Subscriptions Email Class
 * 
 * Modifies the base WooCommerce email class and extends it to send subscription emails.
 *
 * @package		WooCommerce Subscriptions
 * @subpackage	WC_Subscriptions_Email
 * @category	Class
 * @author		Brent Shepherd
 */
class WC_Subscriptions_Email {

	private static $woocommerce_email;

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 * 
	 * @since 1.0
	 */
	public static function init() {
		add_action( 'woocommerce_email', __CLASS__ . '::set_email', 10, 1 );
	}

	/**
	 * Sets up the internal $woocommerce_email property for this class.
	 * 
	 * @since 1.0
	 */
	public static function set_email( $wc_email ) {
		self::$woocommerce_email = $wc_email;
	}
}

WC_Subscriptions_Email::init();
