<?php
/**
 * Subscriptions Address Class
 *
 * Hooks into WooCommerce to handle editing addresses for subscriptions (by editing the original order for the subscription)
 *
 * @package		WooCommerce Subscriptions
 * @subpackage	WC_Subscriptions_Addresses
 * @category	Class
 * @author		Brent Shepherd
 * @since		1.3
 */
class WC_Subscriptions_Addresses {

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 *
	 * @since 1.3
	 */
	public static function init() {

		add_filter( 'woocommerce_my_account_my_subscriptions_actions', __CLASS__ . '::add_edit_address_subscription_action', 10, 2 );

		add_action( 'woocommerce_after_template_part', __CLASS__ . '::maybe_add_edit_address_checkbox', 10 );

		add_action( 'woocommerce_customer_save_address', __CLASS__ . '::maybe_update_subscription_addresses', 10, 3 );
	}

	/**
	 * Add a "Change Shipping Address" button to the "My Subscriptions" table for those subscriptions
	 * which require shipping.
	 *
	 * @param $all_actions array The $subscription_key => $actions array with all actions that will be displayed for a subscription on the "My Subscriptions" table
	 * @param $subscriptions array All of a given users subscriptions that will be displayed on the "My Subscriptions" table
	 * @since 1.3
	 */
	public static function add_edit_address_subscription_action( $all_actions, $subscriptions ) {

		foreach ( $all_actions as $subscription_key => $actions ) {

			$order = new WC_Order( $subscriptions[$subscription_key]['order_id'] );

			$needs_shipping = false;

			foreach( $order->get_items() as $item ) {

				if ( $item['product_id'] !== $subscriptions[$subscription_key]['product_id'] )
					continue;

				$product = $order->get_product_from_item( $item );

				if ( ! is_object( $product ) ) // In case a product has been deleted
					continue;

				if ( $product->needs_shipping() )
					$needs_shipping = true;
			}

			if ( $needs_shipping && in_array( $subscriptions[$subscription_key]['status'], array( 'active', 'on-hold' ) ) ) {

				$all_actions[$subscription_key] = array( 'change_address' => array(
					'url'  => add_query_arg( array( 'address' => 'shipping', 'subscription' => $subscription_key ), get_permalink( woocommerce_get_page_id( 'edit_address' ) ) ),
					'name' => __( 'Change Address', WC_Subscriptions::$text_domain ),
					)
				) + $all_actions[$subscription_key];

			}

		}

		return $all_actions;
	}

	/**
	 * Outputs the necessary markup on the "My Account" > "Edit Address" page for editing a single subscription's
	 * address or to check if the customer wants to update the addresses for all of their subscriptions.
	 *
	 * If editing their default shipping address, this function adds a checkbox to the to allow subscribers to
	 * also update the address on their active subscriptions. If editing a single subscription's address, the
	 * subscription key is added as a hidden field.
	 *
	 * @param $template_name string The name of the template that is being loaded
	 * @since 1.3
	 */
	public static function maybe_add_edit_address_checkbox( $template_name ) {

		if ( 'myaccount/form-edit-address.php' === $template_name ) {

			if ( isset( $_GET['subscription'] ) ) {

				printf( __( '%sBoth the shipping address used for the subscription and your default shipping address for future purchases will be updated.%s', WC_Subscriptions::$text_domain ), '<p>', '</p>' );

				echo '<input type="hidden" name="update_subscription_address" value="' . esc_attr( $_GET['subscription'] ) . '" id="update_subscription_address" />';

			} elseif ( isset( $_GET['address'] ) && WC_Subscriptions_Manager::user_has_subscription() ) {

				$address_type = esc_attr( $_GET['address'] );

				$label = sprintf( __( 'Update the %s address used for <strong>all</strong> of my active subscriptions', WC_Subscriptions::$text_domain ), $address_type );

				woocommerce_form_field( 'update_all_subscriptions_addresses', array(
					'type'  => 'checkbox',
					'class' => array( 'form-row-wide' ),
					'label' => $label,
					)
				);
			}
// No hooks within the "Edit Address" form so need to move it client side
?>
<script type="text/javascript">
jQuery(document).ready(function($){
	var $field;

	if($('#update_all_subscriptions_addresses_field').length > 0)
		$field = $('#update_all_subscriptions_addresses_field');
	else
		$field = $('#update_subscription_address');

	$field.insertBefore($('input[name="save_address"]').parent());
});
</script>
<?php
		}
	}

	/**
	 * When a subscriber's billing or shipping address is successfully updated, check if the subscriber
	 * has also requested to update the addresses on existing subscriptions and if so, go ahead and update
	 * the addresses on the initial order for each subscription.
	 *
	 * @param $user_id int The ID of a user who own's the subscription (and address)
	 * @since 1.3
	 */
	public static function maybe_update_subscription_addresses( $user_id ) {
		global $woocommerce;

		if ( ! WC_Subscriptions_Manager::user_has_subscription( $user_id ) || ! isset( $_GET['address'] ) )
			return;

		$load_address = ( isset( $_GET[ 'address' ] ) ) ? esc_attr( $_GET[ 'address' ] ) : '';
		$load_address = ( $load_address == 'billing' || $load_address == 'shipping' ) ? $load_address : '';

		$address_fields = $woocommerce->countries->get_address_fields( esc_attr( $_POST[ $load_address . '_country' ] ), $load_address . '_' );

		if ( isset( $_POST['update_all_subscriptions_addresses'] ) ) {

			$users_subscriptions = WC_Subscriptions_Manager::get_users_subscriptions( $user_id );

			foreach ( $users_subscriptions as $subscription )
				self::maybe_update_order_address( $subscription, $address_fields );

		} elseif ( isset( $_POST['update_subscription_address'] ) ) {

			$subscription = WC_Subscriptions_Manager::get_users_subscription( $user_id, $_POST['update_subscription_address'] );

			// Update the address only if the user actually owns the subscription
			if ( ! empty( $subscription ) )
				self::maybe_update_order_address( $subscription, $address_fields );

		}
	}

	/**
	 * Update the address fields on an order
	 *
	 * @param $subscription Array A WooCommerce Subscription array
	 * @param $address_fields array Locale aware address fields of the form returned by WC_Countries->get_address_fields() for a given country
	 * @since 1.3
	 */
	public static function maybe_update_order_address( $subscription, $address_fields ) {
		global $woocommerce;

		if ( in_array( $subscription['status'], array( 'active', 'on-hold' ) ) ) {
			foreach ( $address_fields as $key => $field ) {
				update_post_meta( $subscription['order_id'], '_' . $key, woocommerce_clean( $_POST[$key] ) );
			}
		}
	}
}

WC_Subscriptions_Addresses::init();
