/**
 * @file
 * Contains the definition of the behaviour recaptcha.
 */

(function ($, Drupal) {
  Drupal.behaviors.recaptcha = {
    attach(context) {
      $('.g-recaptcha', context).each(function () {
        if (
          typeof grecaptcha === 'undefined' ||
          typeof grecaptcha.render !== 'function'
        ) {
          return;
        }
        if ($(this).closest('body').length > 0) {
          if ($(this).hasClass('recaptcha-processed')) {
            grecaptcha.reset();
          } else {
            grecaptcha.render(this, $(this).data());
            $(this).addClass('recaptcha-processed');
          }
        }
      });
    },
  };

  window.drupalRecaptchaOnload = function () {
    $('.g-recaptcha').each(function () {
      if (!$(this).hasClass('recaptcha-processed')) {
        grecaptcha.render(this, $(this).data());
        $(this).addClass('recaptcha-processed');
      }
    });
  };
})(jQuery, Drupal);
