jQuery(function($) {
  $( document ).on( 'click', '.ccfm-notice-hosting .notice-dismiss', function ( e ) {
    e.preventDefault();

    $.post( ajaxurl, {
        action: 'ccfm-notice-response',
        nonce: ccfm_admin.nonce,
      }, function( data ){}, 'json' );
  } );
});