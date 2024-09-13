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
  
  // form tags available inside upsell content
  
  const FORM_TAGS = array(
    'accept',
    'skip'
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
      
      $function_name = 'render_tag_' . $form_tag;
      $prepared_content = str_replace( '{' . $form_tag . '}', $this->$function_name(), $prepared_content );
      
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
  
}