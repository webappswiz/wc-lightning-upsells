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
    
    add_submenu_page(
      'edit.php?post_type=lightning_upsell',     
      __( 'Upsell settings', WCLU_TEXT_DOMAIN ), // page title
      __( 'Upsell settings', WCLU_TEXT_DOMAIN ), // menu title
      'manage_options', 
      'wclu-settings',  // menu slug
      array( 'Wclu_Settings', 'render_settings_page' )   // callback.
    );
    
  }
  
  public static function do_action() {
    
    $result = '';
    
    if ( isset( $_POST['wclu-button-save'] ) ) {
    
      switch ( $_POST['wclu-button-save'] ) {
        case self::ACTION_SAVE_OPTIONS:
         
          $stored_options = get_option( 'wclu_options', array() );
          
          foreach ( self::$option_names as $option_name => $option_type ) {
            if ( isset( $_POST[$option_name] ) ) {
              $stored_options[ $option_name ] = filter_input( INPUT_POST, $option_name ); // TODO add filtering according to the $option_type
            }
          }
          
          
          // special case for checkbox
          if ( ! isset( $_POST['use_default_template'] ) ) {
            $stored_options['use_default_template'] = false;
          }
          else {
            $stored_options['use_default_template'] = true;
          }
          
          update_option( 'wclu_options', $stored_options );
        break;
        
      }
    }
    
    return $result;
  }
  
	public static function render_settings_page() {
    
    $action_results = '';
    
    if ( isset( $_POST['wclu-button-save'] ) ) {
			$action_results = self::do_action();
		}
    
    echo $action_results;
    
    self::load_options();
   
    self::render_settings_form();
    
  }
  
  public static function render_settings_form() {
    
    $global_settings_field_set = array(
  
      array(
				'name'        => "use_default_template",
				'type'        => 'checkbox',
				'label'       => 'Use default template when creating a new upsell',
				'default'     => '',
        'value'       => self::$option_values['use_default_template'],
			),
      array(
				'name'        => "default_upsell_template",
				'type'        => 'textarea',
        'rows'        => 6,
        'cols'        => 60,
				'label'       => 'Default upsell template',
				'default'     => 'your default template',
        'value'       => self::$option_values['default_upsell_template'],
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
       <input type="submit" id="wclu-button-save" name="wclu-button-save" class="button button-primary" style="" value="<?php echo self::ACTION_SAVE_OPTIONS; ?>" />
      </p>
    
    </form>
    <?php 
  }

}