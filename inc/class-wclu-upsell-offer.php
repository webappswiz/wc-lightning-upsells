<?php

/**
 * Class to create Upsell objects and handle them in the plugin code.
 */
class Wclu_Upsell_Offer extends Wclu_Core {

	// these properties are stored in wp_posts table, and are provided by Wordspress post type system

	public $id = 0;
	public $title;
	public $content;
	
	// these properties are stored in wp_postmeta table, and we handle them by ourselves.
	// default values for these properties are set in Wclu_Core::$default_upsell_settings

	public $product_id;
	public $price_type; // must be one of Wclu_Core::PRICE_TYPE_XXXXXX
	public $offered_price;

	// form tags available inside upsell content

	const FORM_TAGS = array(
			'accept',
			'skip',
			'offered_price',
			'product_price'
	);

	// internal data gathered by upsell object

	private $product_obj;
	private $regular_product_price;
	private $product_name;

	/**
	 * 
	 * @param int $upsell_id
	 * @param string $title
	 * @param string $content
	 * @param array $upsell_settings
	 */
	public function __construct($upsell_id, $title, $content, $upsell_settings = array()) {

		$this->id = $upsell_id;
		$this->title = $title;
		$this->content = $content;

		foreach (self::$default_upsell_settings as $key => $default_value) {
			if (isset($upsell_settings[$key])) {
				$this->$key = $upsell_settings[$key];
			} else {
				$this->$key = $default_value;
			}
		}

		// Gather the data about offered product

		$pf = new WC_Product_Factory();
		$product = $pf->get_product($this->product_id);

		if (is_object($product)) {
			$this->product_obj = $product;
			$this->regular_product_price = $product->get_regular_price();
			$this->product_name = $product->get_title();
		} else {
			$this->regular_product_price = 0.0;
			$this->product_name = '???';
		}
	}

	/**
	 * Calculates upsell offered price
	 * @return float
	 */
	public function get_offered_price() {

		$price = 10; // TODO calculate non-fixed prices;

		if ($this->price_type === self::PRICE_TYPE_FIXED) {
			$price = $this->offered_price;
		}

		return $price;
	}

	/**
	 * Replaces {form_tag} with actual tag contents, relevant to this specific upsell
	 * 
	 * @return string
	 */
	public function get_prepared_content() {

		$prepared_content = $this->content;

		foreach (self::FORM_TAGS as $form_tag) {

			$search_for = '{' . $form_tag . '}';
			$function_name = 'render_tag_' . $form_tag;

			if (strpos($this->content, $search_for) !== false) {
				$prepared_content = str_replace($search_for, $this->$function_name(), $prepared_content);
			}
		}

		return $prepared_content;
	}

	/**
	 * Used by get_prepared_content()
	 * @return string
	 */
	private function render_tag_accept() {
		$out = '?add-to-cart=' . $this->product_id . '&lightning=' . $this->id;
		return $out;
	}

	/**
	 * Used by get_prepared_content()
	 * @return string
	 */
	private function render_tag_skip() {
		$out = '#skip_lightning_' . $this->id;
		return $out;
	}

	/**
	 * Used by get_prepared_content()
	 * @return string
	 */
	private function render_tag_offered_price() {

		$price = $this->calculate_offered_price();

		if (floor($price) == $price) { // special case to avoid printing numbers like 1050.00 and print just 1050
			$decimals = 0;
		} else {
			$decimals = wc_get_price_decimals();
		}

		$out = wc_price($price, array('decimals' => $decimals));

		return $out;
	}

	/**
	 * Used by get_prepared_content()
	 * @return string
	 */
	private function render_tag_product_price() {

		$price = $this->regular_product_price;

		if (floor($price) == $price) { // special case to avoid printing numbers like 1050.00 and print just 1050
			$decimals = 0;
		} else {
			$decimals = wc_get_price_decimals();
		}

		$out = wc_price($price, array('decimals' => $decimals));

		return $out;
	}

	/**
	 * Get the regular price of the product which is offered in this upsell.
	 * 
	 * @return float
	 */
	public function get_product_price() {
		return $this->regular_product_price;
	}

	/**
	 * Get the name of the product which is offered in this upsell.
	 * 
	 * @return string
	 */
	public function get_product_name() {
		return $this->product_name;
	}

	/**
	 * Get the discounted price which is offered in this upsell.
	 * 
	 * @return float
	 */
	public function calculate_offered_price() {

		$result = $this->regular_product_price;

		switch ($this->price_type) {

			case self::PRICE_TYPE_DISCOUNT:

				$result = $this->regular_product_price - $this->offered_price;
				break;

			case self::PRICE_TYPE_PERCENT_DISCOUNT:

				$discount = $this->offered_price > 100 ? 1 : ( $this->offered_price / 100 );

				$result = ( 1 - $discount ) * $this->regular_product_price;
				break;

			case self::PRICE_TYPE_FIXED:
			default:

				$result = $this->offered_price;
				break;
		}

		return $result;
	}
	
	/**
	 * Checks if this upsell meets all specified conditions
	 * 
	 * @return bool
	 */
	public function matches_conditions( array $conditions ) {

		$result = true; // by default upsell is matching. 

		foreach ( $conditions as $condition ) {
			if ( ! $this->matches_single_condition( $condition ) ) {
				$result = false;
				break;
			}
		}

		return $result;
	}
	
	/**
	 * Checks if this upsell meets the specified condition
	 * 
	 * @return bool
	 */
	public function matches_single_condition( array $condition ) {

		$result = true; // by default upsell is matching. 

		// list of methods which can be used to check various conditions
		$available_methods = [
			self::CND_CART_TOTAL      => 'matches_cart_total',
			self::CND_CART_PRODUCTS   => 'matches_cart_products',
			self::CND_USER_BOUGHT     => 'matches_user_purchases'
		];

		if ( array_key_exists( $condition['type'], $available_methods ) && method_exists( $this, $available_methods[ $condition['type'] ] ) ) {
			$method = $available_methods[ $condition['type'] ];
			$result = $this->$method( $condition['value'] );
		}

		return $result;
	}
	
	/**
	 * Checks if this upsell meets the cart total condition
	 * 
	 * @return bool
	 */
	public function matches_cart_total( $cart_total ) {

		$result = true;

		if ( $this->cart_total_enabled ) { // this upsell is shown only when it matches the visitor's cart total amount
			switch( $this->cart_condition_type ) {
				case self::CART_CND_GREATER: // visitor cart total should be greater than upsell threshold 
					$result = $cart_total > $this->cart_total_condition;
					break;
				case self::CART_CND_GREATER_EQUAL: // visitor cart total should be greater of equal than upsell threshold 
					$result = $cart_total >= $this->cart_total_condition;
					break;
				case self::CART_CND_LESS: // visitor cart total should be less equal than upsell threshold 
					$result = $cart_total < $this->cart_total_condition;
					break;
				case self::CART_CND_LESS_EQUAL: // visitor cart total should be less equal than upsell threshold 
					$result = $cart_total <= $this->cart_total_condition;
					break;
			}
		}

		return $result;
	}
	
	
	/**
	 * Checks if this upsell meets the cart contents condition
	 * 
	 * @return bool
	 */
	public function matches_cart_products( $cart_contents ) {

		$result = true;

		if ( $this->cart_contents_enabled && count( $this->cart_contents ) ) { // this upsell is shown only when it matches the visitor's cart content

			$result = false;

			if ( $this->cart_must_hold_all ) { // visitor cart should contain all of selected products

				$found_products = 0;

				foreach ( $this->cart_contents as $required_product_id ) {
					if ( in_array( $required_product_id, $cart_contents) ) {
						$found_products++;
					}
				}

				if ( $found_products == count( $this->cart_contents ) ) {
					$result = true;
				}
			}
			else { // visitor cart should contain any of selected products

				foreach ( $this->cart_contents as $sufficient_product_id ) {
					if ( in_array( $sufficient_product_id, $cart_contents) ) {
						$result = true;
						break;
					}
				}
			}
		}

		return $result;
	}
}
