<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * This class displays specified plugin upsells in frontend
 * 
 */
class Wclu_Display_Upsells extends Wclu_Core {
	
  
  private $upsells = array();
  
  public function __construct( $upsells ) {
  
    $this->upsells = $upsells ;
  }

  /**
   * 
   * @return string
   */
  public function display_in_shortcode() {
    
    $out = '';
    if ( is_array($this->upsells) && count($this->upsells) ) {
      foreach ( $this->upsells as $upsell ) {
        
        // TODO: introduce separate template files 
        $out .= "<div id='lightning-upsell-$upsell->id' class='single-upsell-container' >";
        //$out .= '<h3 class="lightning-upsell-title">' . $data['upsell_title'] . '</h3>';
        $out .= '<div class="lightning-upsell-content">' . do_shortcode( $upsell->content ) . '</div>';
        $out .= "</div>";
      }
    }
    
    return $out;
  }
  
  /**
   * 
   * @return string
   */
  public function display_before_cart() {
    
    $out = '';
    if ( is_array($this->upsells) && count($this->upsells) ) {
      foreach ( $this->upsells as $upsell ) {
        
        // TODO: introduce separate template files 
        $out .= "<div id='lightning-upsell-$upsell->id' class='single-upsell-container' >";
        $out .= '<div class="lightning-upsell-content">' . do_shortcode( $upsell->content ) . '</div>';
        $out .= "</div>";
      }
    }
    
    return $out;
  }
  
}