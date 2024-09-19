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
      'public'              => false,
      'exclude_from_search' => true,
      'publicly_queryable'  => false,
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
        'low'
      );
      
      add_meta_box( 
        'wclu-upsell-setup',                                              // metabox ID
        __( 'Upsell Setup', WCLU_TEXT_DOMAIN ),                           // metabox title
        array( 'Wclu_Post_Type', 'display_metabox_with_upsell_setup' ),   // callback
        Wclu_Core::POST_TYPE,                                           // post type
        'normal',                                                       // metabox placement
        'high'
      );
      
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
		
    $upsell_id = $post->ID;
    $upsell_settings = self::get_upsell_settings( $upsell_id ); // this function always returns an array (empty in the case of a new upsell)

    
    //echo('<pre>' . print_r($post, 1) . '</pre>');
    //echo('$upsell_settings<pre>' . print_r($upsell_settings, 1) . '</pre>');die();
    
    $products = self::get_available_products_to_offer();
    
    $upsell_data_tabs = array(
      'offered_product' => array(
        'label'  => __( 'Upsell deal', WCLU_TEXT_DOMAIN ),
        'target' => 'wclu-upsell-deal',
        'class'  => array( 'wclu-upsell-deal-tab' ),
        'contents' => Wclu_Tab_Deal::get_tab_contents( $upsell_id, $upsell_settings, $products )
      ),
      'upsell_conditions' => array(
        'label'  => __( 'Upsell conditions', WCLU_TEXT_DOMAIN ),
        'target' => 'wclu-upsell-conditions',
        'class'  => array( 'wclu-upsell-conditions-tab' ),
        'contents' => Wclu_Tab_Conditions::get_tab_contents( $upsell_id, $upsell_settings, $products )
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