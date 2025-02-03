<?php

namespace Drupal\Tests\feeds\Kernel\Feeds\Target;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;
use Drupal\node\Entity\Node;

/**
 * Tests for mapping to text and numeric fields.
 *
 * @group feeds
 */
class FieldTest extends FeedsKernelTestBase {

  /**
   * The feed type to test with.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Add fields.
    $this->setUpBodyField();
    $this->createFieldWithStorage('field_alpha');
    $this->createFieldWithStorage('field_beta', [
      'type' => 'integer',
    ]);
    $this->createFieldWithStorage('field_gamma', [
      'type' => 'decimal',
    ]);
    $this->createFieldWithStorage('field_delta', [
      'type' => 'float',
    ]);

    // Create and configure feed type.
    $this->feedType = $this->createFeedTypeForCsv([
      'title' => 'title',
      'body' => 'body',
      'alpha' => 'alpha',
      'beta' => 'beta',
      'gamma' => 'gamma',
      'delta' => 'delta',
    ], [
      'mappings' => [
        [
          'target' => 'title',
          'map' => ['value' => 'title'],
        ],
        [
          'target' => 'body',
          'map' => ['value' => 'body'],
          'settings' => [
            'format' => 'plain_text',
          ],
        ],
        [
          'target' => 'field_alpha',
          'map' => ['value' => 'alpha'],
        ],
        [
          'target' => 'field_beta',
          'map' => ['value' => 'beta'],
        ],
        [
          'target' => 'field_gamma',
          'map' => ['value' => 'gamma'],
        ],
        [
          'target' => 'field_delta',
          'map' => ['value' => 'delta'],
        ],
      ],
    ]);
  }

  /**
   * Configures display of fields.
   */
  protected function setUpFieldDisplay() {
    $this->installConfig(['system']);

    $formats = $this->container->get('entity_type.manager')
      ->getStorage('date_format')
      ->loadMultiple(['long', 'medium', 'short']);
    $formats['long']->setPattern('l, j. F Y - G:i')->save();
    $formats['medium']->setPattern('j. F Y - G:i')->save();
    $formats['short']->setPattern('Y M j - g:ia')->save();

    $this->container->get('entity_display.repository')->getViewDisplay('node', 'article', 'default')
      ->setComponent('field_alpha', [
        'type' => 'text_default',
        'label' => 'above',
      ])
      ->setComponent('field_beta', [
        'type' => 'number_integer',
        'label' => 'above',
      ])
      ->setComponent('field_gamma', [
        'type' => 'number_decimal',
        'label' => 'above',
      ])
      ->setComponent('field_delta', [
        'type' => 'number_decimal',
        'label' => 'above',
      ])
      ->save();
  }

  /**
   * Basic test loading a double entry CSV file.
   */
  public function test() {
    // Import CSV file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();
    $this->assertNodeCount(2);

    // Check the two imported nodes.
    $expected_values_per_node = [
      1 => [
        'body' => 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat.',
        'field_alpha' => 'Lorem',
        'field_beta' => '42',
        'field_gamma' => '4.20',
        'field_delta' => '3.14159',
      ],
      2 => [
        'body' => 'Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat.',
        'field_alpha' => 'Ut wisi',
        'field_beta' => '32',
        'field_gamma' => '1.20',
        'field_delta' => '5.62951',
      ],
    ];
    $this->checkValues($expected_values_per_node);
  }

  /**
   * Tests if values are cleared out when an empty value is provided.
   */
  public function testClearOutValues() {
    $this->setUpFieldDisplay();

    // Add mapping to GUID and set that column as unique.
    $this->feedType->addCustomSource('guid', [
      'label' => 'GUID',
      'value' => 'guid',
    ]);
    $this->feedType->addMapping([
      'target' => 'feeds_item',
      'map' => ['guid' => 'guid'],
      'unique' => ['guid' => TRUE],
    ]);
    $this->feedType->save();

    // Import CSV file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();
    $this->assertNodeCount(2);

    // Check the two imported nodes.
    $expected_values_per_node = [
      1 => [
        'body' => 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat.',
        'field_alpha' => 'Lorem',
        'field_beta' => '42',
        'field_gamma' => '4.20',
        'field_delta' => '3.14159',
      ],
      2 => [
        'body' => 'Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat.',
        'field_alpha' => 'Ut wisi',
        'field_beta' => '32',
        'field_gamma' => '1.20',
        'field_delta' => '5.62951',
      ],
    ];
    $this->checkValues($expected_values_per_node);

    // Configure feed type to update existing values.
    $this->feedType->getProcessor()->setConfiguration([
      'update_existing' => ProcessorInterface::UPDATE_EXISTING,
    ] + $this->feedType->getProcessor()->getConfiguration());
    $this->feedType->save();

    // Import CSV file with empty values.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/content_empty.csv',
    ]);
    $feed->import();
    $this->assertNodeCount(2);

    // Check if all values were cleared out for node 1 and
    // that for node 2 all values were set to '0'.
    $expected_values_per_node_empty = [
      1 => [
        'body' => '',
        'field_alpha' => '',
        'field_beta' => '',
        'field_gamma' => '',
        'field_delta' => '',
      ],
      2 => [
        'body' => 0,
        'field_alpha' => 0,
        'field_beta' => 0,
        'field_gamma' => 0,
        'field_delta' => 0,
      ],
    ];
    $this->checkValues($expected_values_per_node_empty);

    $field_labels = [
      'field_alpha label',
      'field_beta label',
      'field_gamma label',
      'field_delta label',
    ];

    // Check for node 1 if labels are no longer shown.
    $rendered_content = $this->renderNode(Node::load(1));
    foreach ($field_labels as $label) {
      $this->assertStringNotContainsString($label, $rendered_content);
    }

    // Check for node 2 if labels are still shown.
    $rendered_content = $this->renderNode(Node::load(2));
    foreach ($field_labels as $label) {
      $this->assertStringContainsString($label, $rendered_content);
    }

    // Re-import the first file again.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();

    // Check if the two imported nodes have content again.
    $this->checkValues($expected_values_per_node);

    // Import CSV file with non-existent values.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/content_non_existent.csv',
    ]);
    $feed->import();

    // Check if all values were cleared out for node 1.
    $expected_values_per_node_non_existent = [
      1 => [
        'body' => '',
        'field_alpha' => '',
        'field_beta' => '',
        'field_gamma' => '',
        'field_delta' => '',
      ],
    ];
    $this->checkValues($expected_values_per_node_non_existent);
    // Check if labels for fields that should be cleared out are not shown.
    $rendered_content = $this->renderNode(Node::load(1));
    foreach ($field_labels as $label) {
      $this->assertStringNotContainsString($label, $rendered_content);
    }
  }

  /**
   * Tests if text and numeric fields can be used as unique target.
   *
   * @param string $field
   *   The name of the field to set as unique.
   * @param string $subfield
   *   The subfield of the field.
   * @param int $delta
   *   The index of the target in the mapping configuration.
   * @param array $values
   *   (optional) The list of initial values the node to create should get.
   *
   * @dataProvider dataProviderTargetUnique
   */
  public function testTargetUnique($field, $subfield, $delta, array $values = []) {
    $expected_values = [
      'body' => 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat.',
      'field_alpha' => 'Lorem',
      'field_beta' => '42',
      'field_gamma' => '4.20',
      'field_delta' => '3.14159',
    ];

    // Set mapper as unique.
    $mappings = $this->feedType->getMappings();
    $mappings[$delta]['unique'] = [$subfield => TRUE];
    $this->feedType->setMappings($mappings);

    // Configure feed type to update existing values.
    $this->feedType->getProcessor()->setConfiguration([
      'update_existing' => ProcessorInterface::UPDATE_EXISTING,
    ] + $this->feedType->getProcessor()->getConfiguration());

    // And save feed type.
    $this->feedType->save();

    // Create an entity to update.
    $values += [
      'title'  => $this->randomMachineName(8),
      'type'  => 'article',
      'uid'  => 0,
      $field => $expected_values[$field] ?? NULL,
    ];
    $node = Node::create($values);
    $node->save();

    // Run import.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();
    $this->assertNodeCount(2);

    // Check if the first node has the expected values.
    $node = $this->reloadEntity($node);
    foreach ($expected_values as $field_name => $value) {
      $this->assertEquals($value, $node->{$field_name}->value);
    }
  }

  /**
   * Data provider for ::testTargetUnique().
   *
   * Check if text fields, integer fields and decimal fields can be used as
   * unique target.
   */
  public static function dataProviderTargetUnique() {
    return [
      ['field_alpha', 'value', 2],
      ['field_beta', 'value', 3],
      ['field_gamma', 'value', 4],
    ];
  }

  /**
   * Tests if list integer fields can be used as unique target.
   */
  public function testListIntegerTargetUnique() {
    // Add a list integer field.
    $this->createFieldWithStorage('field_jota', [
      'type' => 'list_integer',
      'storage' => [
        'settings' => [
          'allowed_values' => [
            1 => 'One',
            2 => 'Two',
          ],
        ],
      ],
    ]);

    // Reload feed type to reset target plugin cache.
    $this->feedType = $this->reloadEntity($this->feedType);

    // Add custom source and add mapping for this field.
    $this->feedType->addCustomSource('guid', [
      'label' => 'GUID',
      'value' => 'guid',
    ]);
    $this->feedType->addMapping([
      'target' => 'field_jota',
      'map' => ['value' => 'guid'],
      'unique' => ['value' => TRUE],
    ]);

    // And test!
    $this->testTargetUnique('field_jota', 'value', 6, ['field_jota' => 1]);
  }

  /**
   * Tests handling of array values for a unique target.
   */
  public function testUniqueWithArrayValue() {
    // Respond to after parse event.
    $this->container->get('event_dispatcher')
      ->addListener(FeedsEvents::PARSE, [$this, 'afterParseUniqueWithArrayValue'], FeedsEvents::AFTER);

    $this->testTargetUnique('field_alpha', 'value', 2);
  }

  /**
   * After parse callback for ::testUniqueWithArrayValue().
   *
   * @param \Drupal\feeds\Event\ParseEvent $event
   *   The parse event.
   */
  public function afterParseUniqueWithArrayValue(ParseEvent $event) {
    /** @var \Drupal\feeds\Feeds\Item\ItemInterface $item */
    foreach ($event->getParserResult() as $item) {
      // Make value for field_alpha multivalued (as if Tamper 'Explode' plugin
      // was applied to it).
      $alpha = $item->get('alpha');
      $alpha = [$alpha];
      $item->set('alpha', $alpha);
    }
  }

  /**
   * Tests handling of array values for a unique target.
   */
  public function testUniqueWithObjectValue() {
    // Respond to after parse event.
    $this->container->get('event_dispatcher')
      ->addListener(FeedsEvents::PARSE, [$this, 'afterParseUniqueWithObjectValue'], FeedsEvents::AFTER);

    $this->testTargetUnique('field_alpha', 'value', 2);
  }

  /**
   * After parse callback for ::testUniqueWithObjectValue().
   *
   * @param \Drupal\feeds\Event\ParseEvent $event
   *   The parse event.
   */
  public function afterParseUniqueWithObjectValue(ParseEvent $event) {
    /** @var \Drupal\feeds\Feeds\Item\ItemInterface $item */
    foreach ($event->getParserResult() as $item) {
      // Make value for field_alpha an object.
      $alpha = $item->get('alpha');
      $alpha = new TestObject($alpha);
      $item->set('alpha', $alpha);
    }
  }

  /**
   * Tests importing data where a multivalue field is marked as unique.
   */
  public function testUniqueMultivalueField() {
    // Respond to after parse event.
    $this->container->get('event_dispatcher')
      ->addListener(FeedsEvents::PARSE, [$this, 'afterParseUniqueMultivalueField'], FeedsEvents::AFTER);

    // Set cardinality of field_alpha to unlimited.
    $this->container->get('entity_type.manager')
      ->getStorage('field_storage_config')
      ->load('node.field_alpha')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->save();

    // Set mapper 'field_alpha' as unique.
    $mappings = $this->feedType->getMappings();
    $mappings[2]['unique'] = ['value' => TRUE];
    $this->feedType->setMappings($mappings);

    // Configure feed type to update existing values.
    $this->feedType->getProcessor()->setConfiguration([
      'update_existing' => ProcessorInterface::UPDATE_EXISTING,
    ] + $this->feedType->getProcessor()->getConfiguration());
    $this->feedType->save();

    // The data that we are going to import for field_alpha looks like this:
    // @code
    // 1: 'Lorem'
    // 2: ['Ut wisi', 'Enim ad minim']
    // @endcode
    // Since Feeds only picks the first value for the lookup, we expect that
    // node 1 and node 3 will get updated.
    // Create a few nodes that can be updated.
    $node1 = Node::create([
      'title'  => 'Article1',
      'type'  => 'article',
      'uid'  => 0,
      'field_alpha' => ['Lorem', 'Ipsum'],
    ]);
    $node1->save();

    $node2 = Node::create([
      'title'  => 'Article2',
      'type'  => 'article',
      'uid'  => 0,
      'field_alpha' => 'Enim ad minim',
    ]);
    $node2->save();

    $node3 = Node::create([
      'title'  => 'Article3',
      'type'  => 'article',
      'uid'  => 0,
      'field_alpha' => ['Dolor Sit', 'Ut Wisi'],
    ]);
    $node3->save();

    // Run import.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();
    $this->assertNodeCount(3);

    // Node 1 should be updated.
    $node1 = $this->reloadEntity($node1);
    $expected_alpha = [
      0 => [
        'value' => 'Lorem',
        'format' => 'plain_text',
      ],
    ];
    $this->assertEquals('Lorem ipsum', $node1->title->value);
    $this->assertEquals($expected_alpha, $node1->toArray()['field_alpha']);

    // Node 2 should stay the same.
    $node2 = $this->reloadEntity($node2);
    $expected_alpha = [
      0 => [
        'value' => 'Enim ad minim',
        'format' => NULL,
      ],
    ];
    $this->assertEquals('Article2', $node2->title->value);
    $this->assertEquals($expected_alpha, $node2->toArray()['field_alpha']);

    // Node 3 should be updated.
    $node3 = $this->reloadEntity($node3);
    $expected_alpha = [
      0 => [
        'value' => 'Ut wisi',
        'format' => 'plain_text',
      ],
      1 => [
        'value' => 'Enim ad minim',
        'format' => 'plain_text',
      ],
    ];
    $this->assertEquals('Ut wisi enim ad minim veniam', $node3->title->value);
    $this->assertEquals($expected_alpha, $node3->toArray()['field_alpha']);
  }

  /**
   * After parse callback for ::testUniqueMultivalueField().
   *
   * @param \Drupal\feeds\Event\ParseEvent $event
   *   The parse event.
   */
  public function afterParseUniqueMultivalueField(ParseEvent $event) {
    /** @var \Drupal\feeds\Feeds\Item\ItemInterface $item */
    foreach ($event->getParserResult() as $item) {
      // Set values for field_alpha.
      $alpha = $item->get('alpha');
      switch ($alpha) {
        case 'Lorem':
          $alpha = ['Lorem'];
          break;

        case 'Ut wisi':
          $alpha = ['Ut wisi', 'Enim ad minim'];
          break;
      }
      $item->set('alpha', $alpha);
    }
  }

  /**
   * Checks the field values.
   *
   * @param array $expected_values_per_node
   *   The expected field values, per node ID.
   */
  protected function checkValues(array $expected_values_per_node) {
    foreach ($expected_values_per_node as $node_id => $expected_values) {
      $node = Node::load($node_id);
      foreach ($expected_values as $field_name => $value) {
        $this->assertEquals($value, $node->{$field_name}->value);
      }
    }
  }

  /**
   * Renders the given node and returns the result.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node to render.
   *
   * @return string
   *   The rendered content.
   */
  protected function renderNode(Node $node) {
    $display = \Drupal::service('entity_display.repository')->getViewDisplay($node->getEntityTypeId(), $node->bundle(), 'default');
    $content = $display->build($node);
    return (string) $this->container->get('renderer')->renderRoot($content);
  }

}

/**
 * Class for testing with using objects as values to import.
 */
class TestObject {

  /**
   * The object's value.
   *
   * @var string
   */
  private $value;

  /**
   * Constructs a new TestObject.
   */
  public function __construct(string $value) {
    $this->value = $value;
  }

  /**
   * Casts object to string.
   */
  public function __toString() {
    return $this->value;
  }

}
