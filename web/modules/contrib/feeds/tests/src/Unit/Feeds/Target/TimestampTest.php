<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Feeds\Target\Timestamp;
use Drupal\feeds\Plugin\Type\Target\TargetInterface;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\Timestamp
 * @group feeds
 */
class TimestampTest extends DateTestBase {

  /**
   * The ID of the plugin.
   *
   * @var string
   */
  protected static $pluginId = 'timestamp';

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return Timestamp::class;
  }

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin(array $configuration = []): TargetInterface {
    $target_class = $this->getTargetClass();
    $method = $this->getMethod($target_class, 'prepareTarget')->getClosure();

    $configuration += [
      'feed_type' => $this->createMock(FeedTypeInterface::class),
      'target_definition' => $method($this->getMockFieldDefinition()),
    ];
    return new $target_class($configuration, static::$pluginId, [], $this->systemDateConfig->reveal());
  }

  /**
   * @covers ::prepareValue
   */
  public function testPrepareValue() {
    $target = $this->instantiatePlugin();
    $method = $this->getProtectedClosure($target, 'prepareValue');

    // Test valid timestamp.
    $values = ['value' => 1411606273];
    $method(0, $values);
    $this->assertSame($values['value'], 1411606273);

    // Test year value.
    $values = ['value' => 2000];
    $method(0, $values);
    $this->assertSame($values['value'], strtotime('2000-01-01T00:00:00Z'));

    // Test invalid value.
    $values = ['value' => 'abc'];
    $method(0, $values);
    $this->assertSame($values['value'], '');
  }

}
