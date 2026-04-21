/**
 * @file
 * Hook slick slider slick/js/slick.load.js
 */
(function ($, Drupal, drupalSettings) {

    'use strict';
   
    Drupal.behaviors.slickConfig = {
        attach: function (context) {
            window.onresize = function(event) {
                if($(".node-dsu-component-page-edit-form .paragraph--type--c-teasercycle").length) {
                    var editLeftColumnWidth = $(".node-dsu-component-page-edit-form .layout-region-node-main").width();
                    $(".paragraph--view-mode--preview").css("width", (editLeftColumnWidth-72));
                }
            };
        }
    };
})(jQuery, Drupal, drupalSettings);