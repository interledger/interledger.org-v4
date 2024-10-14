<?php

namespace Drupal\Tests\feeds\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase as CoreUpdatePathTestBase;

/**
 * Base class for Feeds update tests.
 */
abstract class UpdatePathTestBase extends CoreUpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['feeds', 'node'];

  /**
   * Returns the path to the Drupal core fixture.
   *
   * @param int $core_version
   *   The oldest core version to get.
   *
   * @return string
   *   A path to the drupal core fixture.
   */
  protected function getCoreFixturePath(int $core_version = 9): string {
    $fixtures_per_core_version[8] = [
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-8.8.0.bare.standard.php.gz',
    ];
    $fixtures_per_core_version[9] = [
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-9.3.0.bare.standard.php.gz',
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
    ];
    $fixtures_per_core_version[10] = [
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-10.3.0.bare.standard.php.gz',
    ];

    $selected_fixtures = [];
    foreach ($fixtures_per_core_version as $fixtures_core_version => $core_version_fixtures) {
      if ($fixtures_core_version >= $core_version) {
        $selected_fixtures = array_merge($selected_fixtures, $core_version_fixtures);
      }
    }

    foreach ($selected_fixtures as $fixture) {
      if (file_exists($fixture)) {
        return $fixture;
      }
    }

    throw new \Exception('No suitable core fixture found. Please adjust ' . __CLASS__ . ' as necessary.');
  }

}
