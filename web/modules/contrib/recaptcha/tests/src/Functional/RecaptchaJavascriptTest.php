<?php

namespace Drupal\Tests\recaptcha\Functional;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Test the recaptcha module using JavaScript.
 *
 * @see https://developers.google.com/recaptcha/docs/faq#id-like-to-run-automated-tests-with-recaptcha-what-should-i-do
 *
 * @group reCAPTCHA
 *
 * @dependencies recaptcha
 */
class RecaptchaJavascriptTest extends WebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['recaptcha_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  // These are test keys that will always validate.
  protected const SITE_KEY = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';
  protected const SECRET_KEY = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->config('recaptcha.settings')
      ->set('site_key', self::SITE_KEY)
      ->set('secret_key', self::SECRET_KEY)
      ->save();
  }

  /**
   * Test the recaptcha on a form loaded via ajax that also submits via ajax.
   */
  public function testRecaptchaOnAjaxForm() {
    // Load the /recaptcha-test page with the AJAX button.
    $path = Url::fromRoute('recaptcha_test.page')->toString();
    $this->drupalGet($path);

    // No recaptcha JS on the page.
    $this->assertSession()->responseNotContains('https://www.google.com/recaptcha/api.js', 'reCAPTCHA js is not present before the form is loaded via AJAX.');

    // Click the button.
    $this->click('a#load-ajax-form');

    // Once the form is loaded.
    $this->getSession()->wait(2000, '(jQuery("form[data-drupal-selector^=recaptcha-test-ajax-form]").length > 0)');
    $this->assertJsCondition('Drupal.behaviors.recaptcha', 100, 'recaptcha Drupal behaviors found.');

    // The recaptcha should be on the page.
    $this->assertSession()->responseContains('https://www.google.com/recaptcha/api.js', 'reCAPTCHA js has been added.');
    $grecaptcha = $this->getSession()->getPage()->find('css', 'form .g-recaptcha');
    $this->assertJsCondition('window.grecaptcha !== undefined', 1000, 'The Google recaptcha library is loaded.');
    $this->assertNotEmpty($grecaptcha, 'g-recaptcha element is found.');

    // Test form submission.
    // First, try a submission that will trigger the validation error handler.
    $this->submitForm([
      'email' => 'invalid@example.com',
    ], 'Submit');
    $messages = $this->getMessages();
    $this->assertNotEmpty($messages);
    $this->assertStringContainsString('Invalid email', $messages);
    $this->assertStringContainsString('The answer you entered for the CAPTCHA was not correct.', $messages);
    $this->assertStringNotContainsString('Form submit successful.', $messages);

    // Now submit again with a valid email.
    $this->submitForm([
      'email' => 'valid@example.com',
    ], 'Submit');
    $messages = $this->getMessages();
    $this->assertStringContainsString('The answer you entered for the CAPTCHA was not correct.', $messages);
    $this->assertStringNotContainsString('Form submit successful.', $messages);

    // We need to re-validate the captcha;
    // So click it,.
    $this->clickRecaptcha();
    // And submit for the last time.
    $this->submitForm([
      'email' => 'valid@email.com',
    ], 'Submit');
    $messages = $this->getMessages();
    $this->assertStringNotContainsString('The answer you entered for the CAPTCHA was not correct.', $messages);
    $this->assertStringContainsString('Form submit successful.', $messages);
  }

  /**
   * {@inheritdoc}
   */
  protected function submitForm(array $edit, $submit, $form_html_id = NULL) {
    parent::submitForm($edit, $submit, $form_html_id);

    // Because we're submitting the form via AJAX give it 500ms before we test
    // anything else with the response.
    $this->getSession()->wait(500);
  }

  /**
   * Click the captcha checkbox element and wait for it to be validated.
   *
   * @param int $timeout
   *   The time to wait for the recaptcha to get validated (in miliseconds).
   *
   * @throws \Behat\Mink\Exception\DriverException
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   */
  protected function clickRecaptcha($timeout = 2000) {
    $driver = $this->getSession()->getDriver();
    $recaptchaIFrame = $this->getSession()->getPage()->find('css', 'form .g-recaptcha iframe');
    $driver->switchToIFrame($recaptchaIFrame->getAttribute('name'));
    $recaptchaCheckbox = $driver->find('//span[@id="recaptcha-anchor"]');
    if (!empty($recaptchaCheckbox)) {
      $recaptchaCheckbox[0]->click();
      $this->getSession()->wait($timeout, 'document.getElementById("recaptcha-anchor").attributes["aria-checked"].value === true;');
    }
    else {
      $this->fail('Unable to find recaptcha checkbox.');
    }
    $driver->switchToWindow();
    $this->assertJsCondition('grecaptcha.getResponse() !== ""', $timeout, 'grecaptcha has response.');
  }

  /**
   * Search for messages in the last html page response.
   *
   * @return string
   *   The message.
   */
  public function getMessages($timeout = 2000) {
    $this->getSession()->wait($timeout, '(jQuery("div[data-drupal-messages]").length > 0)');
    $page = $this->getSession()->getPage();
    $messages = $page->findAll('css', 'div[data-drupal-messages]');
    $text = '';
    if (isset($messages)) {
      foreach ($messages as $message) {
        $text .= $message->getText();
      }
    }

    return $text;
  }

}
