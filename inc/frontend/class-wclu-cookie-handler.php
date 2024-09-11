<?php

class Wclu_Cookie_Handler extends Wclu_Core {
  
  
/**
 * All info about upsells skipped by site visitor
 * is stored in the separate cookie.
 */
  const COOKIE_NAME_SKIPPED = 'wclu-skipped-upsells';
  
  /**
   * Register Wordpress actions and filters related to the cookie handling
   */
  public function __construct() {
    
  }
  
  /**
   * 
   * @return array
   */
  public static function get_skipped_upsells() {
    
    $cookie = array();
    
    if ( isset( $_COOKIE[ self::COOKIE_NAME_SKIPPED] ) ) {
      $cookie = explode( '|', $_COOKIE[ self::COOKIE_NAME_SKIPPED ] );  
    }
    
    return $cookie;
  }
}