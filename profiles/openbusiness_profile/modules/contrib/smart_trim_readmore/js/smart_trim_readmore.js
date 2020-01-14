/**
 * @file
 * Defines the behavior of the Smart Trim Readmore module.
 */

(function ($, Drupal) {

  "use strict";

  /**
   * Behavior to initialize "More" and "Less" links.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.smartTrimReadmore = {
    attach: function (context) {
      $(context)
        .find('.smart-trim-readmore-summary .more-link')
        .once('init-smart-trim-readmore-links')
        .each(function () {
          $(this).click(function () {
            var summary = $(this).closest('.smart-trim-readmore-summary');
            summary.hide();
            summary.next('.smart-trim-readmore-output').slideDown(100);
            return false;
          });
        });

      $(context)
        .find('.smart-trim-readmore-output .less-link')
        .once('init-smart-trim-readmore-links')
        .each(function () {
          $(this).click(function () {
            var text = $(this).closest('.smart-trim-readmore-output');
            text.slideUp(100);
            text.prev('.smart-trim-readmore-summary').slideDown(100);
            return false;
          });
        });
    }
  };

})(jQuery, Drupal);
