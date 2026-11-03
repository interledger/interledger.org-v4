<?php

namespace Drupal\Tests\captcha\Functional;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Drupal\Core\Database\Database;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests CAPTCHA cron.
 *
 * @group captcha
 */
#[Group('captcha')]
#[RunTestsInSeparateProcesses]
class CaptchaCronTest extends BrowserTestBase {

  /**
   * Modules to install for this Test class.
   *
   * @var array
   */
  protected static $modules = ['captcha'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Temporary captcha sessions storage.
   *
   * @var array
   */
  public $captchaSessions;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Cron run from Drush sees PHP's default session lifetime rather than the
    // one Drupal is configured with. Cron must not use it.
    ini_set('session.gc_maxlifetime', 1440);
    $gc_maxlifetime = $this->container->getParameter('session.storage.options')['gc_maxlifetime'];

    // Get request time.
    $request_time = \Drupal::time()->getRequestTime();

    // Add removed session.
    $time = $request_time - 1 - $gc_maxlifetime;
    $this->captchaSessions['remove_sid'] = $this->addCaptchaSession('captcha_cron_test_remove', $time);
    // Add remain session, older than PHP's default session lifetime.
    $this->captchaSessions['remain_sid'] = $this->addCaptchaSession('captcha_cron_test_remain', $request_time - 1441);
  }

  /**
   * Add test CAPTCHA session data.
   *
   * @param string $form_id
   *   Form id.
   * @param int $request_time
   *   Timestamp.
   *
   * @return int
   *   CAPTCHA session id.
   */
  public function addCaptchaSession($form_id, $request_time) {
    // Initialize solution with random data.
    $solution = hash('sha256', mt_rand());

    // Insert an entry and thankfully receive the value
    // of the autoincrement field 'csid'.
    $connection = Database::getConnection();
    $captcha_sid = $connection->insert('captcha_sessions')->fields([
      'uid' => 0,
      'sid' => session_id(),
      'ip_address' => \Drupal::request()->getClientIp(),
      'timestamp' => $request_time,
      'form_id' => $form_id,
      'solution' => $solution,
      'status' => 1,
      'attempts' => 0,
    ])->execute();

    return $captcha_sid;
  }

  /**
   * Test CAPTCHA cron.
   */
  public function testCron() {
    \Drupal::service('cron')->run();

    $connection = Database::getConnection();
    $sids = $connection->select('captcha_sessions')
      ->fields('captcha_sessions', ['csid'])
      ->condition('csid', array_values($this->captchaSessions), 'IN')
      ->execute()
      ->fetchCol();

    // Test if CAPTCHA cron removes sessions older than the session lifetime.
    $this->assertNotContains($this->captchaSessions['remove_sid'], $sids, 'CAPTCHA cron removes captcha session data older than the session lifetime.');

    // Test if CAPTCHA cron keeps sessions younger than the session lifetime.
    $this->assertContains($this->captchaSessions['remain_sid'], $sids, 'CAPTCHA cron does not remove captcha session data younger than the session lifetime.');
  }

}
