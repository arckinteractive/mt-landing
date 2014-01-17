<?php
/**
 * Variable Subscription Product Class
 *
 * This class extends the WC Variable product class to create variable products with recurring payments.
 *
 * @class 		WC_Product_Variable_Subscription
 * @package		WooCommerce Subscriptions
 * @category	Class
 * @since		1.3
 * 
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( class_exists( 'WC_Product_Simple' ) ) : // WC 1.x compatibility

class WC_Product_Variable_Subscription extends WC_Product_Variable {

	var $subscription_price;

	var $subscription_period;

	var $max_variation_period;

	var $subscription_period_interval;

	var $max_variation_period_interval;

	var $product_type;

	/**
	 * Create a simple subscription product object.
	 *
	 * @access public
	 * @param mixed $product
	 */
	public function __construct( $product ) {

		parent::__construct( $product );

		$this->product_type = 'variable-subscription';

		// Load all meta fields
		$this->product_custom_fields = get_post_meta( $this->id );

		// Convert selected subscription meta fields for easy access
		if ( ! empty( $this->product_custom_fields['_subscription_price'][0] ) )
			$this->subscription_price = $this->product_custom_fields['_subscription_price'][0];

		if ( ! empty( $this->product_custom_fields['_subscription_sign_up_fee'][0] ) )
			$this->subscription_sign_up_fee = $this->product_custom_fields['_subscription_sign_up_fee'][0];

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

		$this->limit_subscriptions = ( ! isset( $this->product_custom_fields['_subscription_limit'][0] ) ) ? 'no' : $this->product_custom_fields['_subscription_limit'][0];

		add_filter( 'woocommerce_add_to_cart_handler', array( &$this, 'add_to_cart_handler' ), 10, 2 );
	}


	/**
	 * Sync variable product prices with the childs lowest/highest prices.
	 *
	 * @access public
	 * @return void
	 */
	public function variable_product_sync() {
		global $woocommerce;

		parent::variable_product_sync();

		$children = get_posts( array(
			'post_parent' 	=> $this->id,
			'posts_per_page'=> -1,
			'post_type' 	=> 'product_variation',
			'fields' 		=> 'ids',
			'post_status'	=> 'publish'
		));

		$lowest_initial_amount = $highest_initial_amount = $lowest_price = $highest_price = '';
		$shortest_initial_period = $longest_initial_period = $shortest_trial_period = $longest_trial_period = $shortest_trial_length = $longest_trial_length = '';
		$longest_initial_interval = $shortest_initial_interval = $variable_subscription_period = $variable_subscription_period_interval = '';

		if ( $children ) {

			foreach ( $children as $child ) {

				$is_max = $is_min = false;

				// WC has already determined the correct price which accounts for sale price
				$child_price = get_post_meta( $child, '_price', true );

				$child_billing_period    = get_post_meta( $child, '_subscription_period', true );
				$child_billing_interval  = get_post_meta( $child, '_subscription_period_interval', true );
				$child_sign_up_fee       = get_post_meta( $child, '_subscription_sign_up_fee', true );
				$child_free_trial_length = get_post_meta( $child, '_subscription_trial_length', true );
				$child_free_trial_period = get_post_meta( $child, '_subscription_trial_period', true );

				if ( $child_price === '' && $child_sign_up_fee === '' )
					continue;

				$child_price       = ( $child_price === '' ) ? 0 : $child_price;
				$child_sign_up_fee = ( $child_sign_up_fee === '' ) ? 0 : $child_sign_up_fee;

				$has_free_trial = ( $child_free_trial_length !== '' && $child_free_trial_length > 0 ) ? true : false;

				// Determine some recurring price flags
				$is_lowest_price     = ( $child_price < $lowest_price || '' === $lowest_price ) ? true : false;
				$is_longest_period   = ( $child_billing_period === WC_Subscriptions::get_longest_period( $variable_subscription_period, $child_billing_period ) ) ? true : false;
				$is_longest_interval = ( $child_billing_interval >= $variable_subscription_period_interval || '' === $variable_subscription_period_interval ) ? true : false;

				// Find the amount the subscriber will have to pay up-front
				if ( $has_free_trial ) {
					$initial_amount   = $child_sign_up_fee;
					$initial_period   = $child_free_trial_period;
					$initial_interval = $child_free_trial_length;
				} else {
					$initial_amount   = $child_price + $child_sign_up_fee;
					$initial_period   = $child_billing_period;
					$initial_interval = $child_billing_interval;
				}

				// We have a free trial & no license fee, so need to choose the longest free trial (and maybe the shortest)
				if ( $has_free_trial && $child_sign_up_fee == 0 ) {

					// First variation
					if ( '' === $longest_trial_period ) {

						$is_min = true;

					// If two variations have the same free trial, choose the variation with the lowest recurring price for the longest period
					} elseif ( $variable_subscription_trial_period === $child_free_trial_period && $child_free_trial_length === $variable_subscription_trial_length ) {

						// If the variation has the lowest recurring price, it's the cheapest
						if ( $is_lowest_price ) {

							$is_min = true;

						// When current variation's free trial is the same as the lowest, it's the cheaper if it has a longer billing schedule
						} elseif ( $child_price === $lowest_price ) {

							if ( $is_longest_period && $is_longest_interval ) {

								$is_min = true;

							// Longest with a new billing period
							} elseif ( $is_longest_period && $child_billing_period !== $variable_subscription_trial_period ) {

								$is_min = true;

							}
						}

					// Otherwise the cheapest variation is the one with the longer trial
					} elseif ( $variable_subscription_trial_period === $child_free_trial_period ) {

						$is_min = ( $child_free_trial_length > $variable_subscription_trial_length ) ? true : false;

					// Otherwise just a longer trial period (that isn't equal to the longest period)
					} elseif ( $child_free_trial_period === WC_Subscriptions::get_longest_period( $longest_trial_period, $child_free_trial_period ) ) {

						$is_min = true;

					}

					if ( $is_min ) {
						$longest_trial_period = $child_free_trial_period;
						$longest_trial_length = $child_free_trial_length;
					}

					// If the current cheapest variation is also free then the shortest trial period is the most expensive
					if ( 0 == $lowest_price || '' === $lowest_price ) {

						if ( '' === $shortest_trial_period ) {

							$is_max = true;

						// Need to check trial length
						} elseif ( $shortest_trial_period === $child_free_trial_period ) {

							$is_max = ( $child_free_trial_length < $shortest_trial_length ) ? true : false;

						// Need to find shortest period
						} elseif ( $child_free_trial_period === WC_Subscriptions::get_shortest_period( $shortest_trial_period, $child_free_trial_period ) ) {

							$is_max = true;

						}

						if ( $is_max ) {
							$shortest_trial_period = $child_free_trial_period;
							$shortest_trial_length = $child_free_trial_length;
						}
					}

				} else {

					$longest_initial_period  = WC_Subscriptions::get_longest_period( $longest_initial_period, $initial_period );
					$shortest_initial_period = WC_Subscriptions::get_shortest_period( $shortest_initial_period, $initial_period );

					$is_lowest_initial           = ( $initial_amount < $lowest_initial_amount || '' === $lowest_initial_amount ) ? true : false;
					$is_longest_initial_period   = ( $initial_period === $longest_initial_period ) ? true : false;
					$is_longest_initial_interval = ( $initial_interval > $longest_initial_interval || '' === $longest_initial_interval ) ? true : false;

					$is_highest_initial   = ( $initial_amount > $highest_initial_amount || '' === $highest_initial_amount ) ? true : false;
					$is_shortest_period   = ( $initial_period === $shortest_initial_period || '' === $shortest_initial_period ) ? true : false;
					$is_shortest_interval = ( $initial_interval < $shortest_initial_interval || '' === $shortest_initial_interval ) ? true : false;

					// If we're not dealing with the shortest or longest billing period, then ignore this variation
					if ( ! $is_lowest_initial && $initial_amount !== $lowest_initial_amount )
						continue;

					// If the variation has the lowest price, it's the cheapest
					if ( $is_lowest_initial ) {

						$is_min = true;

					// When current variation's price is the same as the lowest, it's the cheapest only if it has a longer billing schedule
					} elseif ( $initial_amount === $lowest_initial_amount ) {

						if ( $is_longest_initial_period && $is_longest_initial_interval ) {

							$is_min = true;

						// Longest with a new billing period
						} elseif ( $is_longest_initial_period && $initial_period !== $longest_initial_period ) {

							$is_min = true;

						// If the initial amount & billing periods are the same, and there is a free trial, find the cheapest recurrig price
						} elseif ( $has_free_trial && $initial_period == $longest_initial_period && $initial_interval == $longest_initial_interval ) {

							// If the variation has the lowest recurring price, it's the cheapest
							if ( $is_lowest_price ) {

								$is_min = true;

							// When current variation's price is the same as the lowest, it's the cheapest only if it has a longer billing schedule
							} elseif ( $child_price === $lowest_price ) {

								if ( $is_longest_period && $is_longest_interval ) {

									$is_min = true;

								// Longest with a new billing period
								} elseif ( $is_longest_period && $child_billing_period !== $variable_subscription_period ) {

									$is_min = true;

								}
							}
						}
					}

					// If we have the highest price for the shortest period, we might have the maximum variation
					if ( $is_highest_initial && $is_shortest_period && $is_shortest_interval ) {

						$is_max = true;

					// But only if its for the shortest billing period
					} elseif ( $child_price === $highest_price ) {

						if ( $is_shortest_period && $is_shortest_interval )
							$is_max = true;
						elseif ( $is_shortest_period )
							$is_max = true;

					}
				}

				// If it's the min subscription terms
				if ( $is_min ) {

					$lowest_price          = $child_price;
					$lowest_regular_price  = get_post_meta( $child, '_regular_price', true );
					$lowest_sale_price     = get_post_meta( $child, '_sale_price', true );

					$lowest_regular_price = ( '' === $lowest_regular_price ) ? 0 : $lowest_regular_price;
					$lowest_sale_price    = ( '' === $lowest_sale_price ) ? 0 : $lowest_sale_price;

					$lowest_initial_amount    = $initial_amount;
					$longest_initial_period   = $initial_period;
					$longest_initial_interval = $initial_interval;

					$variable_subscription_sign_up_fee     = $child_sign_up_fee;
					$variable_subscription_period          = $child_billing_period;
					$variable_subscription_period_interval = $child_billing_interval;
					$variable_subscription_trial_length    = $child_free_trial_length;
					$variable_subscription_trial_period    = $child_free_trial_period;
					$variable_subscription_length          = get_post_meta( $child, '_subscription_length', true );
				}

				if ( $is_max ) {
					$highest_price          = $child_price;
					$highest_regular_price  = get_post_meta( $child, '_regular_price', true );
					$highest_sale_price     = get_post_meta( $child, '_sale_price', true );
					$highest_initial_amount = $initial_amount;

					$highest_regular_price = ( '' === $highest_regular_price ) ? 0 : $highest_regular_price;
					$highest_sale_price    = ( '' === $highest_sale_price ) ? 0 : $highest_sale_price;

					$max_subscription_period          = $child_billing_period;
					$max_subscription_period_interval = $child_billing_interval;
				}
			}

			update_post_meta( $this->id, '_price', $lowest_price );
			update_post_meta( $this->id, '_min_variation_price', $lowest_price );
			update_post_meta( $this->id, '_max_variation_price', $highest_price );
			update_post_meta( $this->id, '_min_variation_regular_price', $lowest_regular_price );
			update_post_meta( $this->id, '_max_variation_regular_price', $highest_regular_price );
			update_post_meta( $this->id, '_min_variation_sale_price', $lowest_sale_price );
			update_post_meta( $this->id, '_max_variation_sale_price', $highest_sale_price );

			update_post_meta( $this->id, '_min_variation_period', $variable_subscription_period );
			update_post_meta( $this->id, '_max_variation_period', $variable_subscription_period_interval );
			update_post_meta( $this->id, '_min_variation_period_interval', $max_subscription_period );
			update_post_meta( $this->id, '_max_variation_period_interval', $max_subscription_period_interval );

			update_post_meta( $this->id, '_subscription_price', $lowest_price );
			update_post_meta( $this->id, '_subscription_sign_up_fee', $variable_subscription_sign_up_fee );
			update_post_meta( $this->id, '_subscription_period', $variable_subscription_period );
			update_post_meta( $this->id, '_subscription_period_interval', $variable_subscription_period_interval );
			update_post_meta( $this->id, '_subscription_trial_period', $variable_subscription_trial_period );
			update_post_meta( $this->id, '_subscription_trial_length', $variable_subscription_trial_length );
			update_post_meta( $this->id, '_subscription_length', $variable_subscription_length );

			$this->subscription_price           = $lowest_price;
			$this->subscription_sign_up_fee     = $variable_subscription_sign_up_fee;
			$this->subscription_period          = $variable_subscription_period;
			$this->subscription_period_interval = $variable_subscription_period_interval;
			$this->subscription_trial_period    = $variable_subscription_trial_period;
			$this->subscription_trial_length    = $variable_subscription_trial_length;
			$this->subscription_length          = $variable_subscription_length;

			$woocommerce->clear_product_transients( $this->id );

		} else { // No variations yet

			$this->subscription_price           = 0;
			$this->subscription_sign_up_fee     = 0;
			$this->subscription_period          = 'day';
			$this->subscription_period_interval = 1;
			$this->subscription_trial_period    = 'day';
			$this->subscription_trial_length    = 1;
			$this->subscription_length          = 0;

		}

	}

	/**
	 * Returns the price in html format.
	 *
	 * @access public
	 * @param string $price (default: '')
	 * @return string
	 */
	public function get_price_html( $price = '' ) {

		$price = parent::get_price_html( $price );

		if ( ! isset( $this->subscription_period ) || ! isset( $this->subscription_period_interval ) || ! isset( $this->max_variation_period ) || ! isset( $this->max_variation_period_interval ) )
			$this->variable_product_sync();

		// Make sure the price contains "From:" when billing schedule differs between variations
		if ( false === strpos( $price, $this->get_price_html_from_text() ) ) {
			if ( $this->subscription_period !== $this->max_variation_period )
				$price = $this->get_price_html_from_text() . $price;
			elseif ( $this->subscription_period_interval !== $this->max_variation_period_interval )
				$price = $this->get_price_html_from_text() . $price;
		}

		$price = WC_Subscriptions_Product::get_price_html( $price, $this );

		return apply_filters( 'woocommerce_variable_subscription_price_html', $price, $this );
	}

	/**
	 * get_child function.
	 *
	 * @access public
	 * @param mixed $child_id
	 * @return object WC_Product_Subscription or WC_Product_Subscription_Variation
	 */
	public function get_child( $child_id ) {
		return get_product( $child_id, array(
			'product_type' => 'Subscription_Variation',
			'parent_id'    => $this->id,
			'parent'       => $this,
			) );
	}

	/**
	 *
	 * @param $product_type string A string representation of a product type
	 * @return $product object Any WC_Product_* object
	 */
	public function add_to_cart_handler( $handler, $product ) {

		if ( 'variable-subscription' === $handler )
			$handler = 'variable';

		return $handler;
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