<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\TargetValidationException;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Feeds\Target\Uuid;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\Uuid
 * @group feeds
 */
class UuidTest extends FieldTargetTestBase {

  /**
   * The ID of the plugin.
   *
   * @var string
   */
  protected static $pluginId = 'uuid';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return Uuid::class;
  }

  /**
   * @covers ::prepareValue
   */
  public function testPrepareValueIgnoreSpaces() {
    $prepareTarget = $this->getMethod(Uuid::class, 'prepareTarget')->getClosure();
    $configuration = [
      'feed_type' => $this->createMock(FeedTypeInterface::class),
      'target_definition' => $prepareTarget($this->getMockFieldDefinition()),
    ];
    $target = new Uuid($configuration, 'uuid', []);

    $prepareValue = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['value' => ' bfe2cffc-f86a-493f-8ccc-5017fac1f382'];
    $prepareValue(0, $values);
    $this->assertSame('bfe2cffc-f86a-493f-8ccc-5017fac1f382', $values['value']);

    $values = ['value' => ' eb8dc174-ceb7-47e9-8ec6-daa03b165c83 '];
    $prepareValue(0, $values);
    $this->assertSame('eb8dc174-ceb7-47e9-8ec6-daa03b165c83', $values['value']);
  }

  /**
   * @covers ::prepareValue
   * @dataProvider prepareValueTestData
   */
  public function testPrepareValue($delta, &$values, $expectedException, $expectedMessage, $expectedValue) {
    $prepareTarget = $this->getMethod(Uuid::class, 'prepareTarget')->getClosure();
    $configuration = [
      'feed_type' => $this->createMock(FeedTypeInterface::class),
      'target_definition' => $prepareTarget($this->getMockFieldDefinition()),
    ];
    $target = new Uuid($configuration, 'uuid', []);

    $prepareValue = $this->getProtectedClosure($target, 'prepareValue');

    $this->expectException($expectedException);
    $this->expectExceptionMessage($expectedMessage);

    $prepareValue($delta, $values);
    $this->assertSame($expectedValue, $values['value']);
  }

  /**
   * Data provider for the test.
   */
  public static function prepareValueTestData() {
    return [
      'uuid_not_set' => [
        0,
        ['value' => NULL],
        EmptyFeedException::class,
        'UUID value cannot be empty',
        NULL,
      ],
      'uuid_is_empty' => [
        1,
        ['value' => ' '],
        EmptyFeedException::class,
        'UUID value cannot be empty',
        NULL,
      ],
      'uuid_is_invalid' => [
        2,
        ['value' => '1234567890'],
        TargetValidationException::class,
        'Supplied value "<em class="placeholder">1234567890</em>" is not a valid UUID.',
        NULL,
      ],
      'uuid_is_valid_delta_is_not' => [
        3,
        ['value' => '637f4cf0-38aa-4285-b3fd-a632f9bfdd76'],
        TargetValidationException::class,
        'UUID field cannot hold more than 1 value',
        NULL,
      ],
    ];
  }

}
