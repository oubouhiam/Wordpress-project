jQuery(function($) {
    jQuery(".ivole-comment-a").click(function(t) {
        t.preventDefault();
        var o = jQuery(".pswp")[0];
        var pics = jQuery(this).parent().parent().find("img");
        var this_pic = jQuery(this).find("img");
        var inx = 0;
        if(pics.length > 0 && this_pic.length > 0) {
          var a = [];
          for(i=0; i<pics.length; i++) {
            a.push({
              src: pics[i].src,
              w: pics[i].naturalWidth,
              h: pics[i].naturalHeight,
              title: pics[i].alt
            });
            if(this_pic[0].src == pics[i].src) {
              inx = i;
            }
          }
          var r = {
            index: inx
          };
          new PhotoSwipe(o,PhotoSwipeUI_Default,a,r).init();
        }
      });

      //show lightbox when click on images attached to reviews
      jQuery(".ivole-video-a, .iv-comment-video-icon").click(function(t) {
        if( ! jQuery( "#iv-comment-videos-id" ).hasClass( "iv-comment-videos-modal" ) ) {
          var tt = t.target.closest("[id^='iv-comment-video-id-']");
          var iid = "#" + tt.id;
          jQuery( "#iv-comment-videos-id" ).addClass( "iv-comment-videos-modal" );
          jQuery( iid ).addClass( "iv-comment-video-modal" );
          jQuery( iid ).find( "video" ).prop( "controls", true );
          jQuery( iid ).find( ".iv-comment-video-icon" ).hide();
          jQuery( iid ).find( "video" ).get(0).play();
          jQuery( iid ).css({
            "top": "50%",
            "margin-top": function() { return -$(this).outerHeight() / 2 }
          });
          return false;
        }
      });

      //close video lightbox
      jQuery("#iv-comment-videos-id").click(function(t) {
        if( jQuery( "#iv-comment-videos-id" ).hasClass( "iv-comment-videos-modal" ) ) {
          jQuery( "#iv-comment-videos-id" ).removeClass( "iv-comment-videos-modal" );
          var vids = jQuery( "#iv-comment-videos-id" ).find("[id^='iv-comment-video-id-']");
          var i = 0;
          var iid = "";
          for( i = 0; i < vids.length; i++ ) {
            iid = "#" + vids[i].id;
            if( jQuery( iid ).hasClass( "iv-comment-video-modal" ) ) {
              jQuery( iid ).removeClass( "iv-comment-video-modal" );
              jQuery( iid ).find( "video").get(0).pause();
              jQuery( iid ).find( "video" ).prop( "controls", false );
              jQuery( iid ).find( ".iv-comment-video-icon" ).show();
              jQuery( iid ).removeAttr("style");
            }
          }
          return false;
        }
      });

      //show a div with a checkbox to send a copy of reply to CR
      jQuery( '#the-comment-list' ).on( 'click', '.comment-inline', function (e) {
        var $el = $( this ), action = 'replyto';
        if ( 'undefined' !== typeof $el.data( 'action' ) ) {
    			action = $el.data( 'action' );
    		}
        if ( action == 'replyto' ) {
          if ( $el.hasClass( 'ivole-comment-inline' ) || $el.hasClass( 'ivole-reply-inline' ) ) {
            //jQuery('#ivole_replyto_cr_checkbox').prop('checked','checked');
            jQuery('#ivole_replyto_cr_checkbox').val('no');
            jQuery( '#ivole_replytocr' ).show();
          } else {
            jQuery( '#ivole_replytocr' ).hide();
          }
        }
        return false;
      });

      //
      jQuery('#ivole_replyto_cr_checkbox').change(function() {
        if(jQuery(this).prop('checked')) {
          jQuery(this).val('yes');
        } else {
          jQuery(this).val('no');
        }
      });
});
