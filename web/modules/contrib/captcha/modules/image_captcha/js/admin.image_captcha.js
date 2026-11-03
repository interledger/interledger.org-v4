/**
 * @file
 * Contains helper js for Captcha admin pages.
 *
 * @TODO remove and use native states.
 */

(function ($) {
  Drupal.behaviors.captchaAdmin = {
    attach(context) {
      // Helper function to show/hide noise level widget.
      const noiseLevelShower = function (speed) {
        speed = typeof speed === 'undefined' ? 'slow' : speed;
        if (
          document.querySelector('#edit-image-captcha-dot-noise')?.checked ||
          document.querySelector('#edit-image-captcha-line-noise')?.checked
        ) {
          $('.form-item-image-captcha-noise-level').show(speed);
        } else {
          $('.form-item-image-captcha-noise-level').hide(speed);
        }
      };

      // Add onclick handler to the dot and line noise check boxes.
      $('#edit-image-captcha-dot-noise').on('click', noiseLevelShower);
      $('#edit-image-captcha-line-noise').on('click', noiseLevelShower);
      // Show or hide appropriately on page load.
      noiseLevelShower(0);

      // Helper function to show/hide smooth distortion widget.
      const smoothDistortionShower = function (speed) {
        speed = typeof speed === 'undefined' ? 'slow' : speed;
        if (
          document.querySelector('#edit-image-captcha-distortion-amplitude')
            ?.value > 0
        ) {
          $('.form-item-image-captcha-bilinear-interpolation').show(speed);
        } else {
          $('.form-item-image-captcha-bilinear-interpolation').hide(speed);
        }
      };

      // Add onchange handler to the distortion level select widget.
      $('#edit-image-captcha-distortion-amplitude').on(
        'change',
        smoothDistortionShower,
      );
      // Show or hide appropriately on page load.
      smoothDistortionShower(0);
    },
  };
})(jQuery);
