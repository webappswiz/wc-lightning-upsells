<?php

/**
 * Class to find various upsells in the database and convert them into Wclu_Upsell_Offer
 */
class Wclu_Db_Search extends Wclu_Core {
  
  
  public static function find_matching_upsells() {
    
    $upsell_ids = [ 1, 2, 300];
    
    return $upsell_ids;
  }
  
  
  /**
   * 
   * @global type $wpdb
   * @return \Wclu_Upsell_Offer
   */
  public static function find_upsell_by_id( $id ) {
  
    global $wpdb;
    
    $upsell = false;
    
    $wp = $wpdb->prefix;
    
      
    $sql = "SELECT p.`ID`, p.`post_title` AS 'upsell_title', p.`post_content` AS `upsell_content`, pm.`meta_value` AS 'settings' from {$wp}posts AS p
      LEFT JOIN `{$wp}postmeta` AS pm on p.`ID` = pm.`post_id`
      WHERE p.`ID ` = %d AND pm.`meta_key` = %s 
      AND p.post_type = %s AND p.post_status =  'publish' ";
    
    $query_sql = $wpdb->prepare( $sql, array( $id, self::UPSELL_SETTINGS, self::POST_TYPE) );
        
    $row = $wpdb->get_row( $query_sql, ARRAY_A );
    
    if ( $row ) {
      $upsell = new Wclu_Upsell_Offer(
        $row['ID'],
        $row['upsell_title'],
        $row['upsell_content'],
        (array) unserialize($row['settings'])
      );
    }
    
    return $upsell;
  }
  
  
  /**
   * 
   * @global type $wpdb
   * @return \Wclu_Upsell_Offer
   */
  public static function find_all_upsells() {
    global $wpdb;
    
    $wp = $wpdb->prefix;
    
      
    $sql = "SELECT p.`ID`, p.`post_title` AS 'upsell_title', p.`post_content` AS `upsell_content`, pm.`meta_value` AS 'settings' from {$wp}posts AS p
      LEFT JOIN `{$wp}postmeta` AS pm on p.`ID` = pm.`post_id`
      WHERE pm.`meta_key` = %s
      AND p.post_type = %s AND p.post_status =  'publish' ";
    
    $query_sql = $wpdb->prepare( $sql, array( self::UPSELL_SETTINGS, self::POST_TYPE) );
        
    $upsells = array();
    
    $sql_results = $wpdb->get_results( $query_sql, ARRAY_A );
    
    foreach ( $sql_results as $row ) {
      
      $upsells[] = new Wclu_Upsell_Offer(
        $row['ID'],
        $row['upsell_title'],
        $row['upsell_content'],
        (array) unserialize($row['settings'])
      );
    }
    
    return $upsells;
  }
}