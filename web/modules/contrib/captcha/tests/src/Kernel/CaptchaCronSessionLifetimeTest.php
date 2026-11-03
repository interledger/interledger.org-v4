<?php

declare(strict_types=1);

namespace Drupal\Tests\captcha\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests which session lifetime CAPTCHA cron uses to expire challenges.
 *
 * @group captcha
 */
#[Group('captcha')]
#[RunTestsInSeparateProcesses]
final class CaptchaCronSessionLifetimeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['captcha'];

  /**
   * Overrides the session.storage.options container parameter, if set.
   *
   * @var array|null
   */
  protected ?array $sessionStorageOptions = NULL;

  /**
   * PHP's session.gc_maxlifetime before the test changed it.
   *
   * @var string|false
   */
  protected string|false $originalGcMaxlifetime;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('captcha', ['captcha_sessions']);

    // PHP's default, which is what Drush cron sees.
    $this->originalGcMaxlifetime = ini_get('session.gc_maxlifetime');
    ini_set('session.gc_maxlifetime', '1440');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if ($this->originalGcMaxlifetime !== FALSE) {
      ini_set('session.gc_maxlifetime', $this->originalGcMaxlifetime);
    }
    parent::tearDown();
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    if ($this->sessionStorageOptions !== NULL) {
      $container->setParameter('session.storage.options', $this->sessionStorageOptions);
    }
  }

  /**
   * Tests that cron uses the configured gc_maxlifetime, not PHP's.
   */
  public function testConfiguredLifetime(): void {
    $this->setSessionStorageOptions(['gc_maxlifetime' => 7200]);
    $request_time = $this->container->get('datetime.time')->getRequestTime();

    $expired = $this->addCaptchaSession($request_time - 7201);
    $live = $this->addCaptchaSession($request_time - 1441);

    captcha_cron();

    $remaining = $this->getCaptchaSessionIds();
    $this->assertNotContains($expired, $remaining);
    $this->assertContains($live, $remaining);
  }

  /**
   * Tests that cron falls back to PHP's value when none is configured.
   */
  public function testFallbackLifetime(): void {
    $this->setSessionStorageOptions([]);
    $request_time = $this->container->get('datetime.time')->getRequestTime();

    $expired = $this->addCaptchaSession($request_time - 1441);
    $live = $this->addCaptchaSession($request_time - 1439);

    captcha_cron();

    $remaining = $this->getCaptchaSessionIds();
    $this->assertNotContains($expired, $remaining);
    $this->assertContains($live, $remaining);
  }

  /**
   * Rebuilds the container with the given session storage options.
   *
   * @param array $options
   *   The session.storage.options container parameter.
   */
  protected function setSessionStorageOptions(array $options): void {
    $this->sessionStorageOptions = $options;
    $this->container->get('kernel')->rebuildContainer();
    $this->container = \Drupal::getContainer();
    $this->assertSame($options, $this->container->getParameter('session.storage.options'));
  }

  /**
   * Inserts a CAPTCHA session with the given timestamp.
   *
   * @param int $timestamp
   *   The session timestamp.
   *
   * @return int
   *   The CAPTCHA session ID.
   */
  protected function addCaptchaSession(int $timestamp): int {
    return (int) $this->container->get('database')->insert('captcha_sessions')
      ->fields([
        'uid' => 0,
        'sid' => 'test',
        'ip_address' => '127.0.0.1',
        'timestamp' => $timestamp,
        'form_id' => 'captcha_cron_test',
        'solution' => 'solution',
        'status' => 0,
        'attempts' => 0,
      ])
      ->execute();
  }

  /**
   * Returns the IDs of all stored CAPTCHA sessions.
   *
   * @return int[]
   *   The CAPTCHA session IDs.
   */
  protected function getCaptchaSessionIds(): array {
    return array_map('intval', $this->container->get('database')
      ->select('captcha_sessions', 'cs')
      ->fields('cs', ['csid'])
      ->execute()
      ->fetchCol());
  }

}
