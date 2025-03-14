<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Plugin\Tamper\EntityFinder;
use Prophecy\Argument;

/**
 * Tests the Entity Finder plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\EntityFinder
 * @group tamper
 */
class EntityFinderTest extends TamperPluginTestBase {

  /**
   * The entity type manager.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * An entity type definition.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityTypeDefinition;

  /**
   * The entity type bundle info.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Field manager used in the test.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityTypeBundleInfo = $this->prophesize(EntityTypeBundleInfoInterface::class);
    $this->entityFieldManager = $this->prophesize(EntityFieldManagerInterface::class);
    $this->entityTypeDefinition = $this->prophesize(EntityTypeInterface::class);

    $this->entityTypeDefinition->hasKey('bundle')
      ->willReturn(TRUE);

    $this->entityTypeManager->getDefinitions()
      ->willReturn([
        'entity_test' => $this->entityTypeDefinition->reveal(),
      ]);
    $this->entityTypeManager->getDefinition('entity_test')
      ->willReturn($this->entityTypeDefinition->reveal());
    $this->entityTypeManager->getDefinition('non_existent')
      ->willReturn(NULL);
    $this->entityTypeBundleInfo->getBundleInfo(Argument::type('string'))
      ->willReturn([]);
    $this->entityFieldManager->getFieldDefinitions(Argument::type('string'), Argument::type('string'))
      ->willReturn([]);

    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    $config = [
      EntityFinder::SETTING_ENTITY_TYPE => 'entity_test',
      EntityFinder::SETTING_BUNDLE => 'entity_test',
      EntityFinder::SETTING_FIELD => 'name',
    ];

    return new EntityFinder($config, 'default_value', [], $this->getMockSourceDefinition(), $this->entityTypeManager->reveal(), $this->entityTypeBundleInfo->reveal(), $this->entityFieldManager->reveal());
  }

  /**
   * Tests building the form when the entity type no longer exists.
   */
  public function testBuildConfigurationFormWithInvalidEntityType() {
    $this->plugin->setConfiguration([
      EntityFinder::SETTING_ENTITY_TYPE => 'non_existent',
    ]);
    $this->assertIsArray($this->plugin->buildConfigurationForm([], $this->createMock(FormStateInterface::class)));
  }

}
