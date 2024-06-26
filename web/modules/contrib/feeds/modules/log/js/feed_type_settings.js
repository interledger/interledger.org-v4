/**
 * @file
 * Utility functions to display settings summaries on vertical tabs.
 */

(function ($, Drupal) {
  Drupal.behaviors.feedsLogSetSummary = {
    attach(context) {
      const $context = $(context);

      $context.find('#edit-log-configuration').drupalSetSummary((context) => {
        const enabled = $(context).find(
          'input[name="log_configuration[status]"]',
        )[0].checked;
        if (enabled) {
          return Drupal.t('Logging enabled');
        }

        return Drupal.t('Logging disabled');
      });
    },
  };
})(jQuery, Drupal);
