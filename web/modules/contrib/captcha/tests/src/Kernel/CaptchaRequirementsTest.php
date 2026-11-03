<?php

declare(strict_types=1);

namespace Drupal\Tests\captcha\Kernel;

use Drupal\Core\Extension\InstallRequirementsInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\captcha\Hook\CaptchaRequirementsHooks;
use Drupal\image_captcha\Install\Requirements\ImageCaptchaRequirements;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the CAPTCHA and Image CAPTCHA requirements.
 *
 * @group captcha
 */
#[Group('captcha')]
#[RunTestsInSeparateProcesses]
final class CaptchaRequirementsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['captcha', 'image_captcha'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['captcha']);
    $this->container->get('module_handler')->loadInclude('captcha', 'install');
    $this->container->get('module_handler')->loadInclude('image_captcha', 'install');
  }

  /**
   * Tests the blocked submission counter on the status report.
   */
  public function testRuntimeRequirements(): void {
    $hooks = $this->container->get(CaptchaRequirementsHooks::class);

    // Nothing is reported while statistics are off.
    $this->assertSame([], $hooks->runtimeRequirements());
    $this->assertSame([], captcha_requirements('runtime'));

    $this->config('captcha.settings')->set('enable_stats', TRUE)->save();
    $this->container->get('state')->set('captcha.wrong_response_counter', 3);

    $requirements = $hooks->runtimeRequirements();
    $this->assertSame('Already 3 blocked form submissions', (string) $requirements['captcha_wrong_response_counter']['value']);

    // The legacy hook used by core older than 11.3 returns the same rows, and
    // nothing outside the runtime phase.
    $this->assertEquals($requirements, captcha_requirements('runtime'));
    $this->assertSame([], captcha_requirements('install'));
  }

  /**
   * Tests the Image CAPTCHA install requirements where GD is available.
   */
  public function testImageCaptchaInstallRequirements(): void {
    if (!function_exists('imagecreatetruecolor')) {
      $this->markTestSkipped('The GD extension is not available.');
    }

    $this->assertSame([], image_captcha_requirements('install'));
    $this->assertSame([], image_captcha_requirements('runtime'));

    // Core 11.2 and later use the class instead of the legacy hook.
    if (interface_exists(InstallRequirementsInterface::class)) {
      $this->assertSame([], ImageCaptchaRequirements::getRequirements());
    }
  }

}
