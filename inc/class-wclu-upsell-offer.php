<?php

/**
 * Class to create Upsell objects and handle them in the plugin code.
 */
class Wclu_Upsell_Offer extends Wclu_Core {

  
  // these properties are stored in wp_posts table, and are provided by Wordspress post type system
  
  public $id = 0;
  public $title;
  public $content;
  
  // these properties are stores in wp_postmeta table, and we handle them by ourselves.
  // default values for these properties are set in Wclu_Core::$default_upsell_settings
  
  public $product_id;
  public $price_type;
  public $offered_price;
  
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
  }
  
  public function get_product_price() {
    
    $price = 10; // TODO calculate non-fixed prices;
  
    if ( $this->price_type === self::PRICE_TYPE_FIXED ) {
      $price = $this->offered_price;
    }
    
    return $price;
  }
  
}