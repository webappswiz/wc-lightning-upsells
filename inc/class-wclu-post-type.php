<?php

class Wclu_Post_Type extends Wclu_Core {

  /**
   * Register Wordpress actions related to the post type and its metaboxes.
   */
  public function __construct() {
    add_action( 'init', array( $this, 'register_post_type' ), 20 );
    
    add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
    add_action( 'add_meta_boxes', array( $this, 'remove_unwanted_meta_boxes' ) );
    
    add_action( 'save_post', array($this, 'save_meta_box_data') );

  }
    
  /**
   * Register custom post type for offer
   * 
   * @callback init
   */
  public static function register_post_type() {

    if ( post_type_exists( Wclu_Core::POST_TYPE ) ) {
      return;
    }

    $labels = array(
      'name'               => __( 'Upsells',                              WCLU_TEXT_DOMAIN ),
      'singular_name'      => __( 'Upsell',                               WCLU_TEXT_DOMAIN ),
      'add_new'            => __( 'Add New',                              WCLU_TEXT_DOMAIN ),
      'add_new_item'       => __( 'Add New',                              WCLU_TEXT_DOMAIN ),
      'edit_item'          => __( 'Edit Upsell',                          WCLU_TEXT_DOMAIN ),
      'new_item'           => __( 'New Upsell',                           WCLU_TEXT_DOMAIN ),
      'search_items'       => __( 'Search Upsells',                       WCLU_TEXT_DOMAIN ),
      'not_found'          => __( 'No upsells found',                     WCLU_TEXT_DOMAIN ),
      'not_found_in_trash' => __( 'No upsells found in Trash',            WCLU_TEXT_DOMAIN ),
      'edit'               => __( 'Edit',                                 WCLU_TEXT_DOMAIN ),
      'parent'             => __( 'Parent upsell',                        WCLU_TEXT_DOMAIN ),
      'all_items'          => __( 'All Upsells',                          WCLU_TEXT_DOMAIN ),
      'menu_name'          => __( 'Lightning Upsells ',                   WCLU_TEXT_DOMAIN ),
    );

    $args = array(
      'labels'              => $labels,
      'description'         => '',
      'public'              => true,
      'exclude_from_search' => true,
      'publicly_queryable'  => true,
      'show_ui'             => true,
      'show_in_nav_menus'   => true,
      'show_in_menu'        => true,
      'show_in_admin_bar'   => true,
      'menu_position'       => 58,
      'menu_icon'           => 'dashicons-cart',
      'capability_type'     => 'post',
      'hierarchical'        => false,
      'supports'            => array( 'title', 'editor' ),
      'has_archive'         => true,
      'rewrite'             => array(
        'slug'       => Wclu_Core::POST_TYPE,
        'with_front' => true,
        'feeds'      => true,
        'pages'      => true,
      ),
      'query_var'           => true,
    );

    register_post_type( Wclu_Core::POST_TYPE, $args );

  }
  
  /**
   * Show our custom metaboxes on "Edit Upsell" page.
   * 
   * @callback add_meta_boxes
   */
  public static function add_meta_boxes() {
    global $pagenow, $typenow;

    
    if ( $pagenow == 'post.php' && $typenow == Wclu_Core::POST_TYPE ) {
      add_meta_box( 
        'wclu-custom-css',                                              // metabox ID
        __( 'Custom style (CSS)', WCLU_TEXT_DOMAIN ),                   // metabox title
        array( 'Wclu_Post_Type', 'display_metabox_with_custom_css' ),   // callback
        Wclu_Core::POST_TYPE,                                           // post type
        'normal',                                                       // metabox placement
        'high'
      );
      
      add_meta_box( 
        'wclu-upsell-setup',                                              // metabox ID
        __( 'Upsell Setup', WCLU_TEXT_DOMAIN ),                           // metabox title
        array( 'Wclu_Post_Type', 'display_metabox_with_upsell_setup' ),   // callback
        Wclu_Core::POST_TYPE,                                           // post type
        'normal',                                                       // metabox placement
        'high'
      );
      
			$locale  = localeconv();
			$decimal = isset( $locale['decimal_point'] ) ? $locale['decimal_point'] : '.';

      $woocommerce_admin_params = array(
				'i18n_decimal_error'               => sprintf( __( 'Please enter in decimal (%s) format without thousand separators.', WCLU_TEXT_DOMAIN ), $decimal ),
				'i18n_mon_decimal_error'           => sprintf( __( 'Please enter in monetary decimal (%s) format without thousand separators and currency symbols.', WCLU_TEXT_DOMAIN ), wc_get_price_decimal_separator() ),
				'decimal_point'                    => $decimal,
				'mon_decimal_point'                => wc_get_price_decimal_separator(),
			);

			
      
      $suffix = ''; //defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
      
      // Register WooCommerce scripts
			wp_enqueue_script( 'woocommerce_admin', WC()->plugin_url() . '/assets/js/admin/woocommerce_admin' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'jquery-tiptip' ), WC()->version );
			wp_localize_script( 'woocommerce_admin', 'woocommerce_admin', $woocommerce_admin_params );
      
      wp_enqueue_script( 'wc-admin-meta-boxes', WC()->plugin_url() . '/assets/js/admin/meta-boxes' . $suffix . '.js', array( 'jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable', 'accounting', 'round', 'ajax-chosen', 'chosen', 'plupload-all' ), WC_VERSION );
      wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
    }
  }

  /**
   * Remove metaboxes on "Edit Upsell" page
   * 
   * @callback add_meta_boxes
   */
  public static function remove_unwanted_meta_boxes() {
    
    global $pagenow, $typenow;
    
    if ( $pagenow == 'post.php' && $typenow == Wclu_Core::POST_TYPE ) {
      remove_meta_box( 'woothemes-settings',    Wclu_Core::POST_TYPE, 'normal' );
      remove_meta_box( 'commentstatusdiv',      Wclu_Core::POST_TYPE, 'normal' );
      remove_meta_box( 'slugdiv',               Wclu_Core::POST_TYPE, 'normal' );
      remove_meta_box( 'wpseo_meta',            Wclu_Core::POST_TYPE, 'normal' ); // Yoast SEO
    }
  }
  
  
  /**
   * Renders contents in "Upsell settings" metabox
   *
	 * @param WP_Post $post The post object.
	 */
	public static function display_metabox_with_upsell_setup( $post ) {
			
    $upsell_settings = self::get_upsell_settings( $post->ID ); // this function always returns an array (empty in the case of a new upsell)

    
    //echo('<pre>' . print_r($post, 1) . '</pre>');
    //echo('$upsell_settings<pre>' . print_r($upsell_settings, 1) . '</pre>');die();
    
    $upsell_data_tabs = array(
      'offered_product' => array(
        'label'  => __( 'Upsell deal', WCLU_TEXT_DOMAIN ),
        'target' => 'wclu-upsell-deal',
        'class'  => array( 'wclu-upsell-deal-tab' ),
        'contents' => self::get_upsell_deal_tab_contents( $upsell_settings )
      ),
      'show_when'       => array(
        'label'  => __( 'Upsell rules', WCLU_TEXT_DOMAIN ),
        'target' => 'wclu-upsell-rules',
        'class'  => array( 'wclu-upsell-rules-tab' ),
        'contents' => 'BBBBBB'
      )
    );

    ?>
    <div class="panel-wrap upsell_data">
      <ul class="offer_data_tabs wc-tabs">
        <?php foreach ( $upsell_data_tabs as $id => $tab_settings ): ?>
          <li id="<?php echo $id; ?>" class="<?php echo $id; ?>_options <?php echo $id; ?>_tab <?php echo implode( ' ', (array) $tab_settings['class'] ); ?>">
            <a href="#<?php echo $tab_settings['target']; ?>"><?php echo esc_html( $tab_settings['label'] ); ?></a>
          </li>
        <?php endforeach; ?>
      </ul>


        <?php foreach ( $upsell_data_tabs as $id => $tab_settings ): ?>
          <?php $display = $id == 'offered_product' ? 'block' : 'none'; ?>
          <div id="<?php echo $tab_settings['target']; ?>" class="upsell_deal_product panel wclu_options_panel" style="display:<?php echo $display; ?>">
            <div class="options_group" style="padding:10px; ">
              <?php echo $tab_settings['contents']; ?>
            </div>
          </div>
        <?php endforeach; ?>
    </div>
    <?php
	}
  
  public static function get_upsell_deal_tab_contents( array $upsell_settings ) {
    
    $postfield    = self::METABOX_FIELD_NAME;
    
    $upsell_product_id = $upsell_settings['product_id'];
    $upsell_price_type = $upsell_settings['price_type'];
    $upsell_price      = $upsell_settings['offered_price'];
    
    ob_start();


    $discount_types = array(
      self::PRICE_TYPE_FIXED                => __( 'Fixed Price', WCLU_TEXT_DOMAIN ),
      self::PRICE_TYPE_DISCOUNT             => __( 'Fixed Price Discount', WCLU_TEXT_DOMAIN ),
      self::PRICE_TYPE_PERCENT_DISCOUNT     => __( 'Percent Discount', WCLU_TEXT_DOMAIN ),
    );	

    $products = self::get_available_products_to_offer();
    
    ?>
      <p class="form-field">
        <label id="wclu_upsell_product_id" for="wclu_upsell_product_id">
          <strong><?php echo __( 'Which product to offer?', WCLU_TEXT_DOMAIN ); ?></strong>
          <?php echo __( '(Select the product and set the price)', WCLU_TEXT_DOMAIN ); ?>
        </label>
      </p>
      <p class="form-field">
        <label for="wclu_upsell_product_id"><?php echo __( 'Offered Product', WCLU_TEXT_DOMAIN ); ?></label>
        <select class="wc-product-search" style="width: 50%;" id="wclu_upsell_product_id" name="<?php echo $postfield; ?>[product_id]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', WCLU_TEXT_DOMAIN ); ?>">
          <?php echo '<option value="' . esc_attr( 0 ) . '"' . selected( $upsell_product_id, 0, false ) . '>' . __( 'Not selected', WCLU_TEXT_DOMAIN ) . '</option>';?>
          
          <?php foreach ( $products as $product_id => $product_name ): ?>
            <?php echo '<option value="' . esc_attr( $product_id ) . '"' . selected( $upsell_product_id, $product_id, false ) . '>' . $product_name . '</option>';?>
          <?php endforeach; ?>
        </select> 
        <?php
          echo wc_help_tip( __( 'Selected product would be shown as an upsell. On accepting this upsell, product would be added to the cart', WCLU_TEXT_DOMAIN ) );
        ?>
      </p>
      <p class="form-field">
						<label for="offered_at"><?php echo __( 'Offer At', WCLU_TEXT_DOMAIN ); ?></label>
						
            <input type="number" step="any" min="0" class="short" name="<?php echo $postfield; ?>[offered_price]" id="offer_price" placeholder="Enter price" value="<?php echo esc_attr($upsell_price); ?>"> 
            
						<select id="wclu_upsell_price_type" name="<?php echo $postfield; ?>[price_type]" class="select short">
							<?php
							foreach ( $discount_types as $key => $value ) {
								echo "<option value='$key' " . selected( $key, $upsell_price_type ) . "> $value </option>";
							}
							?>
						</select>
						<?php
							echo wc_help_tip( __( 'Enter an amount/discount as an upsell price for the selected product.', WCLU_TEXT_DOMAIN ) );
						?>
					</p>
    <?php

    $tab_html = ob_get_contents();
    ob_end_clean();
   
    return $tab_html;
  }
    
  public static function display_metabox_with_custom_css() {
    global $post;

    $upsell_id = $post->ID;

     // Add a nonce field so we can check for it later.
    wp_nonce_field(self::NONCE, self::NONCE);
    
    $postfield    = self::METABOX_FIELD_NAME;
    $settings     = self::get_upsell_settings( $upsell_id );
    $custom_css   = $settings['custom_css'] ?? '';

    ?>

    <div id="wclu_custom_css-<?php echo $offer_id; ?>">
      <span><?php echo __( 'Write custom CSS for this upsell ', WCLU_TEXT_DOMAIN ); ?></span>
      <span><?php echo __( '(Use <b>#wclu_current_upsell</b> selector to target the current upsell only)', WCLU_TEXT_DOMAIN ); ?></span><br><br>
      <textarea id="wclu_custom_css" name="<?php echo $postfield; ?>[custom_css]"><?php echo esc_attr( $custom_css ); ?></textarea>
    </div>
    <?php
  }

  
  /**
   * Saves WCLU settings for the specified Upsell.
   * @param int $post_id
   */
  public function save_meta_box_data( $post_id ) {

    $nonce = filter_input( INPUT_POST, self::NONCE, FILTER_SANITIZE_STRING );
    // Check if our nonce is set.
    if ( ! $nonce ) {
      return;
    }

    // Verify that the nonce is valid.
    if ( ! wp_verify_nonce( $nonce, self::NONCE ) ) {
      return;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
      return;
    }

    
    // Check the post type and user's permissions
    if ( filter_input( INPUT_POST, 'post_type', FILTER_SANITIZE_STRING ) == Wclu_Core::POST_TYPE && current_user_can( 'edit_page', $post_id ) ) {

      
      
      // it's safe for us to save the data now

      // TODO: replace with filter_input?
      $wclu_settings = $_POST[self::METABOX_FIELD_NAME]; // filter_input( INPUT_POST, self::METABOX_FIELD_NAME, FILTER_DEFAULT, array(FILTER_REQUIRE_ARRAY) ); 
       
      if ( is_array( $wclu_settings ) ) {
        
        // special case for checkbox
        if ( ! isset( $wclu_settings['upsells_enabled'] ) ) {
          $wclu_settings['upsells_enabled'] = false;
        }
        else {
          $wclu_settings['upsells_enabled'] = true;
        }
        
        //$wclu_settings['custom_css'] = $_POST[]

        update_post_meta($post_id, self::UPSELL_SETTINGS, $wclu_settings );
      }
    }
  }
}