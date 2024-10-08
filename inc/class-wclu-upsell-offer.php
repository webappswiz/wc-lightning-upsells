<?php

/**
 * Class to create Upsell objects and handle them in the plugin code.
 */
class Wclu_Upsell_Offer extends Wclu_Core {

	// these properties are stored in wp_posts table, and are provided by Wordspress post type system

	public $id = 0;
	public $title;
	public $content;
	
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
	
	// these upsell properties are stored in wp_postmeta table (meta_key: Wclu_Core::UPSELL_SETTINGS )
	// default values for these properties are set in Wclu_Core::$default_upsell_settings

	public $product_id                  = 0; // int
	public $price_type                  = self::PRICE_TYPE_FIXED;
	public $offered_price               = 0; // float
	public $cart_total_condition        = 0; // float
	public $cart_condition_type         = self::CART_CND_GREATER;
	public $cart_total_enabled          = 0; // bool
	public $cart_contents_enabled       = 0; // bool
	public $cart_must_hold_all          = 0; // bool
	public $cart_contents               = array();
	

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
	 * Save updated upsell settings into DB
	 * 
	 * @param array $new_settings data from POST
	 */
	public function update_settings( array $new_settings ) {
		
		$upsell_settings = $this->calculate_additional_info( $new_settings );
		update_post_meta( $this->id, self::UPSELL_SETTINGS, $upsell_settings ); 
	}

	/**
	 * DEPRECATED
	 * 
	 * @param array $new_settings data from POST
	 */
	public function update_statistics( array $new_settings ) {

		global $wpdb;
		$upsell_table = $wpdb->prefix . self::TABLE_STATISTICS;
		
		$income = $new_settings['offered_product_price'];
		$update_statistics_sql = "UPDATE `$upsell_table` SET `income` = %f WHERE `upsell_id` = %d";
			
		$update_statistics_query = $wpdb->prepare( $update_statistics_sql, array( $income, $this->id) );
		
		$wpdb->query( $update_statistics_query );
	}

	
	/**
	 * Calculate updated upsell data based on the settings 
	 * 
	 * @param array $settings
	 */
	protected function calculate_additional_info( $settings ) {
		
		$full_settings = $settings;		
		$full_settings['regular_product_price'] = $this->regular_product_price;
		$full_settings['offered_product_price'] = $this->calculate_offered_price( $settings );
		
		return $full_settings;
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
	 * @param array $settings For the special case when using settings from POST 
	 * @return float
	 */
	public function calculate_offered_price( $settings = false ) {

		$regular_product_price = $this->regular_product_price;
		
		if ( is_array( $settings ) ) {
			$price_type            = $settings['price_type'];
			$offered_price         = $settings['offered_price'];
		}
		else {	
			$price_type            = $this->price_type;
			$offered_price         = $this->offered_price;
		}
		
		$result = $regular_product_price;

		switch ( $price_type ) {

			case self::PRICE_TYPE_DISCOUNT:

				$result = $regular_product_price - $offered_price;
				break;

			case self::PRICE_TYPE_PERCENT_DISCOUNT:

				$discount = $offered_price > 100 ? 1 : ( $offered_price / 100 );

				$result = ( 1 - $discount ) * $regular_product_price;
				break;

			case self::PRICE_TYPE_FIXED:
			default:

				$result = $offered_price;
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
	
	/**
	 * 
	 * @param string $event_type
	 */
	public function record_statistics_event( $event_type ) {
		return self::record_statistics( $this->id, $event_type );
	}
	
	/**
	 * Increases column value in the statistics record for the specified upsell
	 *
	 * @param int $upsell_id
	 * @param string $column
	 * @param int|float $quantity
	 * @param bool $float
	 */
	public static function record_statistics( $upsell_id, $column, $quantity = 1, $float = false ) {
	
		global $wpdb;
		
		$updated = false;
		
		$available_columns = [
			self::EVENT_ACCEPT => 'int', 
			self::EVENT_SKIP   => 'int',
			self::EVENT_VIEW   => 'int',
			self::EVENT_ORDER  => 'int',
			self::STAT_REVENUE => 'float',
		];
		
		// event type must be one of the existing table columns
		if ( array_key_exists( $column, $available_columns ) ) {
			
			$upsell_table = $wpdb->prefix . self::TABLE_STATISTICS;
			
			if ( $float && $available_columns[$column] === 'float' ) {
				
				$update_sql = "UPDATE $upsell_table SET `$column` = `$column` + %f WHERE upsell_id = %d LIMIT 1"; 
				
			} elseif ( ! $float && $available_columns[$column] === 'int' ) {
				
				$update_sql = "UPDATE $upsell_table SET `$column` = `$column` + %d WHERE upsell_id = %d LIMIT 1"; 
			}
			
			$update_query = $wpdb->prepare( $update_sql, array( $quantity, $upsell_id ) );
			
			$updated = $wpdb->query( $update_query );
		}
		
		return $updated;
	}
	
	/**
	 * Returns array with the upsell statistics.
	 * 
	 * @param int $upsell_id
	 * @return array
	 */
	public static function get_statistics( $upsell_id ) {
	
		global $wpdb;
		
		$upsell_table = $wpdb->prefix . self::TABLE_STATISTICS;

		$select_sql = "SELECT * FROM $upsell_table WHERE upsell_id = %d LIMIT 1"; 

		$select_query = $wpdb->prepare( $select_sql, array( $upsell_id ) );
			
		$result = $wpdb->get_row( $select_query, ARRAY_A );
		
		if ( ! $result ) {
			return array();
		}
		
		return $result;
	}
}
