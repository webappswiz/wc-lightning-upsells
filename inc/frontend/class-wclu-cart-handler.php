<?php

class Wclu_Cart_Handler extends Wclu_Core {
  
  /**
   * Register Wordpress actions and filters related to the cart handling
   */
  public function __construct() {
    
    add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_item_metadata' ), 4, 10 );
    
    add_action( 'woocommerce_add_order_item_meta', array( $this, 'add_values_to_order_item_meta' ), 1, 2 );
    add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_items_from_session' ), 1, 3 );
    add_action( 'woocommerce_before_calculate_totals', array( $this, 'apply_custom_upsell_price_to_product' ), 1, 1 );
  }
  
  /**
   * Callback for "woocommerce_add_cart_item_data" filter which is applied in WC_Cart->add_to_cart()
   * 
   * @param array $cart_item_data
   * @param int $product_id
   * @param int $variation_id
   * @param int $quantity
   */
  public function add_item_metadata( $cart_item_data, $product_id, $variation_id, $quantity ) {
    
    $upsell_id = filter_input(INPUT_GET, 'lightning', FILTER_VALIDATE_INT);
    $upsell = Wclu_Db_Search::find_upsell_by_id( intval($upsell_id) );
    
    if ( $upsell ) { // should be Wclu_Upsell_Offer
      
      $custom_upsell_data = array(
        'upsell_id' => $upsell_id,
        'upsell_product_price' => $upsell->get_product_price()
      );
        
      $cart_item_data = array_merge( $cart_item_data, $custom_upsell_data );
      
       self::wc_log('WCLU - add_item_metadata', [ 'cart_item_data' => $cart_item_data ] );
    }
     
    return $cart_item_data;
  }
 
  
  public function get_cart_items_from_session( $item, $values, $key ) {

      if (array_key_exists( 'upsell_id', $values ) ) {
          $item['upsell_id'] = $values['upsell_id'];
      }
      
      if (array_key_exists( 'upsell_product_price', $values ) ) {
          $item['upsell_product_price'] = $values['upsell_product_price'];
      }
      
      self::wc_log('WCLU - wdm_get_cart_items_from_session', [ 'item' => $item ] );

      return $item;
  }
  
  public function add_values_to_order_item_meta( $item_id, $values ) {
    global $woocommerce,$wpdb;

    wc_add_order_item_meta( $item_id,'_upsell_id', $values['upsell_id'] );
    wc_add_order_item_meta( $item_id,'_upsell_product_price', $values['upsell_product_price'] ); //$values['_custom_options']['another_example_field']);

    self::wc_log('WCLU - wdm_add_values_to_order_item_meta', [ 'values' => $values ] );
  }
  
  
  public function apply_custom_upsell_price_to_product( $cart_object ) {
    
    foreach ( $cart_object->cart_contents as $cart_item_key => $value ) {       

      if ( isset( $value['upsell_product_price'] ) ) {
        $value['data']->set_price($value['upsell_product_price']);
      }
      
    }
  }
}