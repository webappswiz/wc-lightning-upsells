jQuery(document).ready(function () {


});

/**
 * Plugin link allowing to skip some upsell 
 * has the href consisting of this prefix + upsell ID.
 */
const prefixInSkippingLink = 'skip_lightning_';

/**
 * HTML Element containing an upsell info & contents
 * has the ID consisting of this prefix + upsell ID.
 */
const prefixInContainerId = 'lightning-upsell-';


/**
 * All info about upsells skipped by site visitor
 * is stored in the separate cookie.
 */
const cookieForSkippedUpsells = 'wclu-skipped-upsells';


let Wclu_Frontend = {

  init: function() {
    this.initSkipLinks();
  },

  initSkipLinks: function() {
    var elements = document.getElementsByTagName( "a" );

    for ( var i = 0; i < elements.length; i++ ) {

      if ( elements[i].href.includes( prefixInSkippingLink ) ) {
        elements[i].onclick = handleSkip;
      }
    }
  },
  
  saveSkippedUpsellToCookie: function( upsellId ) {

    // Get all cookies
    const cookies = document.cookie.split(';');

    // Find the plugin cookie
    for ( let i = 0; i < cookies.length; i++ ) {
      
      let cookie = cookies[i].trim();

      if ( cookie.startsWith( cookieForSkippedUpsells + '=' ) ) {
        
        let currentValue = cookie.substring( cookieForSkippedUpsells.length + 1 );

        // Append the new string
        let newValue = currentValue + '|' + upsellId;

        // Set the updated cookie
        document.cookie = `${cookieForSkippedUpsells}=${newValue}; path=/`;

        return;
      }
    }

    // At this point of the code execution 
    // we know that plugin cookie is not found. Let's create a fresh one
    
    let newValue = '|' + upsellId;
    document.cookie = `${cookieForSkippedUpsells}=${newValue}; path=/`;

  }

}


/**
 * Handler of the click event for "Skip Upsell" links
 * 
 * @param {type} e
 * @returns void
 */
function handleSkip(e) {

  e.preventDefault();

  const chunks = this.href.toString().split( prefixInSkippingLink );

  if ( chunks[1] && parseInt( chunks[1] ) > 0 ) {

    const upsellId = parseInt( chunks[1] );

    let upsell_container = document.getElementById( prefixInContainerId + upsellId );

    if ( upsell_container ) {
      upsell_container.style.display = "none";
      Wclu_Frontend.saveSkippedUpsellToCookie( upsellId );
    }
  }
}


Wclu_Frontend.init();
