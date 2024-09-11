<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Wclu_Core {

  public static $plugin_root;
  
  public const POST_TYPE = 'lightning_upsell';
  
  // options key used to save plugin settings
  public const OPTION_NAME_SETTINGS = 'wclu_options';
  
  // postmeta key used to save generation settings for each separate upsell
  public const UPSELL_SETTINGS = 'wclu_settings'; 
  
	public static $prefix = 'wclu_';
	
  // names of HTML fields in the form
  public const FIELD_DATE_START       = 'report_date_start';
  public const FIELD_DATE_END         = 'report_date_end';
  
  // available price types for an upsell
  public const PRICE_TYPE_FIXED                 = 'fixed_price';
  public const PRICE_TYPE_DISCOUNT              = 'fixed_discount';
  public const PRICE_TYPE_PERCENT_DISCOUNT      = 'discount_fraction';
  
  // name of the submit button that triggers POST form
  public const BUTTON_SUMBIT = 'wclu-button';
  
  // used in the admin area in plugin metabox.
  const NONCE = 'wclu_metabox_nonce';

  // field name for upsell settings array (used in varius upsell-related metaboxes)
  const METABOX_FIELD_NAME  = 'wclu_post_data';
  
  // Actions triggered by buttons in backend area
  public const ACTION_SAVE_OPTIONS = 'Save settings';
  
  public static $error_messages = [];
  public static $messages = [];
  
  
  public static $option_names = [
    'test_setting_1'                         => 'string',
    'test_setting_2'                         => 'string',
  ];
  
	public static $default_option_values = [
    'test_setting_1'                         => '',
    'test_setting_2'                         => '',
	];
  
  public static $default_upsell_settings = [
    'product_id'                             => 0,
    'price_type'                             => self::PRICE_TYPE_FIXED,
    'offered_price'                          => 0
  ];
    
  /**
   * List of settings used for each individual user profile.
   * 
   * Format: [ setting name => default setting value ]
   * 
   * @var array
   */
	public static $user_profile_settings = [
    'test_setting_0'        => 0,
    'test_setting_000'          => '',
	];
  
	public static $option_values = array();

	public static function init() {
		self::load_options();
	}

	public static function load_options() {
		$stored_options = get_option( 'wclu_options', array() );
    
		foreach ( self::$default_option_values as $option_name => $default_option_value ) {
			if ( isset( $stored_options[$option_name] ) ) {
				self::$option_values[$option_name] = $stored_options[$option_name];
			}
			else {
				self::$option_values[$option_name] = $default_option_value;
			}
		}
	}

	protected function display_messages( $error_messages, $messages ) {
		$out = '';
		if ( count( $error_messages ) ) {
			foreach ( $error_messages as $message ) {
        
        if ( is_wp_error( $message ) ) {
          $message_text = $message->get_error_message();
        }
        else {
          $message_text = trim( $message );
        }
        
				$out .= '<div class="notice-error settings-error notice is-dismissible"><p>'
				. '<strong>'
				. $message_text
				. '</strong></p>'
				. '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>'
				. '</div>';
			}
		}
    
		if ( count( $messages ) ) {
			foreach ( $messages as $message ) {
				$out .= '<div class="notice-info notice is-dismissible"><p>'
				. '<strong>'
				. $message
				. '</strong></p>'
				. '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>'
				. '</div>';
			}
		}

		return $out;
	}
  

  
  /**
   * Returns HTML table rows each containing field, field name, and field description
   * 
   * @param array $field_set 
   * @return string HTML
   */
	public static function render_fields_row( $field_set ) {
    
    $out = '';
    
		foreach ( $field_set as $field ) {
			
			$value = $field['value'];
			
			if ( ( ! $value) && ( $field['type'] != 'checkbox' ) ) {
				$value = $field['default'] ?? '';
			}
			
			$out .= self::display_field_in_row( $field, $value );
		}
    
    return $out;
	}
	
	/**
	 * Generates HTML code for input row in table
	 * @param array $field
	 * @param array $value
   * @return string HTML
	 */
	public static function display_field_in_row($field, $value) {
    
		$label = $field['label']; // $label = __($field['label'], DDB_TEXT_DOMAIN);
		
		$value = htmlspecialchars($value);
		$field['id'] = str_replace( '_', '-', $field['name'] );
		
		// 1. Make HTML for input
		switch ($field['type']) {
			case 'text':
				$input_HTML = self::make_text_field( $field, $value );
				break;
			case 'dropdown':
				$input_HTML = self::make_dropdown_field( $field, $value );
				break;
			case 'textarea':
				$input_HTML = self::make_textarea_field( $field, $value );
				break;
			case 'checkbox':
				$input_HTML = self::make_checkbox_field( $field, $value );
				break;
			case 'hidden':
				$input_HTML = self::make_hidden_field( $field, $value );
				break;
			default:
				$input_HTML = '[Unknown field type "' . $field['type'] . '" ]';
		}
		
		
		// 2. Make HTML for table cell
		switch ( $field['type'] ) {
			case 'hidden':
				$table_cell_html = <<<EOT
		<td class="col-hidden" style="display:none;" >{$input_HTML}</td>
EOT;
				break;
			case 'text':
			case 'textarea':
			case 'checkbox':
			default:
				$table_cell_html = <<<EOT
		<td>{$input_HTML}</td>
EOT;
				
		}

		return $table_cell_html;
	}
  
  
  
	/**
	 * Generates HTML code with TR rows containing specified field set
   * 
	 * @param array $field
	 * @param mixed $value
   * @return string HTML
	 */
	public static function display_field_set( $field_set ) {
		foreach ( $field_set as $field ) {

			$value = $field['value'] ?? false;
			
      $field['id'] = str_replace( '_', '-', $field['name'] );

			echo self::make_field( $field, $value );
		}
	}
	
  
	/**
	 * Generates HTML code with TR row containing specified field input
   * 
	 * @param array $field
	 * @param mixed $value
   * @return string HTML
	 */
	public static function make_field( $field, $value ) {
		$label = $field['label'];
		
		if ( ! isset( $field['style'] ) ) {
			$field['style'] = '';
		}
		
		// 1. Make HTML for input
		switch ( $field['type'] ) {
			case 'checkbox':
				$input_html = self::make_checkbox_field( $field, $value );
				break;
			case 'text':
				$input_html = self::make_text_field( $field, $value );
				break;
      case 'number':
				$input_html = self::make_number_field( $field, $value );
				break;
			case 'date':
				$input_html = self::make_date_field( $field, $value );
				break;
			case 'dropdown':
				$input_html = self::make_dropdown_field( $field, $value );
				break;
			case 'textarea':
				$input_html = self::make_textarea_field( $field, $value );
				break;
			case 'hidden':
				$input_html = self::make_hidden_field( $field, $value );
				break;
			default:
				$input_html = '[Unknown field type "' . $field['type'] . '" ]';
		}
		
		if (isset($field['display'])) {
			$display = $field['display'] ? 'table-row' : 'none';
		}
		else {
			$display = 'table-row';
		}
		
		// 2. Make HTML for table row
		switch ($field['type']) {
			/*case 'checkbox':
				$table_row_html = <<<EOT
		<tr style="display:{$display}" >
			<td colspan="3" class="col-checkbox">{$input_html}<label for="wclu_{$field['id']}">$label</label></td>
		</tr>
EOT;
				break;*/
			case 'hidden':
				$table_row_html = <<<EOT
		<tr style="display:none" >
			<td colspan="3" class="col-hidden">{$input_html}</td>
		</tr>
EOT;
				break;
			case 'dropdown':
			case 'text':
      case 'number':
			case 'textarea':
      case 'checkbox':
			default:
				if (isset($field['description']) && $field['description']) {
					$table_row_html = <<<EOT
		<tr style="display:{$display}" >
			<td class="col-name" style="{$field['style']}"><label for="wclu_{$field['id']}">$label</label></td>
			<td class="col-input">{$input_html}</td>
			<td class="col-info">
				{$field['description']}
			</td>
		</tr>
EOT;
				}
				else {
				$table_row_html = <<<EOT
		<tr style="display:{$display}" >
			<td class="col-name" style="{$field['style']}"><label for="wclu_{$field['id']}">$label</label></td>
			<td class="col-input">{$input_html}</td>
			<td class="col-info"></td>
		</tr>
EOT;
				}
		}

		
		return $table_row_html;
	}
	

	/**
	 * Generates HTML code for hidden input
	 * @param array $field
	 * @param array $value
	 */
	public static function make_hidden_field($field, $value) {
		$out = <<<EOT
			<input type="hidden" id="wclu_{$field['id']}" name="{$field['name']}" value="{$value}">
EOT;
		return $out;
	}	
	
	/**
	 * Generates HTML code for text field input
	 * @param array $field
	 * @param array $value
	 */
	public static function make_text_field($field, $value) {
    
    $size = $field['size'] ?? 25;
    
		$out = <<<EOT
			<input type="text" id="wclu_{$field['id']}" name="{$field['name']}" size="{$size}"value="{$value}" class="wclu-text-field">
EOT;
		return $out;
	}
  
  /**
	 * Generates HTML code for number field input
	 * @param array $field
	 * @param array $value
	 */
	public static function make_number_field($field, $value) {
		$out = <<<EOT
			<input type="number" id="wclu_{$field['id']}" name="{$field['name']}" value="{$value}" class="wclu-number-field">
EOT;
		return $out;
	}
  
	/**
	 * Generates HTML code for date field input
	 * @param array $field
	 * @param array $value
	 */
	public static function make_date_field($field, $value) {
    
    $min = $field['min'] ?? '2023-01-01';
    
		$out = <<<EOT
			<input type="date" id="wclu_{$field['id']}" name="{$field['name']}" value="{$value}" min="{$min}" class="wclu-date-field">
EOT;
		return $out;
	}
	
	/**
	 * Generates HTML code for textarea input
	 * @param array $field
	 * @param array $value
	 */
	public static function make_textarea_field($field, $value) {
		$out = <<<EOT
			<textarea id="wclu_{$field['id']}" name="{$field['name']}" cols="{$field['cols']}" rows="{$field['rows']}" value="">{$value}</textarea>
EOT;
		return $out;
	}
	
	/**
	 * Generates HTML code for dropdown list input
	 * @param array $field
	 * @param array $value
	 */
	public static function make_dropdown_field($field, $value) {
    
    $autocomplete = $field['autocomplete'] ?? false;
    
    $class = $autocomplete ? 'wclu-autocomplete' : '';
    
    $out = "<select class='$class' name='{$field['name']}' id='wclu_{$field['id']}' >";

		foreach ($field['options'] as $optionValue => $optionName) {
			$selected = ((string)$value == (string)$optionValue) ? 'selected="selected"' : '';
			$out .= '<option '. $selected .' value="' . $optionValue . '">' . $optionName .'</option>';
		}
		
		$out .= '</select>';
		return $out;
	}
	
	
	/**
	 * Generates HTML code for checkbox 
	 * @param array $field
	 */
	public static function make_checkbox_field($field, $value) {
		$chkboxValue = $value ? 'checked="checked"' : '';
		$out = <<<EOT
			<input type="checkbox" id="wclu_{$field['id']}" name="{$field['name']}" {$chkboxValue} value="1" class="wclu-checkbox-field"/>
EOT;
		return $out;
	}	
	
  
  public static function set_user_cookie_identifier() {
    if ( ! isset( $_COOKIE['wclu_cookie'] ) ) {
      
      if ( is_user_logged_in() ) {
        $user_id = get_current_user_id();
        $user_hash = md5( $user_id );
        add_user_meta( $user_id, 'wclu_hash', $user_hash , true );
      }
      else {
        $user_hash = md5( 'wclu_user' . rand(40000, 90000) . time() );
      }
      
      setcookie( 'wclu_cookie', 'sessionID_' . $user_hash, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
    }
  }
  
  public static function get_user_cookie_identifier() {
    if ( isset( $_COOKIE['wclu_cookie'] ) ) {
      return $_COOKIE['wclu_cookie'];
    }
    
    return false;
  }
  
  /**
   * Gets upsell settings.
   * 
   * @param int $upsell_id
   * @return array
   */
  public static function get_upsell_settings( int $upsell_id ) {
    $settings = get_post_meta( $upsell_id, self::UPSELL_SETTINGS, true );
    
    if ( ! is_array($settings) ) { $settings = self::$default_upsell_settings; }
    
    return $settings;
  }
  
  
  /**
   * Finds products that could be offered in an upsell
   * 
   * @return array
   */
  public static function get_available_products_to_offer() {
    global $wpdb;
    
    $products = array();
    
    $wp = $wpdb->prefix;
    
      
    $sql = "SELECT p.`ID`, p.`post_title` AS 'product_title' from {$wp}posts AS p
      WHERE p.post_type = %s AND p.post_status =  'publish' ";
    
    $query_sql = $wpdb->prepare( $sql, array( 'product' ) );
        
    $results = $wpdb->get_results( $query_sql, ARRAY_A );
    
    if ( $results ) {
      
      foreach ( $results as  $row ) {
        $products[$row['ID']] = $row['product_title'];
      }
    }
    
    return $products;
  }
  
  /**
   * Write into WooCommerce log. 
   * 
   * @param string $message
   * @param array $data
   */
  public static function wc_log( string $message, array $data ) {
    
    $data['source'] = 'wc-lightning-upsells';
    
    wc_get_logger()->info(
      $message,
      $data
    );
  }
  
}
