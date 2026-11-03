<?php

declare(strict_types=1);

namespace Drupal\image_captcha\Install\Requirements;

use Drupal\Core\Extension\InstallRequirementsInterface;

/**
 * Install time requirements for the Image CAPTCHA module.
 *
 * Only core 11.2 and later load this class; older core uses
 * image_captcha_requirements(). Both share
 * _image_captcha_install_requirements().
 */
final class ImageCaptchaRequirements implements InstallRequirementsInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRequirements(): array {
    // Core loads this class by path during installation, before the module is
    // installed, so the .install file may not be loaded yet.
    include_once dirname(__DIR__, 3) . '/image_captcha.install';
    return _image_captcha_install_requirements();
  }

}
