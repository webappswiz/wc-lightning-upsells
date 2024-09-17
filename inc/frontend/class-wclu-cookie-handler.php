<?php

class Wclu_Cookie_Handler extends Wclu_Core {

	/**
	 * All info about upsells skipped by site visitor
	 * is stored in the separate cookie.
	 */
	const COOKIE_NAME_SKIPPED = 'wclu-skipped-upsells';

	/**
	 * All info about upsells accepted by site visitor
	 * is stored in the separate cookie.
	 */
	const COOKIE_NAME_ACCEPTED = 'wclu-accepted-upsells';

	/**
	 * Register Wordpress actions and filters related to the cookie handling
	 */
	public function __construct() {
		
	}

	/**
	 * Provides array with ids of upsells that were skipped by the current visitor
	 *  
	 * @return array
	 */
	public static function get_skipped_upsells() {

		$cookie = array();

		if (isset($_COOKIE[self::COOKIE_NAME_SKIPPED])) {
			$cookie = explode('|', $_COOKIE[self::COOKIE_NAME_SKIPPED]);
		}

		return $cookie;
	}

	/**
	 * Provides array with ids of upsells that were skipped by the current visitor
	 * 
	 * @return array
	 */
	public static function get_accepted_upsells() {

		$cookie = array();

		if (isset($_COOKIE[self::COOKIE_NAME_ACCEPTED])) {
			$cookie = explode('|', $_COOKIE[self::COOKIE_NAME_ACCEPTED]);
		}

		return $cookie;
	}

	/**
	 * Save in cookies the fact that the upsell was accepted.
	 * 
	 * @param int $upsell_id
	 */
	public static function save_accept_for_upsell(int $upsell_id) {

		if (isset($_COOKIE[self::COOKIE_NAME_ACCEPTED])) {
			$value = $_COOKIE[self::COOKIE_NAME_ACCEPTED] . '|' . $upsell_id;
		} else {
			$value = $upsell_id;
		}

		$expires = time() + 365 * 24 * 3600; /* expire in 1 year */

		setcookie(self::COOKIE_NAME_ACCEPTED, $value, $expires);
	}
}
