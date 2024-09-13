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
  
  private $regular_product_price;
  
  // form tags available inside upsell content
  
  const FORM_TAGS = array(
    'accept',
    'skip',
    'offered_price',
    'product_price'
  );
  
  /**
   * 
   * @param int $upsell_id
   * @param string $title
   * @param string $content
   * @param array $upsell_settings
   */
  public function __construct( $upsell_id, $title, $content, $upsell_settings = array() ) {
    
    $this->id         = $upsell_id;
    $this->title      = $title;
    $this->content    = $content;
    
    foreach ( self::$default_upsell_settings as $key => $default_value ) {
      if ( isset( $upsell_settings[$key] ) ) {
        $this->$key = $upsell_settings[$key];
      }
      else {
        $this->$key = $default_value;
      }
    }
    
    $this->regular_product_price = $this->get_product_price();
    
  }
  
  /**
   * Calculates upsell offered price
   * @return float
   */
  public function get_offered_price() {
    
    $price = 10; // TODO calculate non-fixed prices;
  
    if ( $this->price_type === self::PRICE_TYPE_FIXED ) {
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
    
    foreach ( self::FORM_TAGS as $form_tag ) {
      
      $search_for = '{' . $form_tag . '}';
      $function_name = 'render_tag_' . $form_tag;
      
      if ( strpos( $this->content, $search_for ) !== false ) {
        $prepared_content = str_replace( $search_for, $this->$function_name(), $prepared_content );
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
    
    if ( floor( $price ) == $price ) { // special case to avoid printing numbers like 1050.00 and print just 1050
      $decimals = 0;
    }
    else {
      $decimals = wc_get_price_decimals();
    }
    
    $out = wc_price( $price, array( 'decimals' => $decimals ) ); 
    
    return $out;
  }
  
  /**
   * Used by get_prepared_content()
   * @return string
   */
  private function render_tag_product_price() {
    
    $price = $this->regular_product_price;
    
    if ( floor( $price) == $price ) { // special case to avoid printing numbers like 1050.00 and print just 1050
      $decimals = 0;
    }
    else {
      $decimals = wc_get_price_decimals();
    }
    
    $out = wc_price( $price, array( 'decimals' => $decimals ) );
    
    return $out;
  }
  
  /**
   * Get the regular price of the product which is offered in this upsell.
   * 
   * @return float
   */
  private function get_product_price() {
    
    $price = 0.0;
    
    $pf = new WC_Product_Factory();
    $product = $pf->get_product( $this->product_id ); 
    if ( is_object( $product ) ) {
      $price = $product->get_regular_price();
    }
    
    return $price;
  }
  
  /**
   * Get the discounted price which is offered in this upsell.
   * 
   * @return float
   */
  public function calculate_offered_price() {
    
    $result = $this->regular_product_price;
    
    switch( $this->price_type ) {
      
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
  
}