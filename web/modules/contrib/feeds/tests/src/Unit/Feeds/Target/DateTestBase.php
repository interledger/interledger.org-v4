<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\Core\Config\ImmutableConfig;

/**
 * Base class for date related targets tests.
 */
abstract class DateTestBase extends FieldTargetWithContainerTestBase {

  /**
   * The system date configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $systemDateConfig;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->systemDateConfig = $this->prophesize(ImmutableConfig::class);
  }

}
