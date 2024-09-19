/* This code is used in admin area for Lightning Upsells */
jQuery(document).ready(function( $ ){
  
  // Initialise CSS highlighter
  if ( wclu_settings.use_codemirror ) { // wclu_settings is provided by wp_localize_script() 
    wp.codeEditor.initialize( jQuery( '#wclu_custom_css' ), wclu_settings.wclu_custom_css );
  }
  
  // Initialise product dropdowns
  
  if ( document.getElementById( 'wclu_upsell_product_id') ) {
    $( '#wclu_upsell_product_id' ).selectWoo();
  }
  
    if ( document.getElementById( 'wclu_upsell_cart_contents') ) {
      console.log('Be be be');
    $( '#wclu_upsell_cart_contents' ).selectWoo();
  }
  else {
      console.log('Oh well');
  }
  
  // Enable tabbed panels
  $( document.body )
    .on( 'wclu-init-tabbed-panels', function () {
  
    $( 'ul.wc-tabs' ).show();
    $( 'ul.wc-tabs a' ).on( 'click', function ( e ) {
      e.preventDefault();
      var panel_wrap = $( this ).closest( 'div.panel-wrap' );
      $( 'ul.wc-tabs li', panel_wrap ).removeClass( 'active' );
      $( this ).parent().addClass( 'active' );
      $( 'div.panel', panel_wrap ).hide();
      $( $( this ).attr( 'href' ) ).show( 0, function () {
      $( this ).trigger( 'woocommerce_tab_shown' );
      } );
    } );
  
  }).trigger( 'wclu-init-tabbed-panels' );
  });
  
  
  