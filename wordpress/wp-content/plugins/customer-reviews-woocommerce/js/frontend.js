jQuery(document).ready(function($) {
  //enable attachment of images to comments
  jQuery('form#commentform').attr( "enctype", "multipart/form-data" ).attr( "encoding", "multipart/form-data" );
  //prevent review submission if captcha is not solved
  jQuery("#commentform").submit(function(event) {
    if( ajax_object.ivole_recaptcha === '1' ) {
      var recaptcha = jQuery("#g-recaptcha-response").val();
      if (recaptcha === "") {
        event.preventDefault();
        alert("Please confirm that you are not a robot");
      }
    }
  });
  //show lightbox when click on images attached to reviews
  jQuery(".ivole-comment-a").click(function(t) {
    if(ajax_object.ivole_disable_lightbox === '0') {
        //only if lightbox is not disabled in settings of the plugin
        t.preventDefault();
        var o = jQuery(".pswp")[0];
        var pics = jQuery(this).parent().parent().find("img");
        var this_pic = jQuery(this).find("img");
        var inx = 0;
        if (pics.length > 0 && this_pic.length > 0) {
            var a = [];
            for (i = 0; i < pics.length; i++) {
                a.push({
                    src: pics[i].src,
                    w: pics[i].naturalWidth,
                    h: pics[i].naturalHeight,
                    title: pics[i].alt
                });
                if (this_pic[0].src == pics[i].src) {
                    inx = i;
                }
            }
            var r = {
                index: inx
            };
            new PhotoSwipe(o, PhotoSwipeUI_Default, a, r).init();
        }
    }
  });
  //register a listener for votes on for reviews
  jQuery("a.ivole-a-button-text").on("click", function(t) {
    t.preventDefault();
    var reviewIDhtml = jQuery(this).attr('id');
    if(reviewIDhtml != null) {
      var reviewID = reviewIDhtml.match(/\d+/)[0];
      var data = {
        "action": "ivole_vote_review",
        "reviewID": reviewID,
        "upvote": 1,
        "security": ajax_object.ajax_nonce
      };
      //check if it is upvote or downvote
      if(reviewIDhtml.indexOf("ivole-reviewyes-") >= 0) {
        data.upvote = 1;
      } else if(reviewIDhtml.indexOf("ivole-reviewno-") >= 0) {
        data.upvote = 0;
      } else {
        return;
      }
      jQuery("#ivole-reviewyes-" + reviewID).parent().parent().parent().parent().hide();
      jQuery("#ivole-reviewno-" + reviewID).parent().parent().parent().parent().hide();
      jQuery("#ivole-reviewvoting-" + reviewID).text(ajax_object.text_processing);
      jQuery.post(ajax_object.ajax_url, data, function(response) {
        if( response.code === 0 ) {
          jQuery("#ivole-reviewvoting-" + reviewID).text(ajax_object.text_thankyou);
        } else if( response.code === 1 ) {
          jQuery("#ivole-reviewvoting-" + reviewID).text(ajax_object.text_thankyou);
        } else if( response.code === 2 ) {
          jQuery("#ivole-reviewvoting-" + reviewID).text(ajax_object.text_thankyou);
        } else if( response.code === 3 ) {
          jQuery("#ivole-reviewvoting-" + reviewID).text(ajax_object.text_error1);
        } else {
          jQuery("#ivole-reviewvoting-" + reviewID).text(ajax_object.text_error2);
        }
      }, "json");
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
});
