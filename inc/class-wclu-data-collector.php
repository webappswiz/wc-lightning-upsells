<?php

/**
 * Gathers customer data into separate table to allow clustering analysis of customer behaviour
 */
class Wclu_Data_Collector extends Wclu_Core {

	public function __construct() {
        
		add_action('user_register', array( $this, 'initialize_customer_data' ) );
		
	}
	 
	public function initialize_customer_data( $user_id ) {
			
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_CUSTOMERS_DATA;

		$wpdb->insert(
			$table_name,
			array(
				'user_id'             => $user_id,
				'number_of_orders'    => 0,
				'order_sum'           => 0.0,
				'number_of_products'  => 0.0,
				'account_age'         => 0.0,
				'subscription_status' => 0
			),
			array('%d', '%d', '%f', '%f', '%f', '%d')
		);
		
	}  

	public static function get_customer_range( int $start, int $end ) {
		global $wpdb;

		$wp = $wpdb->prefix;

		$sql = "SELECT u.`ID` AS 'id' from {$wp}users AS u
			WHERE u.`ID` >= %d AND u.`ID` <= %d ";

		$query_sql = $wpdb->prepare($sql, array( $start, $end ) );
		
		$results  = $wpdb->get_results($query_sql, ARRAY_A);
		
		$user_ids = array_map( function( $a ) { return $a['id']; }, $results );
		
		return $user_ids;
	}
	
	public static function process_customer_data( $user_id ) {
		
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_CUSTOMERS_DATA;

		// Get all completed orders for the user
		$order_ids = wc_get_orders( array(
				'customer_id'   => $user_id,
				'status'        => 'completed',
				'return'        => 'ids'
		));

		$number_of_orders = count( $order_ids );
		$total_order_sum  = 0;
		$total_products   = 0;

		foreach ( $order_ids as $order_id ) {
				$order = wc_get_order( $order_id );
				$total_order_sum += $order->get_total();
				$total_products += count($order->get_items());
		}

		$average_order_sum = $number_of_orders > 0 ? round( $total_order_sum / $number_of_orders, 2 ) : 0;
		$average_products  = $number_of_orders > 0 ? round( $total_products / $number_of_orders, 2 ) : 0;
		
		// Calculate account age in months
		$account_age = self::calculate_age_of_account( $user_id );

		$wpdb->replace(
			$table_name,
			array(
				'user_id'             => $user_id,
				'number_of_orders'    => $number_of_orders,
				'order_sum'           => $average_order_sum,
				'number_of_products'  => $average_products,
				'account_age'         => $account_age,
				'subscription_status' => 0
			),
			array('%d', '%d', '%f', '%f', '%f', '%d')
		);
    
	}
	
	/**
	 * Returns account age in months
	 * 
	 * @param int $user_id
	 * @return float
	 */
	public static function calculate_age_of_account( $user_id ) {
		
		$date = self::get_registration_date( $user_id );
		
		$start   = new DateTime( $date );
    $end     = new DateTime( 'now' );
		$diff    = $start->diff( $end );
		
		$age_in_months = ( $diff->y * 12 ) + $diff->m + ( $diff->d / 30 );
    
		return $age_in_months;
	}
	
	
	public static function get_registration_date( $user_id ) {
		
		global $wpdb;
		$table_name = $wpdb->prefix . 'users';

		$user_registration_date_sql = "SELECT u.`user_registered` from `$table_name` AS u WHERE u.`ID` = %d ";

		$query = $wpdb->prepare( $user_registration_date_sql, array( $user_id ) );

		$date = $wpdb->get_var( $query );		
		
		return $date;
	}
	
}