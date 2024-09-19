<?php


class Wclu_Tab_Conditions extends Wclu_Core {
  
  /**
   * Renders the "Upsell Conditions" tab
   * 
   * @param int $upsell_id
   * @param array $upsell_settings
   * @return string
   */
  public static function get_tab_contents( int $upsell_id, array $upsell_settings, array $products ) {
    
    $postfield    = self::METABOX_FIELD_NAME;
    
    
    
    ob_start();

    //$products = self::get_available_products_to_offer();
    
    $cart_total_condition	  = $upsell_settings['cart_total_condition'];
    $cart_condition_type	  = $upsell_settings['cart_condition_type'];
	$selected_product		  = $upsell_settings['cart_contents'];
    
    $cart_condition_types = array(
      self::CART_CND_TYPE_LESS                => __( 'Less than', WCLU_TEXT_DOMAIN ),
      self::CART_CND_TYPE_LESS_EQUAL          => __( 'Less than or equal', WCLU_TEXT_DOMAIN ),
      self::CART_CND_TYPE_GREATER             => __( 'Greater than', WCLU_TEXT_DOMAIN ),
      self::CART_CND_TYPE_GREATER_EQUAL       => __( 'Greater than or equal', WCLU_TEXT_DOMAIN ),
    );
    
    ?>
      <p class="form-field">
        <label>
          <strong><?php echo __( 'When to show this upsell?', WCLU_TEXT_DOMAIN ); ?></strong>
          <?php echo __( '(enter conditions that allow to apply this upsell)', WCLU_TEXT_DOMAIN ); ?>
        </label>
      </p>
      <p class="form-field">
        <label for="wclu_upsell_cart_total_condition"><?php echo __( 'Cart total:', WCLU_TEXT_DOMAIN ); ?></label>

        <select id="wclu_upsell_cart_condition_type" name="<?php echo $postfield; ?>[cart_condition_type]" class="select short">
          <?php
          foreach ( $cart_condition_types as $key => $value ) {
            echo "<option value='$key' " . selected( $key, $cart_condition_type ) . "> $value </option>";
          }
          ?>
        </select>
                 
        <input type="number" step="any" min="0" class="short" 
               id="wclu_upsell_cart_total_condition" 
               name="<?php echo $postfield; ?>[cart_total_condition]" 
               placeholder="Enter amount" 
               value="<?php echo esc_attr($cart_total_condition); ?>"> 

        <?php
          echo wc_help_tip( __( 'Enter amount for the cart total. Set to 0 to disable this condition', WCLU_TEXT_DOMAIN ) );
        ?>
      </p>
      <p class="form-field">
        <label for="offered_at"><?php echo __( 'Cart contains:', WCLU_TEXT_DOMAIN ); ?></label>

        <select id="wclu_upsell_cart_contents" multiple="1" name="<?php echo $postfield; ?>[cart_contents]" class="select" style="min-width: 70%;">
		  <option value='0' <?php echo selected( '0', $selected_product ); ?>><?php _e( 'Not selected', WCLU_TEXT_DOMAIN ); ?></option>";
          <?php
          foreach ( $products as $key => $value ) {
            echo "<option value='$key' " . selected( $key, $selected_product ) . "> $value </option>";
          }
          ?>
        </select>
           
      </p>
    <?php

    $tab_html = ob_get_contents();
    ob_end_clean();
   
    return $tab_html;
  }
    
}
  