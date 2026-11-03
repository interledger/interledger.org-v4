<?php

declare(strict_types=1);

namespace Drupal\Tests\captcha\Kernel\Migrate\d7;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

// Drupal 12 removed the Drupal 7 migration classes from core. The test classes
// extending this base must still load there, so the base falls back to a
// kernel test that skips itself.
if (class_exists(MigrateDrupal7TestBase::class)) {

  /**
   * Base class for the CAPTCHA Drupal 7 migration tests.
   */
  abstract class CaptchaMigrateDrupal7TestBase extends MigrateDrupal7TestBase {
  }

}
else {

  /**
   * Skips the CAPTCHA Drupal 7 migration tests where core lacks migrate_drupal.
   */
  abstract class CaptchaMigrateDrupal7TestBase extends KernelTestBase {

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void {
      parent::setUp();
      $this->markTestSkipped('Drupal 7 migrations need the migrate_drupal classes, which Drupal 12 removed from core.');
    }

  }

}
