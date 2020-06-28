jQuery(document).ready(function($) {
  jQuery('.ivole-order, [class^=ivole-o-]').click(function(e) {
    e.preventDefault();
    var classList = $(this).attr('class').split(/\s+/);
    var order_id = -1;
    for (var i = 0; i < classList.length; i++) {
        if (classList[i].startsWith('ivole-o-')) {
            order_id = parseInt(classList[i].substring(8), 10);
        }
    }
    if (order_id > -1) {
      var sending = 'Sending...';
      if( $(this).hasClass( 'ivole-order-cr' ) ) {
        sending = 'Syncing...';
      }
      if( sending !== $(this).closest( '#post-' + order_id ).find( '.ivole-review-reminder' ).text() ) {
        $(this).closest( '#post-' + order_id ).find( '.ivole-review-reminder' ).text( sending );
        var data = {
          'action': 'ivole_manual_review_reminder',
          'order_id': order_id
        };
        jQuery.post(ajaxurl, data, function(response) {
          if(response.code === 0) {
            jQuery( '#post-' + response.order_id ).find( '.ivole-review-reminder' ).text( response.message );
          } else if (response.code === 1) {
            jQuery( '#post-' + response.order_id ).find( '.ivole-review-reminder' ).text( 'Error code 1' );
          } else if (response.code === 2) {
            jQuery( '#post-' + response.order_id ).find( '.ivole-review-reminder' ).text( 'Error code 2 (' + response.message + ').' );
          } else if (response.code === 3) {
            jQuery( '#post-' + response.order_id ).find( '.ivole-review-reminder' ).text( response.message );
          } else if (response.code === 7) {
            jQuery( '#post-' + response.order_id ).find( '.ivole-review-reminder' ).html( response.message );
          } else if (response.code === 9) {
            jQuery( '#post-' + response.order_id ).find( '.ivole-review-reminder' ).html( response.message );
          } else if (response.code === 10) {
            jQuery( '#post-' + response.order_id ).find( '.ivole-review-reminder' ).html( response.message );
          } else {
            jQuery( '#post-' + response.order_id ).find( '.ivole-review-reminder' ).text( response.message );
          }
        }, 'json');
      }
    }
  });
});
