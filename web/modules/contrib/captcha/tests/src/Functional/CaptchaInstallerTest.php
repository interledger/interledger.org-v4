<?php

declare(strict_types=1);

namespace Drupal\Tests\captcha\Functional;

use Drupal\Component\Serialization\Yaml;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests installing a site while the global CAPTCHA is enabled.
 *
 * The non-interactive installer used here is the one drush site:install
 * runs, so it submits install_configure_form without a CAPTCHA response.
 *
 * @group captcha
 */
#[Group('captcha')]
#[RunTestsInSeparateProcesses]
final class CaptchaInstallerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'captcha_installer_test';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment(): void {
    parent::prepareEnvironment();

    // Write a profile that installs captcha and enables it globally before
    // the site configuration form is submitted, as an install from existing
    // configuration does.
    $path = $this->siteDirectory . '/profiles/' . $this->profile;
    mkdir($path, 0777, TRUE);
    file_put_contents("$path/{$this->profile}.info.yml", Yaml::encode([
      'type' => 'profile',
      'core_version_requirement' => '*',
      'name' => 'CAPTCHA installer test',
      'install' => ['captcha'],
    ]));
    file_put_contents("$path/{$this->profile}.install", <<<'PHP'
<?php

/**
 * Implements hook_install().
 */
function captcha_installer_test_install() {
  \Drupal::configFactory()->getEditable('captcha.settings')
    ->set('enable_globally', TRUE)
    ->save();
}

PHP);
  }

  /**
   * Tests that the global CAPTCHA does not block the installer.
   */
  public function testInstallWithGlobalCaptcha(): void {
    $this->assertNotEmpty($this->config('captcha.settings')->get('enable_globally'));

    // Once installed, forms get the global CAPTCHA again. The shipped
    // user_pass point is disabled, which opts the form out, so remove it.
    $this->container->get('entity_type.manager')
      ->getStorage('captcha_point')
      ->load('user_pass')
      ->delete();
    $this->drupalGet('user/password');
    $this->assertSession()->fieldExists('captcha_response');
  }

}
