<?php

class Wclu_Conditions_Finder extends Wclu_Core {

	/**
	 * Returns product IDs for all items in cart
	 * 
	 * @return array
	 */
	public static function find_products_in_cart() {

		$product_ids = array();

		$wc_cart = WC()->cart;

		if ( is_object( $wc_cart ) ) {
			foreach ( $wc_cart->get_cart() as $cart_item ) {
				$product_ids[] = $cart_item['product_id'];
			}
		}

		return $product_ids;
	}

	/**
	 * Returns total sum of prices for all items in cart (excluding fees and shipping)
	 * 
	 * @return float
	 */
	public static function find_cart_total() {

		$wc_cart = WC()->cart;

		if ( is_object( $wc_cart ) ) {
			return $wc_cart->get_total( 'edit' );
		}

		return false;
	}
	
	/**
	 * 
	 * 
	 * @return float
	 */
	public static function find_conditions() {
		
		$conditions = array(
			array(
				'type' => self::CND_CART_TOTAL,
				'value' => self::find_cart_total() 
			),
			array(
				'type' => self::CND_CART_PRODUCTS,
				'value' => self::find_products_in_cart() 
			),
			array(
				'type' => self::CND_CUSTOMER_SEGMENT,
				'value' => self::find_customer_segments()
			),
		);
		
		return $conditions;
	}

	/**
	 * TODO 
	 * 
	 * @return float
	 */
	public static function find_user_history() {
		
	}
	
	/**
	 * 
	 * @return array
	 */
	public static function find_customer_segments( $user_id = 0 ) {
		
		$segments = array();
		
		if ( $user_id == 0 ) {
			$user_id = get_current_user_id();
		}
		
		if ( $user_id != 0 ) {
			$segments = Wclu_Customer_Segmentor::get_customer_segments( $user_id );
		}
		
		return $segments;
	}
}
