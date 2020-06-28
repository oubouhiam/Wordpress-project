jQuery(document).ready(function() {
  jQuery('.cr-product-feed-categories-select').select2({
    placeholder: 'Select a category',
    allowClear: true,
    ajax: {
      url: ajaxurl,
      dataType: 'json',
      data: function (params) {
        return {
          q: params.term,
          action: 'cr_google_categories'
        };
      },
      processResults: function( data ) {
        return {
  				results: JSON.parse(data)
        };
      },
      cache: true
    },
    minimumInputLength: 2,
    width: '100%'
  });
  jQuery('.cr-product-feed-identifiers-select').select2({
    placeholder: 'Select a field',
    allowClear: true,
    width: '100%'
  });
});
