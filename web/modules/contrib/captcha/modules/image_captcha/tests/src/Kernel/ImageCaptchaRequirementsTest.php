<?php

declare(strict_types=1);

namespace Drupal\Tests\image_captcha\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests image_captcha_requirements() before the module is installed.
 *
 * @group image_captcha
 */
#[Group('image_captcha')]
#[RunTestsInSeparateProcesses]
final class ImageCaptchaRequirementsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['captcha'];

  /**
   * Tests the install phase requirements check of an uninstalled module.
   *
   * Core only loads the .install file of a module that is being installed;
   * the .module file is not loaded and, outside of the test runner, the
   * module's classes are not autoloadable, so the check must not depend on
   * either.
   */
  public function testInstallRequirements(): void {
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('image_captcha'));

    $path = \Drupal::service('extension.list.module')->getPath('image_captcha');
    require_once $this->root . '/' . $path . '/image_captcha.install';

    $requirements = image_captcha_requirements('install');

    // The test environment has the GD library, so there is nothing to report.
    $this->assertTrue(function_exists('imagepng'));
    $this->assertSame([], $requirements);
    // The check did not need image_captcha.module.
    $this->assertFalse(function_exists('_image_captcha_check_setup'));
  }

}
