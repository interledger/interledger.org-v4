<?php

declare(strict_types=1);

namespace Drupal\Tests\captcha\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\captcha\Constants\CaptchaConstants;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the CAPTCHA with buttons that use #limit_validation_errors.
 *
 * @group captcha
 */
#[Group('captcha')]
#[RunTestsInSeparateProcesses]
final class CaptchaLimitValidationErrorsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['captcha', 'captcha_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Require the CAPTCHA on every submission, so that a correct answer for
    // one button does not exempt the next.
    $this->config('captcha.settings')
      ->set('persistence', CaptchaConstants::CAPTCHA_PERSISTENCE_SHOW_ALWAYS)
      ->save();
  }

  /**
   * Tests which buttons validate the CAPTCHA.
   */
  public function testLimitValidationErrors(): void {
    $session = $this->assertSession();
    $wrong_response = 'The answer you entered for the CAPTCHA was not correct.';

    foreach (['Before', 'After'] as $prefix) {
      // Buttons whose validation sections cover the CAPTCHA must reject a
      // wrong answer and accept the right one.
      foreach (['response', 'element'] as $key) {
        $button = "$prefix: $key";

        $this->drupalGet('captcha-test/limit-validation-errors');
        $this->submitForm(['captcha_response' => 'wrong'], $button);
        $session->pageTextContains($wrong_response);
        $session->pageTextNotContains('Form submitted!');

        $this->drupalGet('captcha-test/limit-validation-errors');
        $this->submitForm(['captcha_response' => ''], $button);
        $session->pageTextNotContains('Form submitted!');

        $this->drupalGet('captcha-test/limit-validation-errors');
        $this->submitForm(['captcha_response' => 'Test 123'], $button);
        $session->pageTextNotContains($wrong_response);
        $session->pageTextContains('Form submitted!');
      }

      // A button that limits validation to other elements skips the CAPTCHA.
      $this->drupalGet('captcha-test/limit-validation-errors');
      $this->submitForm(['captcha_response' => 'wrong'], "$prefix: skip");
      $session->pageTextNotContains($wrong_response);
      $session->pageTextContains('Form submitted!');
    }
  }

}
