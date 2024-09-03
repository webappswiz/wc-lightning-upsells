<?php

class Wclu_Cart_Handler extends Wclu_Core {
  
  /**
   * Register Wordpress actions and filters related to the cart handling
   */
  public function __construct() {
    
    add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_item_metadata' ), 4, 10 );
    
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
    
    if ( isset($_GET['upsell_id'] ) ) {
      echo('BOOOOM!');
      die();
      
    }
    return $cart_item_data;
  }
  
  public static function find_matching_upsells() {
    
    $upsell_ids = [ 1, 2, 300];
    
    return $upsell_ids;
  }
  
}