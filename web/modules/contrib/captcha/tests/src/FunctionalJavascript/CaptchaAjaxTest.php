<?php

declare(strict_types=1);

namespace Drupal\Tests\captcha\FunctionalJavascript;

use Drupal\captcha\Constants\CaptchaConstants;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests CAPTCHA on an AJAX form.
 *
 * @group captcha
 */
#[Group('captcha')]
#[RunTestsInSeparateProcesses]
final class CaptchaAjaxTest extends WebDriverTestBase {

  /**
   * Wrong response error message.
   */
  const CAPTCHA_WRONG_RESPONSE_ERROR_MESSAGE = 'The answer you entered for the CAPTCHA was not correct.';

  /**
   * Unknown CSID error message.
   */
  const CAPTCHA_UNKNOWN_CSID_ERROR_MESSAGE = 'CAPTCHA validation error: unknown CAPTCHA session ID. Contact the site administrator if this problem persists.';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'captcha_ajax_form_test',
  ];

  /**
   * Get the solution of the math CAPTCHA from the current form in the browser.
   *
   * @param int $delta
   *   A number to add to the computed solution (default: 0).
   *
   * @return string
   *   Calculated Math solution.
   */
  protected function getMathCaptchaSolutionFromForm(int $delta = 0): string {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert */
    $assert_session = $this->assertSession();

    // The math question is in the label: "Math question (@x + @y =)". Read
    // the markup rather than the visible text, so a visually hidden part of
    // the label is still included.
    $challenge = $assert_session->elementExists('css', '.form-item-captcha-response label');
    $challenge = strip_tags($challenge->getHtml());

    // Extract terms and operator from challenge.
    $matches = [];
    preg_match('/\\s*(\\d+)\\s*(-|\\+)\\s*(\\d+)\\s*=\\s*/', $challenge, $matches);
    // Solve the challenge.
    $a = (int) $matches[1];
    $b = (int) $matches[3];
    $solution = $matches[2] == '-' ? $a - $b : $a + $b;

    return \strval($solution + $delta);
  }

  /**
   * Data provider for ::testAjaxFormWithCaptcha().
   */
  public static function providerTestAjaxFormWithCaptcha(): array {
    return [
      'challenge succeeded' => [
        TRUE,
      ],
      'challenge failed' => [
        FALSE,
      ],
    ];
  }

  /**
   * Tests the AJAX form loader without a CAPTCHA point.
   */
  public function testAjaxFormLoader(): void {
    $clicks = 3;
    $this->drupalGet('captcha_ajax_form_test');

    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert */
    $assert_session = $this->assertSession();
    $assert_session->buttonExists('Load')->click();

    for ($i = 0; $i < $clicks; ++$i) {
      $assert_session->assertWaitOnAjaxRequest();
      $assert_session->buttonExists('Submit')->click();
    }

    $assert_session->pageTextContains('AJAX Form Loader');
    $assert_session->statusMessageContains('Successfully submitted!');
    $assert_session->statusMessageContains('Count: ' . $clicks);
  }

  /**
   * Tests the AJAX form with a challenge.
   *
   * @param bool $succeed
   *   TRUE to succeed at the challenge, FALSE to fail at it.
   *
   * @dataProvider providerTestAjaxFormWithCaptcha
   */
  #[DataProvider('providerTestAjaxFormWithCaptcha')]
  public function testAjaxFormWithCaptcha(bool $succeed): void {
    // Set up the CAPTCHA point for the test form.
    $entity_type_manager = \Drupal::entityTypeManager();

    $captcha_point_storage = $entity_type_manager->getStorage('captcha_point');
    $captcha_point = $captcha_point_storage->create([
      'captchaType' => CaptchaConstants::CAPTCHA_MATH_CAPTCHA_TYPE,
      'formId' => 'captcha_ajax_form_test',
      'label' => 'captcha_ajax_form_test',
    ]);

    $captcha_point->save();

    // Load the test form.
    $this->drupalGet('captcha_ajax_form_test');

    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert */
    $assert_session = $this->assertSession();
    $assert_session->buttonExists('Load')->click();

    // Fill in either a good or bad response.
    $captcha = $assert_session->waitForField('captcha_response');
    $captcha->setValue($this->getMathCaptchaSolutionFromForm($succeed ? 0 : 1));

    // Submit, then perform assertions based on expectations.
    $assert_session->buttonExists('Submit')->click();
    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->pageTextNotContains(self::CAPTCHA_UNKNOWN_CSID_ERROR_MESSAGE);
    if ($succeed) {
      $assert_session->pageTextNotContains(self::CAPTCHA_WRONG_RESPONSE_ERROR_MESSAGE);
      $assert_session->statusMessageContains('Successfully submitted!');
    }
    else {
      $assert_session->pageTextContains(self::CAPTCHA_WRONG_RESPONSE_ERROR_MESSAGE);
      $assert_session->pageTextNotContains('Successfully submitted!');
    }
  }

}
