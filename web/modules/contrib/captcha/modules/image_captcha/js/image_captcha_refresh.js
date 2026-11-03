/**
 * @file
 * Attaches behaviors for the zipang captcha refresh module.
 */

(function ($) {
  /**
   * Attaches jQuery validate behavior to forms.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *  Attaches the outline behavior to the right context.
   */
  Drupal.behaviors.CaptchaRefresh = {
    attach(context) {
      $('.reload-captcha', context)
        .not('.processed')
        .on('click', function () {
          $(this).addClass('processed');
          const $form = $(this).parents('form');
          // Send post query for getting new captcha data.
          const date = new Date();
          const baseUrl = document.location.origin;
          const url = `${baseUrl.replace(/\/$/g, '')}/${$(this)
            .attr('href')
            .replace(/^\//g, '')}?${date.getTime()}`;
          // Adding loader.
          $('.captcha').addClass('captcha--loading');
          $.get(
            url,
            {},
            function (response) {
              if (response.status === 1) {
                $('.captcha', $form).find('img').attr('src', response.data.url);
                $form[0].querySelector('input[name=captcha_sid]').value =
                  response.data.sid;
                $form[0].querySelector('input[name=captcha_token]').value =
                  response.data.token;
                $('.captcha').removeClass('captcha--loading');
              } else {
                alert(response.message);
              }
            },
            'json',
          );
          return false;
        });
    },
  };
})(jQuery);
