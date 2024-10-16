<?php
if ( !defined( 'ABSPATH' ) ) {
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
	private $post_type;
	private $cart_handler;

	public function __construct( $plugin_root ) {

		Wclu_Core::$plugin_root = $plugin_root;

		add_action( 'plugins_loaded', array($this, 'initialize'), 10 );

		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array($this, 'register_admin_styles_and_scripts') );
		}

		add_action( 'admin_menu', array('Wclu_Settings', 'add_page_to_menu') );

		add_action( 'admin_notices', array($this, 'display_admin_messages') );

		add_action( 'wp_enqueue_scripts', array($this, 'add_frontend_styles_and_scripts') );
		add_action( 'init', array($this, 'add_gutenberg_blocks') );

		add_action( 'init', array($this, 'register_shortcodes') );
		add_action( 'init', array($this, 'register_ajax_actions') );
		add_action( 'init', array($this, 'register_upsell_placement_actions') );
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
		self::create_database_tables();
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

		$js_dependencies = array('jquery');

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
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'use_codemirror' => $codemirror_enabled,
			'use_default_template' => self::$option_values['use_default_template'],
			'default_upsell_template' => self::$option_values['default_upsell_template'],
			'wclu_custom_css' => wp_enqueue_code_editor( array('type' => 'text/css') ),
		) );
	}

	public function add_frontend_styles_and_scripts() {

		$script_name = 'wclu-front.js';

		$script_id = str_replace( '.', '-', $script_name );
		wp_enqueue_script( $script_id, plugins_url( "/js/$script_name", self::$plugin_root ), array('jquery'), WCLU_VERSION, true );
		wp_localize_script( $script_id, 'wclu_settings', array('ajax_url' => admin_url( 'admin-ajax.php' )) );

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
		add_shortcode( 'display_lightning_upsells', array($this, 'shortcode_display_lightning_upsells') );

		add_shortcode( 'accept_lightning', array($this, 'shortcode_accept_lightning') );
		add_shortcode( 'skip_lightning', array($this, 'shortcode_skip_lightning') );
	}

	/**
	 * Register all plugin AJAX actions.
	 * 
	 * @hook init
	 */
	public function register_ajax_actions() {
		
		add_action( 'wp_ajax_wclu_upsell_skipped', array( $this, 'record_upsell_skipped_by_user' ) );
		add_action( 'wp_ajax_nopriv_wclu_upsell_skipped', array( $this, 'record_upsell_skipped_by_user' ) );
		
	}
	
	/**
	 * Register all plugin actions that display offers in various parts of website..
	 * 
	 * @hook init
	 */
	public function register_upsell_placement_actions() {

		add_action( 'woocommerce_before_cart', array($this, 'display_before_cart') );
		add_action( 'woocommerce_before_checkout_form', array($this, 'display_before_checkout_form') );
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

		$current_upsell_id = filter_input( INPUT_GET, 'lightning', FILTER_VALIDATE_INT );

		$exclude_ids = array_merge( $skipped_ids, $accepted_ids );

		if ( $current_upsell_id ) { // special case when user has just accepted an upsell
			$exclude_ids[] = $current_upsell_id; // the accepted upsell should not shown again 
		}

		self::wc_log( 'WCLU - display_lightning_upsells', ['skipped_ids' => $skipped_ids, 'accepted_ids' => $accepted_ids, 'exclude_ids' => $exclude_ids] );

		$current_conditions = Wclu_Conditions_Finder::find_conditions();

		$upsells = Wclu_Db_Search::find_matching_upsells( $current_conditions, $exclude_ids ); // this function always returns an array

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
	 * Handler for 'wclu_upsell_skipped' AJAX action.
	 * 
	 * @hook wp_ajax_wclu_upsell_skipped
	 */
	public function record_upsell_skipped_by_user() {
		
		$upsell_id = filter_input( INPUT_POST, 'upsell_id', FILTER_VALIDATE_INT );
		
		$upsell = Wclu_Db_Search::find_upsell_by_id( $upsell_id ); // returns Wclu_Upsell_Offer or false
		
		if ( $upsell ) {
			$upsell->record_statistics_event( self::EVENT_SKIP );
		}
 	}

	/**
	 * Displays matching upsells
	 * 
	 * @hook woocommerce_before_cart
	 */
	public function display_before_cart() {

		if ( !self::$cart_upsells_displayed ) { // prevent duplication of display
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
						__( 'Lightning Upsells Dashboard' ), // page title.
						__( 'Lightning Upsells Dashboard' ), // menu title.
						'manage_options',
						'wclu-settings', // menu slug.
						array($this, 'render_settings_page')	 // callback.
		);
	}

	public function add_wc_product_meta_box() {
		add_meta_box(
						'edit-product-lightning-upsells',
						__( 'Lightning Upsells for this product', 'wclu' ),
						array($this, 'render_wc_product_meta_box'),
						'product'
		);
	}

	public function render_wc_product_meta_box( $post ) {


		// Add a nonce field so we can check for it later.
		wp_nonce_field( self::NONCE, self::NONCE );

		$wclu_product_settings = get_post_meta( $post->ID, self::PRODUCT_SETTINGS, true );

		$checkbox_value = true; // enable by default. 

		if ( is_array( $wclu_product_settings ) ) {
			$checkbox_value = $wclu_product_settings['upsells_enabled'] === false ? 0 : 1;
		}

		$fields = array(
			array(
				'id' => 'upsells_enabled',
				'name' => self::PRODUCT_SETTINGS . '[upsells_enabled]',
				'type' => 'checkbox',
				'label' => 'Enable?',
				'default' => '',
				'value' => $checkbox_value,
				'description' => 'Enable upsells for this product'
			)
		);
		?>

		<div class="wclu-fieldset">

			<?php // TODO: display here list of upsells and downsells related to this product.  ?>
			<table class="form-table">
					<?php //self::display_field_set( $fields );   ?>	
			</table>
		</div>

		<?php
	}
	
	/**
	 * Creates DB tables when plugin is activated
	 */
	public static function create_database_tables() {

		global $wpdb;

		// Include upgrade script
		require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );

		$upsell_table     = $wpdb->prefix . self::TABLE_STATISTICS;
		$customers_table  = $wpdb->prefix . self::TABLE_CUSTOMERS_DATA;
		$segments_table   = $wpdb->prefix . self::TABLE_CUSTOMER_SEGMENTS;
		
		// Create tables if they do not exist yet
		if ( $wpdb->get_var( "show tables like '$upsell_table'" ) != $upsell_table ) {

			$statistics_sql = "CREATE TABLE `$upsell_table` (
				`upsell_id`    INT NOT NULL PRIMARY KEY,
				`view`         INT NOT NULL DEFAULT 0,
				`accept`       INT NOT NULL DEFAULT 0,
				`order`        INT NOT NULL DEFAULT 0,
				`skip`         INT NOT NULL DEFAULT 0,
				`revenue`      FLOAT NOT NULL DEFAULT 0
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

			// Create statistics table
			dbDelta( $statistics_sql );
			
			$customers_sql = "CREATE TABLE `$customers_table` (
				`user_id`             INT NOT NULL PRIMARY KEY,
				`number_of_orders`    INT NOT NULL DEFAULT 0,
				`order_sum`           FLOAT NOT NULL DEFAULT 0,
				`number_of_products`  FLOAT NOT NULL DEFAULT 0,
				`account_age`         FLOAT NOT NULL DEFAULT 0,
				`subscription_status` INT NOT NULL DEFAULT 0
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

			// Create customers table
			dbDelta( $customers_sql );
			
			$segments_sql = "CREATE TABLE `$segments_table` (
				`user_id`             INT NOT NULL,
				`segment_id`          INT NOT NULL DEFAULT 0,
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

			// Create segments table
			dbDelta( $segments_sql );
		}
		else { // maybe update table if it alresdy exists
			
			$table_version = self::get_table_schema_version();
			
			self::wc_log('maybe update table?', array( '$table_version' => $table_version ));
			//self::maybe_update_database_tables( $table_version ); // will use this later
		}
	}

	/**
	 * To be used in future, when we have actual updates for the table structure
	 * 
	 * @global object $wpdb
	 * @param int $table_version
	 */
	public static function maybe_update_database_tables( $table_version ) {
		
		global $wpdb;
		
		if ( $table_version < 1 ) {
			if ( WCLU_SCHEMA_VERSION == 1 ) { // need to add "income" column after "skips"
				$upsell_table = $wpdb->prefix . self::TABLE_STATISTICS;
				$update_statistics_table_sql = "ALTER TABLE `$upsell_table` ADD COLUMN `income` FLOAT NOT NULL DEFAULT 0 AFTER `skip`;";
				$wpdb->query( $update_statistics_table_sql );
			}
			
			$stored_options = get_option('wclu_options', array());
			$stored_options['table_schema_version'] = WCLU_SCHEMA_VERSION;
			update_option( 'wclu_options', $stored_options );
		}
	}
	
	public static function get_table_schema_version() {
		
		$version = 0;
		$stored_options = get_option('wclu_options', array());
		
		if ( count($stored_options) && isset($stored_options['table_schema_version']) ) {
			$version = intval($stored_options['table_schema_version']);
		}
		
		return $version;
	}
	
}
		