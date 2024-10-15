<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

/**
 * This class displays plugin settings and statistics 
 * 
 */
class Wclu_Settings extends Wclu_Core {

	const CHECK_RESULT_OK = 'ok';

	public static function add_page_to_menu() {

		add_submenu_page(
						'edit.php?post_type=lightning_upsell',
						__('Upsell settings', WCLU_TEXT_DOMAIN), // page title
						__('Upsell settings', WCLU_TEXT_DOMAIN), // menu title
						'manage_options',
						'wclu-settings', // menu slug
						array('Wclu_Settings', 'render_settings_page') // callback.
		);
	}

	public static function do_action() {

		$result = '';

		if (isset($_POST['wclu-button-save'])) {

			switch ($_POST['wclu-button-save']) {
				case self::ACTION_SAVE_OPTIONS:
				case self::ACTION_SAVE_CUSTOMER_SETTINGS:

					$stored_options = get_option('wclu_options', array());

					foreach (self::$option_names as $option_name => $option_type) {
						if (isset($_POST[$option_name])) {
							$stored_options[$option_name] = filter_input(INPUT_POST, $option_name); // TODO add filtering according to the $option_type
						}
					}


					// special case for checkbox
					if (!isset($_POST['use_default_template'])) {
						$stored_options['use_default_template'] = false;
					} else {
						$stored_options['use_default_template'] = true;
					}

					update_option('wclu_options', $stored_options);
					break;
				case self::ACTION_CALCULATE_RANGE:
					
					$start_user_id = $_POST['start_user_id'];
					$end_user_id = $_POST['end_user_id'];
					
					$user_ids = Wclu_Data_Collector::get_customer_range( $start_user_id, $end_user_id );
					
					foreach( $user_ids as $user_id ) {
						Wclu_Data_Collector::process_customer_data( $user_id );
					}
					
					self::wc_log( 'get_customer_range', $user_ids );
					self::wc_log( 'process_customer_data', array( $start_user_id, $end_user_id ) );
					break;
			
				case self::ACTION_CALCULATE_RANDOM_SAMPLE:
					
					$sample_size = $_POST['sample_size'];
					
					if ( $sample_size > 0 && $sample_size <= 100) {
					
						$user_ids = Wclu_Data_Collector::get_customer_sample( $sample_size );
					
						foreach( $user_ids as $user_id ) {
							Wclu_Data_Collector::process_customer_data( $user_id );
						}

						self::wc_log( 'get_customer_range - RANDOM ', $user_ids );
					}
					else {
						$result = self::render_message( 'Please enter valid sample size ( a number less or equal to 100)' );
					}
					
					break;
			}
		}

		return $result;
	}

	public static function render_settings_page() {

		$action_results = '';

		if (isset($_POST['wclu-button-save'])) {
			$action_results = self::do_action();
		}

		echo $action_results;

		self::load_options();
		
		?>

			<h1><?php esc_html_e('Lightning Upsells Dashboard', 'wclu'); ?></h1>
			
			<br><br><br>
		
		<?php 
		//self::render_customer_categories();
		self::render_settings_form();
	}


	public static function render_customer_categories() {

		$customers_settings_field_set = array(
			array(
				'name' => "top_spender_percentile",
				'type' => 'number',
				'label' => 'Percentile to use to define top spenders',
				'min' => 0,
				'max' => 100,
				'step' => 1,
				'value' => self::$option_values['top_spender_percentile'],
			),
			array(
				'name' => "regular_buyer_threshold",
				'type' => 'number',
				'label' => 'Threshold to define regular buyer (min. number of orders)',
				'min' => 0,
				'max' => 100,
				'step' => 1,
				'value' => self::$option_values['regular_buyer_threshold'],
			)
		);
		?> 

	<form method="POST" >

				<h2><?php esc_html_e('Categories of customers', 'wclu'); ?></h2>

				<table class="wclu-global-table">
						<tbody>
								<?php self::display_field_set( $customers_settings_field_set ); ?>
						</tbody>
				</table>

				<p class="submit">  
						<input type="submit" id="wclu-button-save" name="wclu-button-save" class="button button-primary" style="" value="<?php echo self::ACTION_SAVE_CUSTOMER_SETTINGS; ?>" />
				</p>

		</form>

		<?php

	}
	
	public static function render_settings_form() {

		$global_settings_field_set = array(
			array(
				'name' => "use_default_template",
				'type' => 'checkbox',
				'label' => 'Use default template when creating a new upsell',
				'default' => '',
				'value' => self::$option_values['use_default_template'],
			),
			array(
				'name' => "default_upsell_template",
				'type' => 'textarea',
				'rows' => 6,
				'cols' => 60,
				'label' => 'Default upsell template',
				'default' => 'your default template',
				'value' => self::$option_values['default_upsell_template'],
			)
		);
		?> 

		<form method="POST" >

				<h2><?php esc_html_e('Settings', 'wclu'); ?></h2>

				<table class="wclu-global-table">
						<tbody>
								<?php self::display_field_set($global_settings_field_set); ?>
						</tbody>
				</table>

				<p class="submit">  
						<input type="submit" id="wclu-button-save" name="wclu-button-save" class="button button-primary" style="" value="<?php echo self::ACTION_SAVE_OPTIONS; ?>" />
				</p>

		</form>

		<?php
		
		$customer_field_set = array(
			array(
				'name' => "start_user_id",
				'type' => 'text',
				'label' => 'Start user id',
				'value' => $_POST['start_user_id'] ?? 0,
			),
			array(
				'name' => "end_user_id",
				'type' => 'text',
				'label' => 'End user id',
				'value' => $_POST['end_user_id'] ?? 0,
			)
		);

		?>
		<form method="POST" >

				<h2><?php esc_html_e('Calculate customer history for the range of users', 'wclu'); ?></h2>

				<table class="wclu-global-table">
						<tbody>
								<?php self::display_field_set($customer_field_set); ?>
						</tbody>
				</table>

				<p class="submit">  
						<input type="submit" id="wclu-button-save" name="wclu-button-save" class="button button-primary" style="" value="<?php echo self::ACTION_CALCULATE_RANGE; ?>" />
				</p>

		</form>
	<?php
		
		$rand_field_set = array(
			array(
				'name' => "sample_size",
				'type' => 'number',
				'label' => 'Sample size, in % of total data',
				'min' => 0,
				'max' => 100,
				'step' => 0.1,
				'value' => 0,
			)
		);

		$total = Wclu_Data_Collector::get_total_number_of_customers();
		?>
		<form method="POST" >

				<h2><?php esc_html_e('Calculate customer history for the random % of users', 'wclu'); ?></h2>

				Total number of customers: <span id="wclu-total"><?php echo( $total ); ?></span><br>
				You are about to get a sample of <span id="sample_size">0</span> customers (<span id="sample_value">0</span> percent).<br>
				<table class="wclu-global-table">
						<tbody>
								<?php self::display_field_set($rand_field_set); ?>
						</tbody>
				</table>
				
				<script>
					function calculatePercentage( e ) {
						
							const originalNumber = parseInt(document.getElementById('wclu-total').textContent);
							const percentage = parseFloat(document.getElementById('wclu_sample-size').value);

							if ( isNaN(percentage) || percentage == 0 ) {
									document.getElementById('sample_size').textContent = '???';
									return;
							}

							document.getElementById('sample_value').textContent = percentage; 
							
							const result = Math.round( (originalNumber * percentage) / 100 );
							document.getElementById('sample_size').textContent = `approx. ${result}`;
					}
					
					document.getElementById('wclu_sample-size').addEventListener('change', calculatePercentage );
				</script>

				<p class="submit">  
						<input type="submit" id="wclu-button-save" name="wclu-button-save" class="button button-primary" style="" value="<?php echo self::ACTION_CALCULATE_RANDOM_SAMPLE; ?>" />
				</p>

		</form>

		<?php
	}
}
