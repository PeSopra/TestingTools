(function ($, Drupal, once, settings) {
  'use strict';

  Drupal.behaviors.ln_c_cardgrid = {
    attach: function attach(context, settings) {
      $(once('cardgrid-dismiss-focus', '.paragraph--type--ln-c-grid-card-item')).on('keydown', function (e) {
        if(e.key === "Escape" || e.key === "Esc"){
          $(this).blur();
        }
      });
    }
  }

})(jQuery, Drupal, once, drupalSettings);
