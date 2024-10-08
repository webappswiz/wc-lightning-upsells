<?php

class Wclu_Cart_Handler extends Wclu_Core {

	/**
	 * Register Wordpress actions and filters related to the cart handling
	 */
	public function __construct() {

		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'apply_upsell_for_cart_item' ), 4, 10 );

		add_action( 'woocommerce_add_order_item_meta', array( $this, 'add_values_to_order_item_meta' ), 1, 2 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_items_from_session' ), 1, 3 );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'apply_custom_upsell_price_to_product' ), 1, 1 );
		
		// TODO: add option to choose target order status in plugin settings
		add_action( 'woocommerce_order_status_completed', array( $this, 'maybe_update_upsell_statistics' ), 10, 3 );
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

			// save in customer cookies the fact that this upsell was accepted (so we don't show this upsell for him anymore)
			Wclu_Cookie_Handler::save_accept_for_upsell($upsell_id);
			
			// update upsell statistics
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
	
	/**
	 * When an order is completed, check if any upsells were used in this order, and update their statistics
	 * 
	 * Callback for "woocommerce_order_status_completed" action
	 * 
	 * @param int Order ID.
	 * @param WC_Order $order Order object.
	 * @param array $status_transition Status transition data
	 */
	public function maybe_update_upsell_statistics( $order_id, $order_obj, $status_transition  ) {
		
		if ( ! self::is_order_already_processed( $order_id ) ) {
			$upsells_info = self::get_upsells_used_in_order( $order_id );

			self::wc_log('WCLU - maybe_update_upsell_statistics ' . $order_id, ['$upsells_info' => $upsells_info]);

			foreach( $upsells_info as $upsell_id => $upsell_data ) {
				Wclu_Upsell_Offer::record_statistics( $upsell_id, self::EVENT_ORDER, 1 );
				Wclu_Upsell_Offer::record_statistics( $upsell_id, self::STAT_REVENUE, $upsell_data['total'], true );
			}
			
			self::mark_order_as_already_processed( $order_id );
		}
	}
	
	/**
	 * Checks whether upsells in the specified order have already been processed
	 * 
	 * @param int $order_id
	 * @return bool
	 */
	public static function is_order_already_processed( int $order_id ) {
		$meta_value = get_post_meta( $order_id, self::UPSELL_PROCESSED, true );
		
		if ( $meta_value == 1 ) {
			return true;
		}
		
		return false;
	}
	
	public static function mark_order_as_already_processed( int $order_id ) {
		update_post_meta( $order_id, self::UPSELL_PROCESSED, 1 );
	}
	
	/**
	 * Returns upsell ids and quantity of products for each upsell.
	 * 
	 * @param int $order_id
	 * @return array
	 */
	public static function get_upsells_used_in_order( $order_id ) {
		
		$order = wc_get_order( $order_id );

		$upsell_products = array();
		
		// The loop to get the order items which are WC_Order_Item_Product objects 
		foreach ( $order->get_items() as $item ) {

			$meta_data = $item->get_all_formatted_meta_data( '' ); // $hideprefix = ''
			
			self::wc_log('get_upsells_used_in_order', ['$meta_data' => $meta_data] );
			
			foreach ( $meta_data as $meta ) {
				if ( $meta->key === '_upsell_id' ) {
					
					// upsell_id => item quantity
					$upsell_products[ $meta->value] = array(
						'quantity'     => $item->get_quantity(),
					  'total'        => $item->get_total()
					);
					
					break;
				}
			}
		}
		
		return $upsell_products;
	}
}
