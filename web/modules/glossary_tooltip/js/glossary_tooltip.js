(function ($, Drupal) {
  Drupal.behaviors.glossary_tooltip = {
    attach: function (context) {
      context.querySelectorAll('.glossary-tooltip-link').forEach(function (tooltip) {
        tooltip.addEventListener('click', function (e) {
          tooltip.nextElementSibling.classList.toggle('hidden');
        });
      });
    },
  };
})(jQuery, Drupal, drupalSettings);
