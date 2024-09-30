<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

/**
 * This class displays specified plugin upsells in frontend
 * 
 */
class Wclu_Display_Upsells extends Wclu_Core {

	private $upsells = array();

	public function __construct($upsells) {

		$this->upsells = $upsells;
	}

	/**
	 * 
	 * @return string
	 */
	public function display_in_shortcode() {

		$out = '';
		if (is_array($this->upsells) && count($this->upsells)) {
			foreach ($this->upsells as $upsell) {
				if (is_a($upsell, 'Wclu_Upsell_Offer')) {

					$upsell_content = $upsell->get_prepared_content();
					
					$upsell->record_statistics_event( self::EVENT_VIEW );
									
					$final_content = do_shortcode($upsell_content);

					// TODO: introduce separate template files 
					$out .= "<div id='lightning-upsell-$upsell->id' class='single-upsell-container' >";
					$out .= '<div class="lightning-upsell-content">' . $final_content . '</div>';
					$out .= "</div>";
				}
			}
		}

		return $out;
	}

	/**
	 * Maybe deprecate? 
	 * 
	 * @return string
	 */
	public function display_before_cart() {
		return $this->display_in_shortcode();
	}
}
