<?php

/**
 * Determines segments for customers, saves this data into separate table
 * and returns segment info when needed
 */
class Wclu_Customer_Segmentor extends Wclu_Core {

	public function __construct() {
        
		add_action('user_register', array( $this, 'initialize_customer_segment_data' ) );
		
	}
	 
	public function initialize_customer_segment_data( $user_id ) {
			
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_CUSTOMER_SEGMENTS;

		$wpdb->insert(
			$table_name,
			array(
				'user_id'             => $user_id,
				'segment_id'          => self::CUSTOMER_FIRST_TIMER,
			),
			array('%d', '%d')
		);
		
	}
	
	/**
	 * Returns IDs of customer segments
	 * 
	 * @global object $wpdb
	 * @param int $user_id
	 * @return array
	 */
	public static function get_customer_segments( $user_id ) {
		
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_CUSTOMER_SEGMENTS;
		
		$sql = "SELECT segment_id from $table_name AS WHERE user_id = %d";
		$query_sql = $wpdb->prepare($sql, array( $user_id ) );
		
		$results  = $wpdb->get_results($query_sql, ARRAY_A);

		$segments = array_map( function( $a ) { return $a['segment_id']; }, $results );
		
		return $segments;
	}
	
		/**
	 * Returns IDs of customer segments
	 * 
	 * @global object $wpdb
	 * @param int $user_id
	 * @param array $segments
	 * @return array
	 */
	public static function set_customer_segments( int $user_id, array $segments ) {
		
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_CUSTOMER_SEGMENTS;
		
		if ( $user_id > 0 ) {
			// 1) delete all segments for this customer

			$delete_sql = "DELETE from $table_name AS WHERE user_id = %d";
			$query_sql = $wpdb->prepare($delete_sql, array( $user_id ) );

			$wpdb->query($query_sql);

			// 2) add segments for this customer

			$insert_sql = "INSERT INTO $table_name ( user_id, segment_id) VALUES ";
			$sep = '';

			foreach ( $segments as $segment ) {
				if ( intval($segment) > 0 ) {
					$insert_sql .= $sep . '(' . intval($user_id) . ', ' . intval($segment) . ')';
					$sep = ',';
				}
			}

			$wpdb->query($insert_sql);
		}
	}
	
	// TODO 
	public static function define_customer_segments( $user_id ) {
		
		// 1) get number of orders
		
		// 2) get total spent sum
		
		// 3) find top percentile of spending amount.
    
	}
	
}