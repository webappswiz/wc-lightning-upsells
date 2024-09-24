<?php

class Wclu_Tab_Conditions extends Wclu_Core {

  /**
   * Renders the "Upsell Conditions" tab
   * 
   * @param int $upsell_id
   * @param array $upsell_settings
   * @param array $products
   * @return string
   */
  public static function get_tab_contents( int $upsell_id, array $upsell_settings, array $products ) {

		$postfield = self::METABOX_FIELD_NAME;

		ob_start();

		$cart_total_enabled                = intval( $upsell_settings['cart_total_enabled'] );
		$cart_contents_enabled             = intval( $upsell_settings['cart_contents_enabled'] );
		$cart_must_hold_all                = intval( $upsell_settings['cart_must_hold_all'] );

		$cart_total_condition              = $upsell_settings['cart_total_condition'];
		$cart_condition_type               = $upsell_settings['cart_condition_type'];
		$selected_products                 = (array) $upsell_settings['cart_contents'];

		$cart_condition_types = array(
			self::CART_CND_LESS              => __( 'Less than', WCLU_TEXT_DOMAIN ),
			self::CART_CND_LESS_EQUAL        => __( 'Less than or equal', WCLU_TEXT_DOMAIN ),
			self::CART_CND_GREATER           => __( 'Greater than', WCLU_TEXT_DOMAIN ),
			self::CART_CND_GREATER_EQUAL     => __( 'Greater than or equal', WCLU_TEXT_DOMAIN ),
		);

		$upsell_condition_rules = self::formulate_upsell_conditions( $upsell_settings, $cart_condition_types );
		?>
		<p class="form-field">
			<label>
				<strong><?php echo __( 'When to show this upsell?', WCLU_TEXT_DOMAIN ); ?></strong>
				<?php echo __( '(set the conditions that will trigger the display for this upsell)', WCLU_TEXT_DOMAIN ); ?>
			</label>
		</p>
		<p class="form-field">
			<label for="wclu_upsell_cart_total_condition">
				<input type="checkbox" name="<?php echo $postfield; ?>[cart_total_enabled]" value="1" <?php echo $cart_total_enabled ? 'checked' : ''; ?> >
				<?php echo __( 'Cart total:', WCLU_TEXT_DOMAIN ); ?>
			</label>

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
					 value="<?php echo esc_attr( $cart_total_condition ); ?>"> 

				<?php echo wc_help_tip( __( 'Enter amount for the cart total. Set to 0 to disable this condition', WCLU_TEXT_DOMAIN ) ); ?>
		</p>
		<p class="form-field">
			<label for="wclu_upsell_cart_contents">
				<input type="checkbox" name="<?php echo $postfield; ?>[cart_contents_enabled]" value="1" <?php echo $cart_contents_enabled ? 'checked' : ''; ?> >
				<?php echo __( 'Cart contains:', WCLU_TEXT_DOMAIN ); ?>
			</label>

			<select id="wclu_upsell_cart_contents" multiple="1" name="<?php echo $postfield; ?>[cart_contents][]" class="select" style="min-width: 70%;">
			<?php
			foreach ( $products as $key => $value ) {
				echo "<option value='$key' " . self::multi_selected( $key, $selected_products ) . "> $value </option>";
			}
			?>
			</select>

			<div>
			<?php if ( !$cart_must_hold_all ): ?>
				<p>Default mode: it is enough to have at least one of the selected products in the cart.</p>
			<?php endif; ?>
			<input type="checkbox" id="wclu_cart_must_hold_all" name="<?php echo $postfield; ?>[cart_must_hold_all]" value="1" <?php echo $cart_must_hold_all ? 'checked' : ''; ?> >
			<label for="wclu_cart_must_hold_all">
				Cart must hold all of chosen products
			</label>
			</div>

			<p class="form-field" id="upsell-condition-details" >
			<?php if ( $upsell_condition_rules ): ?>
				Current upsell conditions are:
				<ul style="list-style: square; padding-left: 20px;" >
				<?php foreach ( $upsell_condition_rules as $rule ): ?>
					<li><?php echo $rule; ?></li>
				<?php endforeach; ?>
				</ul>
				<?php else: ?>

				<?php echo __( 'Once you select a product and set the price, resulting upsell deal will be shown here.', WCLU_TEXT_DOMAIN ); ?>

			<?php endif; ?>
			</p>
		</p>
		<?php
		$tab_html = ob_get_contents();
		ob_end_clean();

		return $tab_html;
  }

  public static function multi_selected( $key, $values ) {
		if ( in_array( $key, $values ) ) {
			return " selected='selected' ";
		}

		return "";
  }

  public static function formulate_upsell_conditions( $upsell_settings, $cart_condition_types ) {
		$rules = array();

		if ( $upsell_settings['cart_total_enabled'] ?? 0 ) {

			$cart_total_rule = false;

			if ( $upsell_settings['cart_total_condition'] > 0 ) {
				$applied_condition = strtolower( $cart_condition_types[$upsell_settings['cart_condition_type']] );

				$total = wc_price( $upsell_settings['cart_total_condition'] );

				$cart_total_rule = "Visitor cart total must be $applied_condition $total";
			}

			if ( $cart_total_rule ) {
				$rules[] = $cart_total_rule;
			}
		}

		if ( $upsell_settings['cart_contents_enabled'] ?? 0 ) {

			$cart_must_contain = false;

			$count_products = count( $upsell_settings['cart_contents'] );

			if ( $count_products > 1 ) {
				if ( !$upsell_settings['cart_must_hold_all'] ) {
					$cart_must_contain = "Visitor cart must contain at least one of $count_products selected products";
				} else {
					$cart_must_contain = "Visitor cart must contain each one of $count_products selected products";
				}
			} elseif ( $count_products == 1 ) {
				$cart_must_contain = 'Visitor cart must contain the selected product';
			}

			if ( $cart_must_contain ) {
				$rules[] = $cart_must_contain;
			}
		}


		return $rules;
  }
}
