jQuery(document).ready(function() {
  // Select all/none
  jQuery( '.ivole-new-settings' ).on( 'click', '.select_all', function() {
    jQuery( this ).closest( 'td' ).find( 'select option' ).attr( 'selected', 'selected' );
    jQuery( this ).closest( 'td' ).find( 'select' ).trigger( 'change' );
    return false;
  });

  jQuery( '.ivole-new-settings' ).on( 'click', '.select_none', function() {
    jQuery( this ).closest( 'td' ).find( 'select option' ).removeAttr( 'selected' );
    jQuery( this ).closest( 'td' ).find( 'select' ).trigger( 'change' );
    return false;
  });
});
