<?php

declare(strict_types=1);

namespace Drupal\Tests\image_captcha\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests image CAPTCHA forms submitted with #limit_validation_errors.
 *
 * @group image_captcha
 */
#[Group('image_captcha')]
#[RunTestsInSeparateProcesses]
final class ImageCaptchaLimitedValidationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'captcha',
    'dblog',
    'image_captcha',
    'image_captcha_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that a limited-validation rebuild logs no missing form ID error.
   *
   * A file upload on a form with an image CAPTCHA used to log "Missing
   * required form ID on route ..." on every upload.
   */
  public function testLimitedValidationLogsNoError(): void {
    $this->drupalLogin($this->drupalCreateUser(['access content']));
    $this->drupalGet('/captcha-test/image-test-limited-validation');
    $this->assertSession()->elementExists('css', '#edit-captcha-image-wrapper > div.reload-captcha-wrapper > a.reload-captcha');

    $this->submitForm(['name' => 'test'], 'Upload');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldValueEquals('name', 'test');

    $logged = \Drupal::database()->select('watchdog', 'w')
      ->fields('w', ['message'])
      ->condition('type', 'image_captcha')
      ->execute()
      ->fetchCol();
    $this->assertSame([], $logged);
  }

}
