<?php

/**
 * Class to find various upsells in the database and convert them into Wclu_Upsell_Offer
 */
class Wclu_Db_Search extends Wclu_Core {

	/**
	 * Finds all upsells that meet the specified set of conditions
	 * 
	 * @param array $conditions set of upsell conditions to meet
	 * @param array $exclude_ids IDs of upsells to exclude from result
	 * @return array
	 */
	public static function find_matching_upsells( array $conditions, array $exclude_ids ) {

		$matching_upsells = array();

		$upsells = self::find_all_upsells( $exclude_ids );

		echo('$conditions<pre>' . print_r( $conditions , 1 ) . '</pre>' );
		if ( count( $upsells ) ) {
			foreach ( $upsells as $upsell ) {
				if ( $upsell->matches_conditions( $conditions ) ) {
					$matching_upsells[] = $upsell;
				}
			}
		}

		return $matching_upsells;
	}

	/**
	 * 
	 * @global type $wpdb
	 * @return \Wclu_Upsell_Offer
	 */
	public static function find_upsell_by_id(int $id) {

		if ($id === 0) {
			return false;
		}

		global $wpdb;

		$upsell = false;

		$wp = $wpdb->prefix;

		$sql = "SELECT p.`ID`, p.`post_title` AS 'upsell_title', p.`post_content` AS `upsell_content`, pm.`meta_value` AS 'settings' from {$wp}posts AS p
			LEFT JOIN `{$wp}postmeta` AS pm on p.`ID` = pm.`post_id`
			WHERE p.`ID` = %d AND pm.`meta_key` = %s 
			AND p.post_type = %s AND p.post_status =  'publish' ";

		$query_sql = $wpdb->prepare($sql, array($id, self::UPSELL_SETTINGS, self::POST_TYPE));

		$row = $wpdb->get_row($query_sql, ARRAY_A);

		if ($row) {
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
	 * Finds all upsells except some undesirable ones.
	 * 
	 * @param array $exclude_ids
	 * @global object $wpdb
	 * @return \Wclu_Upsell_Offer
	 */
	public static function find_all_upsells($exclude_ids = array()) {
		global $wpdb;

		$wp = $wpdb->prefix;

		if (count($exclude_ids)) {

			// escape values and prepare the list of ids 
			$ids = implode(',', array_map('intval', $exclude_ids));

			$sql = "SELECT p.`ID`, p.`post_title` AS 'upsell_title', p.`post_content` AS `upsell_content`, pm.`meta_value` AS 'settings' from {$wp}posts AS p
				LEFT JOIN `{$wp}postmeta` AS pm on p.`ID` = pm.`post_id`
				WHERE pm.`meta_key` = %s
				AND p.post_type = %s AND p.post_status =  'publish'
				AND p.ID NOT in (" . $ids . ") ";

			$query_sql = $wpdb->prepare($sql, array(self::UPSELL_SETTINGS, self::POST_TYPE));
		} else {
			$sql = "SELECT p.`ID`, p.`post_title` AS 'upsell_title', p.`post_content` AS `upsell_content`, pm.`meta_value` AS 'settings' from {$wp}posts AS p
				LEFT JOIN `{$wp}postmeta` AS pm on p.`ID` = pm.`post_id`
				WHERE pm.`meta_key` = %s
				AND p.post_type = %s AND p.post_status =  'publish' ";

			$query_sql = $wpdb->prepare($sql, array(self::UPSELL_SETTINGS, self::POST_TYPE));
		}

		$upsells = array();

		$sql_results = $wpdb->get_results($query_sql, ARRAY_A);

		foreach ($sql_results as $row) {

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
