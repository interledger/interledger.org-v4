<?php

declare(strict_types=1);

namespace Drupal\Tests\captcha\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\captcha_point_entity_test\Entity\Point;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that loading a "point" entity does not hit captcha_point_load().
 *
 * The function name captcha_point_load() matches hook_ENTITY_TYPE_load() for
 * an entity type with the ID "point".
 *
 * @see https://www.drupal.org/project/captcha/issues/3470143
 *
 * @group captcha
 */
#[Group('captcha')]
#[RunTestsInSeparateProcesses]
final class CaptchaPointLoadHookCollisionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['captcha', 'captcha_point_entity_test'];

  /**
   * Tests loading a "point" entity raises no PHP warnings.
   */
  public function testLoadPointEntity(): void {
    Point::create(['id' => 'test', 'label' => 'Test'])->save();
    \Drupal::entityTypeManager()->getStorage('point')->resetCache();

    $errors = [];
    set_error_handler(function (int $errno, string $errstr) use (&$errors): bool {
      $errors[] = $errstr;
      return TRUE;
    });
    try {
      $point = Point::load('test');
    }
    finally {
      restore_error_handler();
    }

    $this->assertSame([], $errors);
    $this->assertInstanceOf(Point::class, $point);
    $this->assertSame('Test', $point->label());
  }

}
