<?php

namespace Drupal\Tests\rename_admin_paths\Unit\Form;

use Drupal\rename_admin_paths\Form\RenameAdminPathsValidator;
use Drupal\Tests\UnitTestCase;

/**
 * @group tests
 */
class RenameAdminPathsValidatorTest extends UnitTestCase {

  /**
   * @dataProvider defaultPaths
   *
   * @param string $value
   */
  public function testMatchDefaultPath(string $value) {
    $this->assertTrue(RenameAdminPathsValidator::isDefaultPath($value));
  }

  /**
   * @return \Generator
   */
  public function defaultPaths() {
    yield ['user'];
    yield ['admin'];
    yield ['ADMIN'];
    yield ['Admin'];
    yield ['USER'];
    yield ['User'];
  }

  /**
   * @dataProvider nonDefaultPaths
   *
   * @param string $value
   */
  public function testDefaultPath(string $value) {
    $this->assertFalse(RenameAdminPathsValidator::isDefaultPath($value));
  }

  /**
   * @return \Generator
   */
  public function nonDefaultPaths() {
    yield ['user2'];
    yield ['myadmin'];
    yield ['backend'];
  }

  /**
   * @dataProvider validPaths
   *
   * @param string $value
   */
  public function testValidPath(string $value) {
    $this->assertTrue(RenameAdminPathsValidator::isValidPath($value));
  }

  /**
   * @return \Generator
   */
  public function validPaths() {
    yield ['backend'];
    yield ['back-end'];
    yield ['Backend'];
    yield ['Back-End'];
    yield ['Back_End'];
    yield ['Back-End_123'];
    yield ['admin2'];
    yield ['user2'];
    yield ['admin'];
    yield ['user'];
    yield ['Admin'];
  }

  /**
   * @dataProvider invalidPaths
   *
   * @param string $value
   */
  public function testInvalidPath(string $value) {
    $this->assertFalse(RenameAdminPathsValidator::isValidPath($value));
  }

  /**
   * @return \Generator
   */
  public function invalidPaths() {
    yield ['backend!'];
    yield ['back@end'];
    yield ['(Backend)'];
    yield ['Back~End'];
    yield ['Back=End'];
    yield ['Back-End+123'];
    yield ['admin!'];
    yield ['@user'];
  }
}
