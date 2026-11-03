<?php

namespace Drupal\Tests\captcha\Functional;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\captcha\Constants\CaptchaConstants;

/**
 * Tests CAPTCHA Persistence.
 *
 * @group captcha
 */
#[Group('captcha')]
#[RunTestsInSeparateProcesses]
class CaptchaPersistenceTest extends CaptchaWebTestBase {

  use StringTranslationTrait;

  /**
   * The captcha type to use for the tests.
   */
  const TEST_CAPTCHA_TYPE = 'captcha/Test';

  /**
   * The cacheable captcha type to use for the tests.
   */
  const TEST_CACHEABLE_CAPTCHA_TYPE = 'captcha_test/TestCacheable';

  /**
   * Modules to install for this Test class.
   *
   * @var array
   */
  protected static $modules = [
    'captcha_test',
  ];

  /**
   * Set up the persistence and CAPTCHA settings.
   *
   * @param int $persistence
   *   The persistence value.
   * @param string $captcha_type
   *   The CAPTCHA type.
   */
  private function setUpPersistence($persistence, $captcha_type) {
    $this->drupalLogin($this->adminUser);
    // Set persistence.
    $edit = ['persistence' => (string) $persistence];
    $this->drupalGet(self::CAPTCHA_ADMIN_PATH);
    $this->submitForm($edit, 'Save configuration');
    // Log admin out.
    $this->drupalLogout();

    // Set the provided CAPTCHA on user login and register forms.
    // We have to do this with the function captcha_set_form_id_setting()
    // (because the CAPTCHA admin form does not list the Test123 and
    // TestCacheable options).
    // We also have to do this after all usage of the CAPTCHA admin form
    // (because posting the CAPTCHA admin form would set the CAPTCHA to 'none').
    captcha_set_form_id_setting('user_login_form', $captcha_type);
    $this->drupalGet('user');
    $this->assertCaptchaPresence(TRUE);
    captcha_set_form_id_setting('user_register_form', $captcha_type);
    $this->drupalGet('user/register');
    $this->assertCaptchaPresence(TRUE);
  }

  /**
   * Check if Captcha sid present in form.
   *
   * @param string $captcha_sid_initial
   *   Captcha SID token.
   */
  protected function assertPreservedCsid($captcha_sid_initial) {
    $captcha_sid = $this->getCaptchaSidFromForm();
    $this->assertEquals($captcha_sid_initial, $captcha_sid,
      "CAPTCHA session ID should be preserved (expected: $captcha_sid_initial, found: $captcha_sid).");
  }

  /**
   * Check if message about SID present.
   *
   * @param string $captcha_sid_initial
   *   Captcha SID token.
   */
  protected function assertDifferentCsid($captcha_sid_initial) {
    $captcha_sid = $this->getCaptchaSidFromForm();
    $this->assertNotEquals($captcha_sid_initial, $captcha_sid, "CAPTCHA session ID should be different.");
  }

  /**
   * Test persistence always.
   */
  public function testPersistenceAlways() {
    // Set up of persistence and CAPTCHAs.
    $this->setUpPersistence(CaptchaConstants::CAPTCHA_PERSISTENCE_SHOW_ALWAYS, self::TEST_CAPTCHA_TYPE);

    // Go to login form and check if there is a CAPTCHA
    // on the login form (look for the title).
    $this->drupalGet('<front>');
    $this->assertCaptchaPresence(TRUE);
    $captcha_sid_initial = $this->getCaptchaSidFromForm();

    // Try to with wrong user name and password, but correct CAPTCHA.
    $edit = [
      'name' => 'foobar',
      'pass' => 'bazlaz',
      'captcha_response' => 'Test 123',
    ];
    $this->submitForm($edit, 'Log in', self::LOGIN_HTML_FORM_ID);
    // Check that there was no error message for the CAPTCHA.
    $this->assertCaptchaResponseAccepted();

    // Name and password were wrong, we should get an updated
    // form with a fresh CAPTCHA.
    $this->assertCaptchaPresence(TRUE);
    $this->assertPreservedCsid($captcha_sid_initial);

    // Post from again.
    $this->submitForm($edit, 'Log in', self::LOGIN_HTML_FORM_ID);
    // Check that there was no error message for the CAPTCHA.
    $this->assertCaptchaResponseAccepted();
    $this->assertPreservedCsid($captcha_sid_initial);
  }

  /**
   * Test persistence per form instance.
   */
  public function testPersistencePerFormInstance() {
    // Set up of persistence and CAPTCHAs.
    $this->setUpPersistence(CaptchaConstants::CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL_PER_FORM_INSTANCE, self::TEST_CAPTCHA_TYPE);

    // Go to login form and check if there is a CAPTCHA on the login form.
    $this->drupalGet('<front>');
    $this->assertCaptchaPresence(TRUE);
    $captcha_sid_initial = $this->getCaptchaSidFromForm();

    // Try to with wrong user name and password, but correct CAPTCHA.
    $edit = [
      'name' => 'foobar',
      'pass' => 'bazlaz',
      'captcha_response' => 'Test 123',
    ];
    $this->submitForm($edit, 'Log in', self::LOGIN_HTML_FORM_ID);
    // Check that there was no error message for the CAPTCHA.
    $this->assertCaptchaResponseAccepted();
    // There shouldn't be a CAPTCHA on the new form.
    $this->assertCaptchaPresence(FALSE);
    $this->assertPreservedCsid($captcha_sid_initial);

    // Start a new form instance/session.
    $this->drupalGet('node');
    $this->drupalGet('user');
    $this->assertCaptchaPresence(TRUE);
    $this->assertDifferentCsid($captcha_sid_initial);

    // Check another form.
    $this->drupalGet('user/register');
    $this->assertCaptchaPresence(TRUE);
    $this->assertDifferentCsid($captcha_sid_initial);
  }

  /**
   * Test Persistence per form type.
   */
  public function testPersistencePerFormType() {
    // Set up of persistence and CAPTCHAs.
    $this->setUpPersistence(CaptchaConstants::CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL_PER_FORM_TYPE, self::TEST_CAPTCHA_TYPE);

    // Go to login form and check if there is a CAPTCHA on the login form.
    $this->drupalGet('<front>');
    $this->assertCaptchaPresence(TRUE);
    $captcha_sid_initial = $this->getCaptchaSidFromForm();

    // Try to with wrong user name and password, but correct CAPTCHA.
    $edit = [
      'name' => 'foobar',
      'pass' => 'bazlaz',
      'captcha_response' => 'Test 123',
    ];
    $this->submitForm($edit, 'Log in', self::LOGIN_HTML_FORM_ID);
    // Check that there was no error message for the CAPTCHA.
    $this->assertCaptchaResponseAccepted();
    // There shouldn't be a CAPTCHA on the new form.
    $this->assertCaptchaPresence(FALSE);
    $this->assertPreservedCsid($captcha_sid_initial);

    // Start a new form instance/session.
    $this->drupalGet('node');
    $this->drupalGet('user');
    $this->assertCaptchaPresence(FALSE);
    $this->assertDifferentCsid($captcha_sid_initial);

    // Check another form.
    /** @var \Drupal\captcha\Entity\CaptchaPoint $captcha_point */
    $captcha_point = \Drupal::entityTypeManager()
      ->getStorage('captcha_point')
      ->load('user_register_form');
    $captcha_point->enable()->save();
    $this->drupalGet('user/register');
    $this->assertCaptchaPresence(TRUE);
    $this->assertDifferentCsid($captcha_sid_initial);
  }

  /**
   * Test Persistence "Only once".
   */
  public function testPersistenceOnlyOnce() {
    // Set up of persistence and CAPTCHAs.
    $this->setUpPersistence(CaptchaConstants::CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL, self::TEST_CAPTCHA_TYPE);

    // Go to login form and check if there is a CAPTCHA on the login form.
    $this->drupalGet('<front>');
    $this->assertCaptchaPresence(TRUE);
    $captcha_sid_initial = $this->getCaptchaSidFromForm();

    // Try to with wrong user name and password, but correct CAPTCHA.
    $edit = [
      'name' => 'foobar',
      'pass' => 'bazlaz',
      'captcha_response' => 'Test 123',
    ];
    $this->submitForm($edit, 'Log in', self::LOGIN_HTML_FORM_ID);
    // Check that there was no error message for the CAPTCHA.
    $this->assertCaptchaResponseAccepted();
    // There shouldn't be a CAPTCHA on the new form.
    $this->assertCaptchaPresence(FALSE);
    $this->assertPreservedCsid($captcha_sid_initial);

    // Start a new form instance/session.
    $this->drupalGet('node');
    $this->drupalGet('user');

    $this->assertCaptchaPresence(FALSE);
    $this->assertDifferentCsid($captcha_sid_initial);

    // Check another form.
    $this->drupalGet('user/register');
    $this->assertCaptchaPresence(FALSE);
    $this->assertDifferentCsid($captcha_sid_initial);
  }

  /**
   * Test Cacheable CAPTCHA Persistence always.
   */
  public function testCacheableCaptchaPersistenceAlways() {
    // Set up of persistence and CAPTCHAs.
    $this->setUpPersistence(CaptchaConstants::CAPTCHA_PERSISTENCE_SHOW_ALWAYS, self::TEST_CACHEABLE_CAPTCHA_TYPE);

    // Go to login form and check if there is a CAPTCHA
    // on the login form (look for the title).
    $this->drupalGet('<front>');
    $this->assertCaptchaPresence(TRUE);
    $captcha_sid_initial = $this->getCaptchaSidFromForm();

    // Submit login form with valid CAPTCHA but wrong credentials.
    $edit = [
      'name' => 'foobar',
      'pass' => 'bazlaz',
      'captcha_response' => 'Test 123',
    ];
    $this->submitForm($edit, 'Log in', self::LOGIN_HTML_FORM_ID);
    // Check that there was no error message for the CAPTCHA.
    $this->assertCaptchaResponseAccepted();

    // Name and password were wrong, we should get an updated
    // form with a fresh CAPTCHA.
    $this->assertCaptchaPresence(TRUE);
    $this->assertPreservedCsid($captcha_sid_initial);

    // Submit for the second time to make sure the CAPTCHA is still present.
    $this->submitForm($edit, 'Log in', self::LOGIN_HTML_FORM_ID);
    // Check that there was no error message for the CAPTCHA.
    $this->assertCaptchaResponseAccepted();
    $this->assertPreservedCsid($captcha_sid_initial);
  }

  /**
   * Test Cacheable CAPTCHA Persistence per form instance.
   */
  public function testCacheableCaptchaPersistencePerFormInstance() {
    // Set up of persistence and CAPTCHAs.
    $this->setUpPersistence(CaptchaConstants::CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL_PER_FORM_INSTANCE, self::TEST_CACHEABLE_CAPTCHA_TYPE);

    // Go to login form and check if there is a CAPTCHA on the login form.
    $this->drupalGet('<front>');
    $this->assertCaptchaPresence(TRUE);
    $captcha_sid_initial = $this->getCaptchaSidFromForm();

    // Try to with wrong user name and password, but correct CAPTCHA.
    $edit = [
      'name' => 'foobar',
      'pass' => 'bazlaz',
      'captcha_response' => 'Test 123',
    ];
    $this->submitForm($edit, 'Log in', self::LOGIN_HTML_FORM_ID);
    // Check that there was no error message for the CAPTCHA.
    $this->assertCaptchaResponseAccepted();
    // There shouldn't be a CAPTCHA on the new form.
    $this->assertCaptchaPresence(FALSE);

    // Start a new form instance/session.
    $this->drupalGet('node');
    $this->drupalGet('user');
    $this->assertCaptchaPresence(TRUE);

    // Check another form.
    $this->drupalGet('user/register');
    $this->assertCaptchaPresence(TRUE);
    $this->assertDifferentCsid($captcha_sid_initial);
  }

  /**
   * Test Cacheable CAPTCHA Persistence per form type.
   */
  public function testCacheableCaptchaPersistencePerFormType() {
    // Set up of persistence and CAPTCHAs.
    $this->setUpPersistence(CaptchaConstants::CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL_PER_FORM_TYPE, self::TEST_CACHEABLE_CAPTCHA_TYPE);

    // Go to login form and check if there is a CAPTCHA on the login form.
    $this->drupalGet('<front>');
    $this->assertCaptchaPresence(TRUE);
    $captcha_sid_initial = $this->getCaptchaSidFromForm();

    // Try to with wrong user name and password, but correct CAPTCHA.
    $edit = [
      'name' => 'foobar',
      'pass' => 'bazlaz',
      'captcha_response' => 'Test 123',
    ];
    $this->submitForm($edit, 'Log in', self::LOGIN_HTML_FORM_ID);
    // Check that there was no error message for the CAPTCHA.
    $this->assertCaptchaResponseAccepted();
    // There shouldn't be a CAPTCHA on the new form.
    $this->assertCaptchaPresence(FALSE);
    $this->assertPreservedCsid($captcha_sid_initial);

    // Start a new form instance/session.
    $this->drupalGet('node');
    $this->drupalGet('user');
    $this->assertCaptchaPresence(FALSE);

    // Check another form.
    /** @var \Drupal\captcha\Entity\CaptchaPoint $captcha_point */
    $captcha_point = \Drupal::entityTypeManager()
      ->getStorage('captcha_point')
      ->load('user_register_form');
    $captcha_point->enable()->save();
    $this->drupalGet('user/register');
    $this->assertCaptchaPresence(TRUE);
    $this->assertDifferentCsid($captcha_sid_initial);
  }

  /**
   * Test Persistence "Only once" for cacheable CAPTCHA.
   */
  public function testCacheableCaptchaPersistenceOnlyOnce() {
    // Set up of persistence and CAPTCHAs.
    $this->setUpPersistence(CaptchaConstants::CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL, self::TEST_CACHEABLE_CAPTCHA_TYPE);

    // Go to login form and check if there is a CAPTCHA on the login form.
    $this->drupalGet('<front>');
    $this->assertCaptchaPresence(TRUE);
    $captcha_sid_initial = $this->getCaptchaSidFromForm();

    // Try to with wrong user name and password, but correct CAPTCHA.
    $edit = [
      'name' => 'foobar',
      'pass' => 'bazlaz',
      'captcha_response' => 'Test 123',
    ];
    $this->submitForm($edit, 'Log in', self::LOGIN_HTML_FORM_ID);
    // Check that there was no error message for the CAPTCHA.
    $this->assertCaptchaResponseAccepted();
    // There shouldn't be a CAPTCHA on the new form.
    $this->assertCaptchaPresence(FALSE);
    $this->assertPreservedCsid($captcha_sid_initial);

    // Start a new form instance/session.
    $this->drupalGet('node');
    $this->drupalGet('user');
    $this->assertCaptchaPresence(FALSE);

    // Check another form.
    $this->drupalGet('user/register');
    $this->assertCaptchaPresence(FALSE);
    $this->assertDifferentCsid($captcha_sid_initial);
  }

  /**
   * Tests a persistence change after a cacheable CAPTCHA was rendered.
   *
   * The session cache context is added in the element defaults, which are
   * cached, so they must be rebuilt when the persistence setting changes.
   *
   * @see \Drupal\captcha\EventSubscriber\CaptchaCachedSettingsSubscriber
   */
  public function testCacheableCaptchaPersistenceChangedAfterRender() {
    // Render the login form with the default persistence, which builds the
    // element info and fills the Dynamic Page Cache for anonymous users.
    captcha_set_form_id_setting('user_login_form', self::TEST_CACHEABLE_CAPTCHA_TYPE);
    $this->drupalGet('user');
    $this->assertCaptchaPresence(TRUE);

    $this->setUpPersistence(CaptchaConstants::CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL_PER_FORM_TYPE, self::TEST_CACHEABLE_CAPTCHA_TYPE);

    $this->drupalGet('user');
    $this->assertCaptchaPresence(TRUE);
    $edit = [
      'name' => 'foobar',
      'pass' => 'bazlaz',
      'captcha_response' => 'Test 123',
    ];
    $this->submitForm($edit, 'Log in', self::LOGIN_HTML_FORM_ID);
    $this->assertCaptchaResponseAccepted();

    // A new instance of the solved form type has no challenge.
    $this->drupalGet('user');
    $this->assertCaptchaPresence(FALSE);

    // A different visitor still gets the challenge.
    $this->getSession()->reset();
    $this->drupalGet('user');
    $this->assertCaptchaPresence(TRUE);
  }

  /**
   * Test that "show always" validates every submission of a form instance.
   */
  public function testPersistenceAlwaysValidatesEverySubmission() {
    $this->setUpPersistence(CaptchaConstants::CAPTCHA_PERSISTENCE_SHOW_ALWAYS, self::TEST_CAPTCHA_TYPE);

    $this->drupalGet('user');
    $captcha_sid = $this->getCaptchaSidFromForm();
    $captcha_token = $this->getCaptchaTokenFromForm();

    // Solve the CAPTCHA, but fail the log in so the form is shown again.
    $edit = [
      'name' => 'foobar',
      'pass' => 'bazlaz',
      'captcha_response' => 'Test 123',
    ];
    $this->submitForm($edit, 'Log in', self::LOGIN_HTML_FORM_ID);
    $this->assertCaptchaResponseAccepted();
    $this->assertCaptchaPresence(TRUE);
    $this->assertPreservedCsid($captcha_sid);
    // The solved token must not be handed out again.
    $this->assertNotEquals($captcha_token, $this->getCaptchaTokenFromForm());

    // A wrong answer on the same form instance is still rejected.
    $edit['captcha_response'] = 'wrong';
    $this->submitForm($edit, 'Log in', self::LOGIN_HTML_FORM_ID);
    $this->assertSession()->pageTextContains(self::CAPTCHA_WRONG_RESPONSE_ERROR_MESSAGE);
    $this->assertCaptchaPresence(TRUE);

    // Replaying the solved session ID and token on a fresh form is rejected.
    $this->drupalGet('user');
    $this->assertSession()->hiddenFieldExists('captcha_sid')->setValue((string) $captcha_sid);
    $this->assertSession()->hiddenFieldExists('captcha_token')->setValue($captcha_token);
    $edit['captcha_response'] = 'Test 123';
    $this->submitForm($edit, 'Log in', self::LOGIN_HTML_FORM_ID);
    $this->assertSession()->pageTextContains(self::CAPTCHA_WRONG_RESPONSE_ERROR_MESSAGE);
    $this->assertCaptchaPresence(TRUE);
    $this->assertDifferentCsid($captcha_sid);
  }

  /**
   * Test a multi-step workflow with "per form instance" persistence.
   *
   * Once the CAPTCHA is solved on a comment preview, the same form instance
   * must be submittable without solving another CAPTCHA.
   */
  public function testPersistencePerFormInstanceMultiStep() {
    $this->config('captcha.settings')
      ->set('persistence', CaptchaConstants::CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL_PER_FORM_INSTANCE)
      ->save();
    captcha_set_form_id_setting(self::COMMENT_FORM_ID, 'captcha/Test');
    $node = $this->drupalCreateNode();
    $this->drupalLogin($this->normalUser);

    $this->drupalGet('comment/reply/node/' . $node->id() . '/comment');
    $this->assertCaptchaPresence(TRUE);
    $captcha_sid = $this->getCaptchaSidFromForm();

    // Preview with the correct answer: the CAPTCHA is omitted afterwards.
    $edit = $this->getCommentFormValues();
    $edit['captcha_response'] = 'Test 123';
    $this->submitForm($edit, 'Preview');
    $this->assertCaptchaResponseAccepted();
    $this->assertCaptchaPresence(FALSE);
    $this->assertPreservedCsid($captcha_sid);

    // Save the same form instance without a CAPTCHA response.
    $this->submitForm([], 'Save');
    $this->assertCaptchaResponseAccepted();
    $this->assertSession()->pageTextContains('Your comment has been posted.');
    $this->assertSession()->pageTextContains($edit['subject[0][value]']);

    // A new instance of the form gets a new CAPTCHA.
    $this->drupalGet('comment/reply/node/' . $node->id() . '/comment');
    $this->assertCaptchaPresence(TRUE);
    $this->assertDifferentCsid($captcha_sid);
  }

  /**
   * Test that a wrong response never omits later CAPTCHAs.
   */
  public function testWrongResponseIsNotPersisted() {
    $persistence_levels = [
      CaptchaConstants::CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL_PER_FORM_INSTANCE,
      CaptchaConstants::CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL_PER_FORM_TYPE,
      CaptchaConstants::CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL,
    ];
    captcha_set_form_id_setting('user_login_form', 'captcha/Test');
    captcha_set_form_id_setting('user_register_form', 'captcha/Test');
    foreach ($persistence_levels as $persistence) {
      $this->config('captcha.settings')->set('persistence', $persistence)->save();
      $this->getSession()->reset();

      $this->drupalGet('user');
      $captcha_sid = $this->getCaptchaSidFromForm();
      $edit = [
        'name' => 'foobar',
        'pass' => 'bazlaz',
        'captcha_response' => 'wrong',
      ];
      $this->submitForm($edit, 'Log in', self::LOGIN_HTML_FORM_ID);
      $this->assertSession()->pageTextContains(self::CAPTCHA_WRONG_RESPONSE_ERROR_MESSAGE);
      // The same form instance still has a CAPTCHA.
      $this->assertCaptchaPresence(TRUE);
      $this->assertPreservedCsid($captcha_sid);

      // So do a new instance of the form and another form.
      $this->drupalGet('user');
      $this->assertCaptchaPresence(TRUE);
      $this->drupalGet('user/register');
      $this->assertCaptchaPresence(TRUE);
    }
  }

  /**
   * Test that "per form type" and "only once" are scoped to the user session.
   */
  public function testPersistenceIsScopedToUserSession() {
    $persistence_levels = [
      CaptchaConstants::CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL_PER_FORM_TYPE,
      CaptchaConstants::CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL,
    ];
    captcha_set_form_id_setting('user_login_form', 'captcha/Test');
    captcha_set_form_id_setting('user_register_form', 'captcha/Test');
    foreach ($persistence_levels as $persistence) {
      $this->config('captcha.settings')->set('persistence', $persistence)->save();
      $this->getSession()->reset();

      $this->drupalGet('user');
      $edit = [
        'name' => 'foobar',
        'pass' => 'bazlaz',
        'captcha_response' => 'Test 123',
      ];
      $this->submitForm($edit, 'Log in', self::LOGIN_HTML_FORM_ID);
      $this->assertCaptchaResponseAccepted();
      $this->drupalGet('user');
      $this->assertCaptchaPresence(FALSE);

      // A visitor with a new session must solve a CAPTCHA again.
      $this->getSession()->reset();
      $this->drupalGet('user');
      $this->assertCaptchaPresence(TRUE);
      $this->drupalGet('user/register');
      $this->assertCaptchaPresence(TRUE);
    }
  }

}
