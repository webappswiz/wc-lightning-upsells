<?php

class Wclu_Cart_Handler extends Wclu_Core {

	/**
	 * Register Wordpress actions and filters related to the cart handling
	 */
	public function __construct() {

		add_filter('woocommerce_add_cart_item_data', array($this, 'apply_upsell_for_cart_item'), 4, 10);

		add_action('woocommerce_add_order_item_meta', array($this, 'add_values_to_order_item_meta'), 1, 2);
		add_filter('woocommerce_get_cart_item_from_session', array($this, 'get_cart_items_from_session'), 1, 3);
		add_action('woocommerce_before_calculate_totals', array($this, 'apply_custom_upsell_price_to_product'), 1, 1);
	}

	/**
	 * Callback for "woocommerce_add_cart_item_data" filter which is applied in WC_Cart->add_to_cart()
	 * 
	 * We check for the 'lightning' value in POST array, and if it contains an ID of a valid upsell, then apply it.
	 * 
	 * @param array $cart_item_data
	 * @param int $product_id
	 * @param int $variation_id
	 * @param int $quantity
	 */
	public function apply_upsell_for_cart_item($cart_item_data, $product_id, $variation_id, $quantity) {

		$upsell_id = filter_input(INPUT_GET, 'lightning', FILTER_VALIDATE_INT);
		$upsell = Wclu_Db_Search::find_upsell_by_id(intval($upsell_id));

		if ( $upsell ) { // should be Wclu_Upsell_Offer or false
			
			$custom_upsell_data = array(
					'upsell_id' => $upsell_id,
					'upsell_product_price' => $upsell->calculate_offered_price()
			);

			$cart_item_data = array_merge($cart_item_data, $custom_upsell_data);

			self::wc_log('WCLU - add_item_metadata', ['cart_item_data' => $cart_item_data]);

			Wclu_Cookie_Handler::save_accept_for_upsell($upsell_id);
			$upsell->record_statistics_event( self::EVENT_ACCEPT );
		}

		return $cart_item_data;
	}

	public function get_cart_items_from_session($item, $values, $key) {

		if (array_key_exists('upsell_id', $values)) {
			$item['upsell_id'] = $values['upsell_id'];
		}

		if (array_key_exists('upsell_product_price', $values)) {
			$item['upsell_product_price'] = $values['upsell_product_price'];
		}

		//self::wc_log('WCLU - get_cart_items_from_session', [ 'item' => $item ] );

		return $item;
	}

	public function add_values_to_order_item_meta($item_id, $values) {

		wc_add_order_item_meta($item_id, '_upsell_id', $values['upsell_id']);
		wc_add_order_item_meta($item_id, '_upsell_product_price', $values['upsell_product_price']);

		self::wc_log('WCLU - add_values_to_order_item_meta', ['values' => $values]);
	}

	public function apply_custom_upsell_price_to_product($cart_object) {

		foreach ($cart_object->cart_contents as $cart_item_key => $value) {

			if (isset($value['upsell_product_price'])) {
				$value['data']->set_price($value['upsell_product_price']);
			}
		}
	}
}
