<?php

class Wclu_Tab_Deal extends Wclu_Core {
  
  /**
   * Renders the "Upsell Deal" tab
   * 
   * @param int $upsell_id
   * @param array $upsell_settings
   * @param array $products items available to offer
   * @return string
   */
  public static function get_tab_contents( int $upsell_id, array $upsell_settings, array $products ) {
    
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
    
    if ( $upsell_product_id === 0 ) {
      $offered_price        = 0;
      $offer_product_name   = '';
      $regular_price        = 0;
    }
    else {
      $upsell_obj = Wclu_Db_Search::find_upsell_by_id( $upsell_id );
      
      $offered_price        = $upsell_obj->calculate_offered_price();
      $offer_product_name   = $upsell_obj->get_product_name();
      $regular_price        = $upsell_obj->get_product_price();
    }
    
    ?>
      <p class="form-field">
        <label for="wclu_upsell_product_id">
          <strong><?php echo __( 'Which product to offer?', WCLU_TEXT_DOMAIN ); ?></strong>
          <?php echo __( '(Select the product and set the price)', WCLU_TEXT_DOMAIN ); ?>
        </label>
      </p>
      <p class="form-field">
        <label for="wclu_upsell_product_id"><?php echo __( 'Offered Product', WCLU_TEXT_DOMAIN ); ?></label>
        
        <select class="wc-product-search" style="width: 50%;" 
                id="wclu_upsell_product_id" 
                name="<?php echo $postfield; ?>[product_id]" 
                data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', WCLU_TEXT_DOMAIN ); ?>">
          
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

        <input type="number" step="any" min="0" class="short" 
               id="offer_price" 
               name="<?php echo $postfield; ?>[offered_price]" 
               placeholder="Enter price" 
               value="<?php echo esc_attr($upsell_price); ?>"> 

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
      <p class="form-field" id="upsell-deal-details" >
        <?php if ( $upsell_product_id != 0 ): ?>

            Current upsell offer is: 
              <strong id="upsell-offered-price"><?php echo wc_price($offered_price); ?></strong> 
            instead of regular price 
              <strong id="upsell-regular-price"><?php echo wc_price($regular_price); ?></strong> 
            for 
              <strong id="upsell-product-name"><?php echo $offer_product_name; ?></strong>

         <?php else: ?>

          <?php
            echo __( 'Once you select a product and set the price, resulting upsell deal will be shown here.', WCLU_TEXT_DOMAIN );
          ?>
              
        <?php endif; ?>
      </p>
    <?php

    $tab_html = ob_get_contents();
    ob_end_clean();
   
    return $tab_html;
  }
  
}