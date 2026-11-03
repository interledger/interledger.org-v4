<?php

declare(strict_types=1);

namespace Drupal\Tests\captcha\Functional;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the CAPTCHA on forms rebuilt by a limited validation button.
 *
 * @group captcha
 */
#[Group('captcha')]
#[RunTestsInSeparateProcesses]
final class CaptchaLimitedValidationRebuildTest extends CaptchaWebTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['captcha', 'captcha_test'];

  /**
   * The CAPTCHA survives a rebuild and still validates afterwards.
   */
  public function testCaptchaSurvivesLimitedValidationRebuild(): void {
    $this->drupalGet('captcha-test/limited-rebuild');
    $this->assertSession()->fieldExists('captcha_response');
    $captcha_sid = $this->getCaptchaSidFromForm();

    // The rebuilt form still shows the challenge, in the same session, and
    // the partial submission was not counted as an attempt.
    $this->submitForm(['name' => 'Commerce'], 'Recalculate');
    $this->assertSession()->pageTextNotContains('Form submitted!');
    $this->assertSession()->fieldExists('captcha_response');
    $this->assertSame($captcha_sid, $this->getCaptchaSidFromForm());
    $this->assertSame(0, $this->getAttempts($captcha_sid));

    // A wrong answer is still rejected.
    $this->submitForm(['captcha_response' => 'wrong'], 'Submit');
    $this->assertSession()->pageTextContains(self::CAPTCHA_WRONG_RESPONSE_ERROR_MESSAGE);
    $this->assertSession()->pageTextNotContains('Form submitted!');

    // Rebuild again, then answer correctly.
    $this->submitForm(['name' => 'Shipping'], 'Recalculate');
    $this->assertSession()->fieldExists('captcha_response');
    $this->submitForm(['captcha_response' => 'Test 123'], 'Submit');
    $this->assertSession()->pageTextContains('Form submitted!');
  }

  /**
   * Returns the number of attempts recorded for a CAPTCHA session.
   */
  private function getAttempts(int $captcha_sid): int {
    return (int) \Drupal::database()->select('captcha_sessions', 'cs')
      ->fields('cs', ['attempts'])
      ->condition('csid', $captcha_sid)
      ->execute()
      ->fetchField();
  }

}
