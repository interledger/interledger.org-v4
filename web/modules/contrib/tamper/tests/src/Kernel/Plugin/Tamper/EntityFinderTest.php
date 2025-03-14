<?php

namespace Drupal\Tests\tamper\Kernel\Plugin;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\entity_test\Entity\EntityTestNoBundle;
use Drupal\entity_test\Entity\EntityTestWithBundle;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\tamper\Plugin\Tamper\EntityFinder;
use Drupal\tamper\SourceDefinitionInterface;
use Drupal\tamper\TamperManagerInterface;

/**
 * Tests the Entity Finder plugin.
 *
 * @group tamper
 */
class EntityFinderTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'link',
    'tamper',
    'text',
  ];

  /**
   * The Tamper plugin manager.
   */
  protected TamperManagerInterface $pluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test_bundle');
    $this->installEntitySchema('entity_test_with_bundle');
    $this->installEntitySchema('entity_test_no_bundle');

    $this->pluginManager = $this->container->get('plugin.manager.tamper');
  }

  /**
   * Tests if an existing entity can be found using field and bundle.
   */
  public function testFindEntityWithBundle() {
    // Create two bundles.
    EntityTestBundle::create([
      'id' => 'a',
      'label' => 'A',
    ])->save();
    EntityTestBundle::create([
      'id' => 'b',
      'label' => 'B',
    ])->save();

    // Create two entities: one of bundle A and one of bundle B.
    // Both entities have the same name.
    $foo_a = EntityTestWithBundle::create([
      'name' => 'Foo',
      'type' => 'a',
    ]);
    $foo_a->save();
    $foo_b = EntityTestWithBundle::create([
      'name' => 'Foo',
      'type' => 'b',
    ]);
    $foo_b->save();

    // Instantiate the EntityFinder Tamper plugin and configure it to find
    // entities in bundle B.
    $config = [
      EntityFinder::SETTING_ENTITY_TYPE => 'entity_test_with_bundle',
      EntityFinder::SETTING_BUNDLE => 'b',
      EntityFinder::SETTING_FIELD => 'name',
      'source_definition' => $this->createMock(SourceDefinitionInterface::class),
    ];

    /** @var \Drupal\tamper\TamperInterface $plugin */
    $plugin = $this->pluginManager->createInstance('entity_finder', $config);

    // Assert that the entity in bundle B is found.
    $this->assertEquals($foo_b->id(), $plugin->tamper('Foo'));
    // Assert that a non-existing entity results into NULL.
    $this->assertNull($plugin->tamper('Qux'));
  }

  /**
   * Tests if an existing entity can be found not specifying a bundle.
   *
   * The entity type in question supports bundles, we just don't
   * restrict the search to bundle here.
   */
  public function testFindEntityWithoutSettingBundle() {
    // Create two bundles.
    EntityTestBundle::create([
      'id' => 'a',
      'label' => 'A',
    ])->save();
    EntityTestBundle::create([
      'id' => 'b',
      'label' => 'B',
    ])->save();

    // Create two entities: one of bundle A and one of bundle B.
    $foo_a = EntityTestWithBundle::create([
      'name' => 'Foo',
      'type' => 'a',
    ]);
    $foo_a->save();
    $bar_b = EntityTestWithBundle::create([
      'name' => 'Bar',
      'type' => 'b',
    ]);
    $bar_b->save();

    // Instantiate the EntityFinder Tamper plugin and configure it to find
    // entity_test_with_bundle entities.
    $config = [
      EntityFinder::SETTING_ENTITY_TYPE => 'entity_test_with_bundle',
      EntityFinder::SETTING_FIELD => 'name',
      'source_definition' => $this->createMock(SourceDefinitionInterface::class),
    ];

    /** @var \Drupal\tamper\TamperInterface $plugin */
    $plugin = $this->pluginManager->createInstance('entity_finder', $config);

    // Assert that the entities can be found.
    $this->assertEquals($foo_a->id(), $plugin->tamper('Foo'));
    $this->assertEquals($bar_b->id(), $plugin->tamper('Bar'));
    // Assert that a non-existing entity results into NULL.
    $this->assertNull($plugin->tamper('Qux'));
  }

  /**
   * Tests if an existing entity can be found that has no bundle.
   *
   * The entity type in question does NOT support bundles.
   */
  public function testFindEntityWithoutBundle() {
    // Create two entities.
    $foo = EntityTestNoBundle::create([
      'name' => 'Foo',
    ]);
    $foo->save();
    $bar = EntityTestNoBundle::create([
      'name' => 'Bar',
    ]);
    $bar->save();

    // Instantiate the EntityFinder Tamper plugin and configure it to find
    // entity_test_no_bundle entities.
    $config = [
      EntityFinder::SETTING_ENTITY_TYPE => 'entity_test_no_bundle',
      EntityFinder::SETTING_FIELD => 'name',
      'source_definition' => $this->createMock(SourceDefinitionInterface::class),
    ];

    /** @var \Drupal\tamper\TamperInterface $plugin */
    $plugin = $this->pluginManager->createInstance('entity_finder', $config);

    // Assert that the entities can be found.
    $this->assertEquals($foo->id(), $plugin->tamper('Foo'));
    $this->assertEquals($bar->id(), $plugin->tamper('Bar'));
    // Assert that a non-existing entity results into NULL.
    $this->assertNull($plugin->tamper('Qux'));
  }

  /**
   * Tests if an existing entity can be found by UUID.
   */
  public function testFindEntityByUuid() {
    $content_entity_1 = EntityTest::create(['name' => $this->randomMachineName()]);
    $content_entity_1->save();
    $content_entity_2 = EntityTest::create(['name' => $this->randomMachineName()]);
    $content_entity_2->save();

    // Instantiate the EntityFinder Tamper plugin and configure it to find
    // entity_test entities by UUID.
    $config = [
      EntityFinder::SETTING_ENTITY_TYPE => 'entity_test',
      EntityFinder::SETTING_FIELD => 'uuid',
      'source_definition' => $this->createMock(SourceDefinitionInterface::class),
    ];

    /** @var \Drupal\tamper\TamperInterface $plugin */
    $plugin = $this->pluginManager->createInstance('entity_finder', $config);

    // Assert that the entities can be found.
    $this->assertEquals($content_entity_1->id(), $plugin->tamper($content_entity_1->uuid()));
    $this->assertEquals($content_entity_2->id(), $plugin->tamper($content_entity_2->uuid()));
    // Assert that a non-existing entity results into NULL.
    $this->assertNull($plugin->tamper('Qux'));
  }

  /**
   * Tests if an existing entity can be found by a text field.
   */
  public function testFindEntityByField() {
    // Add a text field.
    FieldStorageConfig::create([
      'field_name' => 'field_text',
      'entity_type' => 'entity_test',
      'type' => 'text',
    ])->save();

    FieldConfig::create([
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'field_name' => 'field_text',
      'label' => 'Text',
    ])->save();

    // Create two entities. Intentional make the name of one entity equal to the
    // field_text value of the other entity, to ensure that the field is used to
    // find the entity by.
    $foo = EntityTest::create([
      'name' => 'Foo',
      'field_text' => 'Bar',
    ]);
    $foo->save();
    $qux = EntityTest::create([
      'name' => 'Qux',
      'field_text' => 'Foo',
    ]);
    $qux->save();

    // Instantiate the EntityFinder Tamper plugin and configure it to find
    // entity_test entities by field_text.
    $config = [
      EntityFinder::SETTING_ENTITY_TYPE => 'entity_test',
      EntityFinder::SETTING_FIELD => 'field_text',
      'source_definition' => $this->createMock(SourceDefinitionInterface::class),
    ];

    /** @var \Drupal\tamper\TamperInterface $plugin */
    $plugin = $this->pluginManager->createInstance('entity_finder', $config);

    // Assert that the entities can be found.
    $this->assertEquals($foo->id(), $plugin->tamper('Bar'));
    $this->assertEquals($qux->id(), $plugin->tamper('Foo'));
    // Assert that a non-existing entity results into NULL.
    $this->assertNull($plugin->tamper('Qux'));
  }

  /**
   * Tests if an existing entity can be found by a field column.
   */
  public function testFindEntityByFieldColumn() {
    // Add a link field to test if an entity can be found based on one of the
    // columns of that field.
    FieldStorageConfig::create([
      'field_name' => 'field_link',
      'entity_type' => 'entity_test',
      'type' => 'link',
    ])->save();

    FieldConfig::create([
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'field_name' => 'field_link',
      'label' => 'Link',
    ])->save();

    // Create two entities with links.
    $foo = EntityTest::create([
      'name' => 'Foo',
      'field_link' => [
        'uri' => 'https://www.example.com/foo',
        'title' => 'Bar',
      ],
    ]);
    $foo->save();
    $qux = EntityTest::create([
      'name' => 'Qux',
      'field_link' => [
        'uri' => 'https://www.example.com/qux',
        'title' => 'Foo',
      ],
    ]);
    $qux->save();

    // Instantiate the EntityFinder Tamper plugin and configure it to find
    // entity_test entities by field_link. Since 'uri' is the main property,
    // when column is not configured, entities should be found by that column.
    $config = [
      EntityFinder::SETTING_ENTITY_TYPE => 'entity_test',
      EntityFinder::SETTING_FIELD => 'field_link',
      'source_definition' => $this->createMock(SourceDefinitionInterface::class),
    ];

    /** @var \Drupal\tamper\TamperInterface $plugin */
    $plugin = $this->pluginManager->createInstance('entity_finder', $config);

    // Assert that the entities can be found.
    $this->assertEquals($foo->id(), $plugin->tamper('https://www.example.com/foo'));
    $this->assertEquals($qux->id(), $plugin->tamper('https://www.example.com/qux'));

    // Now configure the plugin to find entities by title instead.
    $config = [
      EntityFinder::SETTING_ENTITY_TYPE => 'entity_test',
      EntityFinder::SETTING_FIELD => 'field_link',
      EntityFinder::SETTING_COLUMN => 'title',
      'source_definition' => $this->createMock(SourceDefinitionInterface::class),
    ];

    /** @var \Drupal\tamper\TamperInterface $plugin */
    $plugin = $this->pluginManager->createInstance('entity_finder', $config);

    // Assert that the entities can be found.
    $this->assertEquals($foo->id(), $plugin->tamper('Bar'));
    $this->assertEquals($qux->id(), $plugin->tamper('Foo'));

    // Assert that the entities are no longer found by url.
    $this->assertNull($plugin->tamper('https://www.example.com/foo'));
    $this->assertNull($plugin->tamper('https://www.example.com/qux'));
  }

}
