<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Basic class that contains common functions,
 * such as:
 * - installation / deinstallation
 * - meta & options management,
 * - adding pages to menu
 * etc
 */
class Wclu_Plugin extends Wclu_Core {
	
	const CHECK_RESULT_OK = 'ok';
  
  private static $cart_upsells_displayed = false;
  private static $checkout_upsells_displayed = false; // TODO
  
  public function __construct( $plugin_root ) {

		Wclu_Core::$plugin_root = $plugin_root;

		add_action( 'plugins_loaded', array( $this, 'initialize'), 10 );
	  
    if ( is_admin() ) {
      add_action( 'admin_enqueue_scripts', array($this, 'register_admin_styles_and_scripts') );
    }
    
		add_action( 'admin_menu', array( 'Wclu_Settings', 'add_page_to_menu' ) );
    
    add_action( 'admin_notices', array( $this, 'display_admin_messages' ) );
    
    add_action( 'wp_enqueue_scripts', array( $this, 'add_frontend_styles_and_scripts' ) );
    add_action( 'init', array( $this, 'add_gutenberg_blocks' ) );

    
    add_action( 'init', array( $this, 'register_shortcodes') );
    add_action( 'init', array( $this, 'register_upsell_placement_actions') );
    //add_action( 'init', array( 'Wclu_Core', 'set_user_cookie_identifier' ) );
    
    
    $this->post_type = new Wclu_Post_Type();
    $this->cart_handler = new Wclu_Cart_Handler();
	}

	public function initialize() {
		self::load_options();
    
	}

	/**
   *  on plugin activation:
   *  - Add options
   *  - check Wordpress and PHP versions
   *  - create custom directory to save uploaded images
   */
	public static function install() {
		self::install_plugin_options();
	}
  
  /**
   *  on plugin deactivation
   */
	public static function uninstall() {
		
	}
  
  public function display_admin_messages() {
    echo self::display_messages( Wclu_Core::$error_messages, Wclu_Core::$messages );
  }
  
	public static function install_plugin_options() {
		add_option( 'wclu_options', self::$default_option_values );
	}
  
  public function register_admin_styles_and_scripts() {
    $file_src = plugins_url( 'css/wclu-admin.css', self::$plugin_root );
    wp_enqueue_style( 'wclu-admin', $file_src, array(), WCLU_VERSION );
    
    $js_dependencies = array( 'jquery' );

    $codemirror_enabled = false;

    // Add WP CodeMirror library for WCLU custom CSS textarea 
    $current_screen = get_current_screen();
    if ( $current_screen instanceof WP_Screen && $current_screen->id == Wclu_Core::POST_TYPE ) {
     
      $codemirror_enabled = true;
      $js_dependencies[] = 'wp-theme-plugin-editor'; 
      wp_enqueue_script( 'wp-theme-plugin-editor' );
      wp_enqueue_style( 'wp-codemirror' );
      
      $js_dependencies[] = 'selectWoo'; 
      wp_enqueue_script( 'selectWoo' );
    }

    wp_enqueue_script( 'wclu-admin-js', plugins_url( '/js/wclu-admin.js', self::$plugin_root ), $js_dependencies, WCLU_VERSION, true );
    
    wp_localize_script( 'wclu-admin-js', 'wclu_settings', array(
      'ajax_url'                => admin_url( 'admin-ajax.php' ),
      'use_codemirror'          => $codemirror_enabled,
      'use_default_template'    => self::$option_values['use_default_template'],
      'default_upsell_template' => self::$option_values['default_upsell_template'],
      'wclu_custom_css'         => wp_enqueue_code_editor( array('type' => 'text/css') ),
    ) );
  }
  
  public function add_frontend_styles_and_scripts( ) {
    
    $script_name = 'wclu-front.js';
    
		$script_id = str_replace( '.', '-', $script_name );
		wp_enqueue_script( $script_id, plugins_url("/js/$script_name", self::$plugin_root), array( 'jquery' ), WCLU_VERSION, true );
    wp_localize_script( $script_id, 'wclu_settings', array( 'ajax_url'			=> admin_url( 'admin-ajax.php' ) ) );
		
    if ( file_exists( WCLU_PATH . 'css/wclu-front.css' ) ) {
      wp_enqueue_style( 'wclu-main', WCLU_URL . 'css/wclu-front.css', false, WCLU_VERSION );
    }
    
	}
  
  public function add_gutenberg_blocks() {
    register_block_type( __DIR__ . '/build-block' );
  }
  
  // Use preg_replace() to search for and replace template tags
  public function replace_template_tags( $block_content, $block ) {
      $block_content = preg_replace( '/{ post_name }/', get_the_title(), $block_content );
      return $block_content;
  }

  
  /*
  public function add_admin_styles_scripts() {
    if ( file_exists(WCLU_PATH . 'js/wclu-admin.js') ) {
      wp_enqueue_script( 'wclu-admin', WCLU_URL . 'js/wclu-admin.js', array( 'jquery' ), WCLU_VERSION, true );

      wp_localize_script( 'wclu-admin', 'ajaxUrl', admin_url('admin-ajax.php') );
    }

    if ( file_exists(WCLU_PATH . 'css/wclu-admin.css') ) {
      wp_enqueue_style( 'wclu-main', WCLU_URL . 'css/wclu-admin.css', false, WCLU_VERSION );
    }
  }
  */
  
  /**
   * Register all plugin shortcodes.
   * 
   * @hook init
   */
  public function register_shortcodes() {
  	add_shortcode( 'display_lightning_upsells',   array( $this, 'shortcode_display_lightning_upsells' ) );
    
    add_shortcode( 'accept_lightning',            array( $this, 'shortcode_accept_lightning' ) );
		add_shortcode( 'skip_lightning',              array( $this, 'shortcode_skip_lightning' ) );
  }
  
  /**
   * Register all plugin actions that display offers in various parts of website..
   * 
   * @hook init
   */
  public function register_upsell_placement_actions() {
    
    add_action( 'woocommerce_before_cart',            array( $this, 'display_before_cart' ) );
    add_action( 'woocommerce_before_checkout_form',   array( $this, 'display_before_checkout_form' ) );
  }
  
  /**
   * Handler for 'display_lightning_upsells' shortcode.
   * 
   * @param array $atts
   * @param string $content
   * @return string
   */
  public function shortcode_display_lightning_upsells( $atts, $content = null ) {
    
    $out = '';
    
    // TODO add shortcode parameters
    extract( shortcode_atts( array( 
        'style' => ""
    ), $atts ) );  
    
    // TODO add search for matching upsells
    
    $skipped_ids = Wclu_Cookie_Handler::get_skipped_upsells();
    $accepted_ids = Wclu_Cookie_Handler::get_accepted_upsells();
    
    $current_upsell_id = filter_input(INPUT_GET, 'lightning', FILTER_VALIDATE_INT);
    
    $exclude_ids = array_merge( $skipped_ids, $accepted_ids );
    
    if ( $current_upsell_id ) { // special case when user has just accepted an upsell
      $exclude_ids[] = $current_upsell_id;
    }
    
    self::wc_log('WCLU - display_lightning_upsells', [ 'skipped_ids' => $skipped_ids, 'accepted_ids' => $accepted_ids, 'exclude_ids' => $exclude_ids ] );
    
    $upsells = Wclu_Db_Search::find_all_upsells( $exclude_ids ); // this function always returns an array
    
    if ( count( $upsells ) ) {
      $wclu_display_upsell = new Wclu_Display_Upsells( $upsells );

      $out = '<div class="lightning-upsells-container">';
      $out .= $wclu_display_upsell->display_in_shortcode();
      $out .= '</div>';
    }
    
    return $out;
    
  }
  
  /**
   * Handler for 'accept_lightning' shortcode.
   * 
   * @param array $atts
   * @param string $content
   * @return string
   */
  public function shortcode_accept_lightning( $atts, $content = null ) {
    
    $out = '';
    
    extract( shortcode_atts( array( 
        'upsell_id' => 0
    ), $atts ) );  
    
    
    if ( $upsell_id > 0 ) {
      $upsell = Wclu_Db_Search::find_upsell_by_id( $upsell_id ); // this function returns Wclu_Upsell_offer or false 

      if ( is_object( $upsell ) ) {
        $out = '?add-to-cart=' . $upsell->product_id . '&lightning=' . $upsell->id;
      }
    }
    
    return $out;
    
  }
  
   /**
   * Handler for 'accept_lightning' shortcode.
   * 
   * @param array $atts
   * @param string $content
   * @return string
   */
  public function shortcode_skip_lightning( $atts, $content = null ) {
    
    $out = '';
    
    extract( shortcode_atts( array( 
        'upsell_id' => 0
    ), $atts ) );  
    
    $upsell = Wclu_Db_Search::find_upsell_by_id( $upsell_id ); // this function returns Wclu_Upsell_offer or false 
    
    if ( is_object( $upsell ) ) {

      $out = '#skip_lightning_' . $upsell->id;
    }
    
    return $out;
    
  }
   
  /**
   * Displays matching upsells
   * 
   * @hook woocommerce_before_cart
   */
  public function display_before_cart() {
    
    if ( ! self::$cart_upsells_displayed ) { // prevent duplication of display
    
      // TODO add search for matching upsells
      $upsells = Wclu_Db_Search::find_all_upsells(); // this function always returns an array

      if ( count( $upsells ) ) {
        $wclu_display_upsell = new Wclu_Display_Upsells( $upsells );

        $out = '<div class="lightning-upsells-container upsells-before-cart">';
        $out .= $wclu_display_upsell->display_before_cart();
        $out .= '</div>';
      }


      self::$cart_upsells_displayed = true;
      
      echo $out;
    }
  }
  
  
	public function add_page_to_menu() {
    
		add_management_page(
			__( 'Lightning Upsells Dashboard' ),          // page title.
			__( 'Lightning Upsells Dashboard' ),          // menu title.
			'manage_options',
			'wclu-settings',			                // menu slug.
			array( $this, 'render_settings_page' )   // callback.
		);
  }
 
  public function add_wc_product_meta_box() {
    add_meta_box(
      'edit-product-lightning-upsells',
      __( 'Lightning Upsells for this product', 'wclu' ),
      array( $this, 'render_wc_product_meta_box' ),
      'product'
    );
  }
  
  /**
  public function do_action() {
    
    $result = '';
    
    if ( isset( $_POST['wclu-button'] ) ) {
      
      switch ( $_POST['wclu-button'] ) {
        case self::ACTION_SAVE_OPTIONS:
         
          $stored_options = get_option( 'wclu_options', array() );
          
          foreach ( self::$option_names as $option_name => $option_type ) {
            $stored_options[ $option_name ] = filter_input( INPUT_POST, $option_name );
          }
          
          update_option( 'wclu_options', $stored_options );
        break;
      }
    }
    
    return $result;
  }
  */
  
  public function render_wc_product_meta_box( $post ) {
    
  
    // Add a nonce field so we can check for it later.
    wp_nonce_field(self::NONCE, self::NONCE);

    $wclu_product_settings = get_post_meta( $post->ID, self::PRODUCT_SETTINGS, true );
   
    $checkbox_value = true; // enable by default. 
    
    if ( is_array( $wclu_product_settings ) ) {
      $checkbox_value = $wclu_product_settings['upsells_enabled'] === false ? 0 : 1;
    }
     
    $fields = array(
			array(
				'id'          => 'upsells_enabled',
				'name'				=> self::PRODUCT_SETTINGS . '[upsells_enabled]', 
				'type'				=> 'checkbox', 
				'label'				=> 'Enable?', 
				'default'			=> '',
				'value'				=> $checkbox_value,
				'description'	=> 'Enable upsells for this product'
			)
    );
    
    ?>

    <div class="wclu-fieldset">
      
      TODO: display here list of upsells and downsells related to this product.
      <table class="form-table">
				<?php //self::display_field_set( $fields ); ?>	
			</table>

      <?php
    }
  

}