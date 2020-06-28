jQuery(document).ready(function($) {
  var ivole_email_color_options1 = { palettes: ['#2C5E66','#db4437','#0f9d58','#3f51b5','#cddc39','#4285f4'] };
  var ivole_email_color_options2 = { palettes: ['#000000','#2f4f4f','#696969','#c0c0c0','#dcdcdc','#ffffff'] };
  var ivole_email_color_options3 = { palettes: ['#1AB394','#85144b','#001f3f','#0074D9','#111111','#F012BE'] };
  jQuery('#ivole_email_color_bg').wpColorPicker(ivole_email_color_options1);
  jQuery('#ivole_email_color_text').wpColorPicker(ivole_email_color_options2);
  jQuery('#ivole_form_color_bg').wpColorPicker(ivole_email_color_options1);
  jQuery('#ivole_form_color_text').wpColorPicker(ivole_email_color_options2);
  jQuery('#ivole_form_color_el').wpColorPicker(ivole_email_color_options3);
  jQuery('#ivole_email_coupon_color_bg').wpColorPicker(ivole_email_color_options1);
  jQuery('#ivole_email_coupon_color_text').wpColorPicker(ivole_email_color_options2);
});
