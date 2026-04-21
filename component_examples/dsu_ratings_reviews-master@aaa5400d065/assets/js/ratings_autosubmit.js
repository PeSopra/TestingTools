(function ($, Drupal, once) {

  Drupal.behaviors.RatingsAutoSubmit = {
    attach: function(context) {

      // the change event bubbles so we only need to bind it to the outer form
      $(once('ratings-auto-submit', '.auto-submit-click', context))
        .change(function (e) {
          var formButton = $(this).closest('form').find('.form-submit');
          if (formButton) {
            formButton.click();
          };
        });

      $(once('ratings-recommend', 'input[data-drupal-selector="edit-recommend-checkbox"]', context))
        .change(function (e) {
          if ($(this).prop('checked')) {
            $('input[data-drupal-selector="edit-recommend"]').val(1);
          }
          else {
            $('input[data-drupal-selector="edit-recommend"]').val('All');
          }
          var formButton = $(this).closest('form').find('.form-submit');
          if (formButton) {
            formButton.click();
          };
        });


      $(once('ratings-useful', 'input[data-drupal-selector="edit-sort-by-useful-checkbox"]', context))
        .change(function (e) {
          if ($(this).prop('checked')) {
            $('input[data-drupal-selector="edit-sort-by"]').val('count');
            $('input[data-drupal-selector="edit-sort-order"]').val('DESC');
          }
          else {
            $('input[data-drupal-selector="edit-sort-by"]').val('created');
            $('input[data-drupal-selector="edit-sort-order"]').val('DESC');
          }
          var formButton = $(this).closest('form').find('.form-submit');
          if (formButton) {
            formButton.click();
          }
        });

    }
  };
}(jQuery, Drupal, once));
