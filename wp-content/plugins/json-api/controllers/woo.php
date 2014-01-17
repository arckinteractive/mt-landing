<?php

/*
Controller name: Woo
Controller description: Some methods to pull Woocommerce data via the json api
*/

class JSON_API_Woo_Controller {

    function __construct() {
        if (!$this->authorized()) {
            return false;
        }
    }

    /**
     * Find an order by the Stripe charge ID
     *
     * Woo buries the charge ID inside a post comment rather than storing
     * it as a meta property. 
     *
     * @access public
     * @return array
     */
    public function get_order_by_charge_id() {

        global $json_api, $wpdb, $woocommerce;

        $charge_id  = esc_sql($json_api->query->charge_id);
        $order_date = esc_sql($json_api->query->order_date);

        if (!$charge_id || !$order_date) {
            $json_api->error("charge_id and order_date are required parameters."); 
            return false;
        }

        $query = "SELECT {$wpdb->comments}.comment_post_ID 
                  FROM {$wpdb->comments}, {$wpdb->posts}
                  WHERE {$wpdb->comments}.comment_type = 'order_note' 
                     AND {$wpdb->comments}.comment_content like '%{$charge_id}%'
                     AND {$wpdb->comments}.comment_post_ID = {$wpdb->posts}.ID
                     AND {$wpdb->posts}.post_date > DATE_SUB('{$order_date}', INTERVAL 5 MINUTE);
        ";

        $result = $wpdb->get_results($query, OBJECT);

	$order = new WC_Order( $result[0]->comment_post_ID );

        $items = $order->get_items();

    	return array(
      	    'order' => $order,
            'items' => $items
    	);
    }
    
    public function get_order() {

        global $json_api, $woocommerce;

	$order = new WC_Order( $json_api->query->order_id );

    	return array(
      	    'order' => $order
    	);
    }

   /**
     * Return an array of items/products within this order.
     *
     * @access public
     * @return array
     */
    public function get_order_items() {

        global $woocommerce;

        $order = $this->get_order();

        $items = $order['order']->get_items();

    	return array(
      	    'items' => $items
    	);
    }

    /**
     * Authorize request by IP and key
     *
     * @access private
     * @return bool
     */
    private function authorized() {

        global $json_api;

        $allowed_ips = array_map('trim', explode(',', get_option('json_api_woo_ip')));  

        if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
            $json_api->error("Source IP {$_SERVER['REMOTE_ADDR']} is not permitted."); 
            return false;
        }

        if ($json_api->query->apikey != get_option('json_api_woo_apikey')) {
            $json_api->error("Invalid API key.");
            return false;
        }

        return true;
    }

}

?>
