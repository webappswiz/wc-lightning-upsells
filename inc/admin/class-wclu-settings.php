<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * This class displays plugin settings and statistics 
 * 
 */
class Wclu_Settings extends Wclu_Core {
	
	const CHECK_RESULT_OK = 'ok';
    
	public static function add_page_to_menu() {
    
		add_management_page(
			__( 'Lightning Upsells Dashboard' ),          // page title.
			__( 'Lightning Upsells Dashboard' ),          // menu title.
			'manage_options',
			'wclu-settings',			                // menu slug.
			array( 'Wclu_Settings', 'render_settings_page' )   // callback.
		);
  }
  
  public static function do_action() {
    
    $result = '';
    
    if ( isset( $_POST['wclu-button'] ) ) {
    
      switch ( $_POST['wclu-button'] ) {
        case self::ACTION_SAVE_OPTIONS:
         
          $stored_options = get_option( 'wclu_options', array() );
          
          foreach ( self::$option_names as $option_name => $option_type ) {
            if ( isset( $_POST[$option_name] ) ) {
              $stored_options[ $option_name ] = filter_input( INPUT_POST, $option_name );
            }
          }
          
          update_option( 'wclu_options', $stored_options );
        break;
        
      }
    }
    
    return $result;
  }
  
	public static function render_settings_page() {
    
    $action_results = '';
    
    if ( isset( $_POST['wclu-button'] ) ) {
			$action_results = self::do_action();
		}
    
    echo $action_results;
    
    self::load_options();
   
    self::render_settings_form();
    
  }
  
  public static function render_settings_form() {
    
    $global_settings_field_set = array(
  
      array(
				'name'        => "test_setting_1",
				'type'        => 'text',
				'label'       => 'test 1',
				'default'     => '',
        'value'       => self::$option_values['test_setting_1'],
			),
      array(
				'name'        => "test_setting_2",
				'type'        => 'text',
				'label'       => 'Test 2',
				'default'     => '',
        'value'       => self::$option_values['test_setting_2'],
			)
		);
    ?> 

    <form method="POST" >
    
      <h1><?php esc_html_e('Lightning Upsells Dashboard', 'wclu'); ?></h1>
      
      <h2><?php esc_html_e('Settings', 'wclu'); ?></h2>
      
      <table class="wclu-global-table">
        <tbody>
          <?php self::display_field_set( $global_settings_field_set ); ?>
        </tbody>
      </table>
      
      <p class="submit">  
       <input type="submit" id="wclu-button-save" name="wclu-button-save" class="button button-primary" style="background: #a52a41; color: white; margin-left: 140px; "value="<?php echo self::ACTION_SAVE_OPTIONS; ?>" />
      </p>
    
    </form>
    <?php 
  }

}