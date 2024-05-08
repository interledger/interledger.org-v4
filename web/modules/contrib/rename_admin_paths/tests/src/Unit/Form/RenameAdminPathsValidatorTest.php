<?php

namespace Drupal\Tests\rename_admin_paths\Unit\Form;

use Drupal\rename_admin_paths\Form\RenameAdminPathsValidator;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the processing of the path validator.
 *
 * @group tests
 */
class RenameAdminPathsValidatorTest extends UnitTestCase {

  /**
   * Asserts that the validator knows a path is a default path.
   *
   * @param string $value
   *   The path value.
   *
   * @dataProvider defaultPaths
   */
  public function testMatchDefaultPath(string $value) {
    $this->assertTrue(RenameAdminPathsValidator::isDefaultPath($value));
  }

  /**
   * Returns a set of Drupal default paths.
   *
   * @return \Generator
   *   The set of default paths with various capitalization.
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
   * Asserts that a path is a Drupal default path.
   *
   * @param string $value
   *   The path value.
   *
   * @dataProvider nonDefaultPaths
   */
  public function testDefaultPath(string $value) {
    $this->assertFalse(RenameAdminPathsValidator::isDefaultPath($value));
  }

  /**
   * Returns a set of non-Drupal-default paths.
   *
   * @return \Generator
   *   The set of non-default paths.
   */
  public function nonDefaultPaths() {
    yield ['user2'];
    yield ['my-admin'];
    yield ['backend'];
  }

  /**
   * Asserts that a path is valid.
   *
   * @param string $value
   *   The path value.
   *
   * @dataProvider validPaths
   */
  public function testValidPath(string $value) {
    $this->assertTrue(RenameAdminPathsValidator::isValidPath($value));
  }

  /**
   * Returns a set of valid values.
   *
   * @return \Generator
   *   The set of valid values.
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
   * Asserts that a path is invalid.
   *
   * @param string $value
   *   The path value.
   *
   * @dataProvider invalidPaths
   */
  public function testInvalidPath(string $value) {
    $this->assertFalse(RenameAdminPathsValidator::isValidPath($value));
  }

  /**
   * Returns a set of invalid values.
   *
   * @return \Generator
   *   The set of invalid values.
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
