<?php

declare(strict_types=1);

namespace Drupal\Tests\captcha\Kernel;

use Drupal\Component\Utility\Crypt;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests how CAPTCHA sessions record the visitor's session ID.
 *
 * @group captcha
 */
#[Group('captcha')]
#[RunTestsInSeparateProcesses]
final class CaptchaSessionIdTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['captcha'];

  /**
   * The session ID in effect before the test changed it.
   */
  private string $originalSessionId;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('captcha', ['captcha_sessions']);
    $this->installConfig('captcha');
    \Drupal::moduleHandler()->loadInclude('captcha', 'inc');
    $this->originalSessionId = (string) session_id();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    session_id($this->originalSessionId);
    parent::tearDown();
  }

  /**
   * Tests that long session IDs are stored as a fixed-length hash.
   *
   * PHP's session.sid_length allows session IDs of up to 256 characters,
   * which overflowed the 64-character sid column.
   */
  public function testLongSessionIdIsHashed(): void {
    $session_id = str_repeat('a1B2c3D4', 32);
    $this->assertSame(256, strlen($session_id));
    session_id($session_id);

    $csid = _captcha_generate_captcha_session('test_form');

    $stored = \Drupal::database()->select('captcha_sessions', 'cs')
      ->fields('cs', ['sid'])
      ->condition('csid', $csid)
      ->execute()
      ->fetchField();
    $this->assertSame(Crypt::hashBase64($session_id), $stored);
    $this->assertStringNotContainsString($session_id, $stored);
  }

  /**
   * Tests that a request without a session stores an empty session ID.
   */
  public function testNoSessionStoresEmptyString(): void {
    session_id('');

    $csid = _captcha_generate_captcha_session('test_form');

    $stored = \Drupal::database()->select('captcha_sessions', 'cs')
      ->fields('cs', ['sid'])
      ->condition('csid', $csid)
      ->execute()
      ->fetchField();
    $this->assertSame('', $stored);
  }

  /**
   * Tests that the post update removes plain-text session IDs.
   */
  public function testPostUpdateClearsPlainSessionIds(): void {
    $csid = \Drupal::database()->insert('captcha_sessions')
      ->fields([
        'sid' => 'plain-text-session-id',
        'form_id' => 'test_form',
      ])
      ->execute();

    \Drupal::moduleHandler()->loadInclude('captcha', 'php', 'captcha.post_update');
    captcha_post_update_clear_plain_session_ids();

    $stored = \Drupal::database()->select('captcha_sessions', 'cs')
      ->fields('cs', ['sid'])
      ->condition('csid', $csid)
      ->execute()
      ->fetchField();
    $this->assertSame('', $stored);
  }

}
