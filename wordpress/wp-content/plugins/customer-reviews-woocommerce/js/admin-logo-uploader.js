jQuery( document ).ready( function() {
    jQuery( '.ivole-upload-shop-logo-submit' ).click( function( e ) {
        var api_url = 'https://z4jhozi8lc.execute-api.us-east-1.amazonaws.com/v1/upload-logo';
        e.preventDefault();
        jQuery( '#ivole_remove_shop_logo_submit' ).prop( 'disabled', true );
        var logo = jQuery( '#ivole_upload_shop_logo' ).first();
        var license_key = jQuery( '#ivole_license_key' ).val();
        var domain = jQuery( '#ivole_shop_domain' ).val();
        var name = jQuery( '#ivole_shop_name' ).val();
        if ( !logo[0].files.length ) {
            jQuery( '#ivole_upload_shop_logo_result' ).text( 'Please select a file to upload' );
            setTimeout( function() {
                jQuery( '#ivole_upload_shop_logo_result' ).html( '&nbsp;' );
            }, 3000 );
        }
        if ( !license_key.length ) {
            jQuery( '#ivole_upload_shop_logo_result' ).html( 'Please enter a valid license key' );
            setTimeout( function() {
                jQuery( '#ivole_upload_shop_logo_result' ).html( '&nbsp;' );
            }, 3000 );
        }
        if ( !domain.length ) {
            jQuery( '#ivole_upload_shop_logo_result' ).html( 'Invalid shop domain' );
            setTimeout( function() {
                jQuery( '#ivole_upload_shop_logo_result' ).html( '&nbsp;' );
            }, 3000 );
        }
        if ( !name.length ) {
            jQuery( '#ivole_upload_shop_logo_result' ).html( 'Invalid shop name' );
            setTimeout( function() {
                jQuery( '#ivole_upload_shop_logo_result' ).html( '&nbsp;' );
            }, 3000 );
        }
        if ( logo[0].files.length && license_key.length && domain.length && name.length ) {
          var data = new FormData();
          data.append( 'image', logo[0].files[0] );
          data.append( 'shopDomain', domain );
          data.append( 'shopName', name );
          data.append( 'licenseKey', license_key );
          jQuery.ajax( {
            type: 'POST',
            url: api_url,
            data: data,
            cache: false,
            processData: false,
            contentType: false,
            xhr: function () {
              var jqXHR = null;
              if (window.ActiveXObject) {
                jqXHR = new window.ActiveXObject("Microsoft.XMLHTTP");
              } else {
                jqXHR = new window.XMLHttpRequest();
              }
              //Upload progress
              jqXHR.upload.addEventListener("progress", function(evt) {
                if (evt.lengthComputable) {
                  var percentComplete = Math.round((evt.loaded * 100) / evt.total);
                  //Do something with upload progress
                  jQuery( '#ivole_upload_shop_logo_result' ).text( 'Uploading: ' + percentComplete + '%' );
                  //console.log('Uploaded percent', percentComplete);
                }
              }, false);
              return jqXHR;
            }
          } ).then( function( response ) {
            if (response.logo) {
                jQuery.post( ajaxurl, {
                    action: 'ivole_update_shop_logo',
                    logo_url: response.logo
                }, function( update_response ) {
                    if ( !update_response.error ) {
                      //jQuery( '#ivole_shop_logo' ).attr( 'src', update_response.logo_result + '?t=' + Date.now() );
                      jQuery( '#ivole_shop_logo' ).attr( 'src', update_response.logo_result );
                      jQuery( '#ivole_remove_shop_logo_submit' ).css( 'visibility', 'visible' );
                      jQuery( '#ivole_remove_shop_logo_submit' ).prop( 'disabled', false );
                      jQuery( '#ivole_upload_shop_logo' ).val( '' );
                    }
                    jQuery( '#ivole_upload_shop_logo_result' ).html( update_response.logo_message );
                    setTimeout( function() {
                        jQuery( '#ivole_upload_shop_logo_result' ).html( '&nbsp;' );
                    }, 3000 );
                } );
            }
            if ( response.error ) {
                jQuery( '#ivole_upload_shop_logo_result' ).html( response.error );
                setTimeout( function() {
                    jQuery( '#ivole_upload_shop_logo_result' ).html( '&nbsp;' );
                }, 3000 );
            }
          } ).fail( function( response ) {
            if ( response.responseJSON ) {
                jQuery( '#ivole_upload_shop_logo_result' ).html( response.responseJSON.error );
                setTimeout( function() {
                    jQuery( '#ivole_upload_shop_logo_result' ).html( '&nbsp;' );
                }, 3000 );
            }
          } );
        }
    });

    jQuery( '.ivole-remove-shop-logo-submit' ).click( function( e ) {
        var api_url = 'https://z4jhozi8lc.execute-api.us-east-1.amazonaws.com/v1/delete-logo';
        e.preventDefault();
        //disable buttons
        jQuery( '#ivole_upload_shop_logo_submit' ).prop( 'disabled', true );
        var license_key = jQuery( '#ivole_license_key' ).val();
        var domain = jQuery( '#ivole_shop_domain' ).val();
        if ( license_key.length && domain.length ) {
          jQuery('#ivole_upload_shop_logo_result').html('Removing...');
          jQuery.post({
            type: 'POST',
            url: api_url,
            data: JSON.stringify({
              shopDomain: domain,
              licenseKey: license_key
            }),
            cache: false,
            processData: false,
            contentType: false
          }).then(function(response) {
            if (response.status && response.status == 'OK') {
              jQuery.post(ajaxurl, {
                action: 'ivole_update_shop_logo',
                logo_url: ''
              }, function(update_response) {
                if (!update_response.error) {
                  jQuery('#ivole_shop_logo').attr('src', '');
                  jQuery('#ivole_remove_shop_logo_submit').css('visibility', 'hidden');
                  jQuery('#ivole_upload_shop_logo_submit').prop('disabled', false);
                }
                jQuery('#ivole_upload_shop_logo_result').html(update_response.logo_message);
                setTimeout(function() {
                  jQuery('#ivole_upload_shop_logo_result').html('&nbsp;');
                }, 3000);
              });
            }
          });
          }
          });
} );
