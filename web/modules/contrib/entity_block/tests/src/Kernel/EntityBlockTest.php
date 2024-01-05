<?php

declare(strict_types=1);

namespace Drupal\Tests\entity_block\Kernel;

use Drupal\block\Entity\Block;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\SchemaCheckTestTrait;

/**
 * Tests the EntityBlock block plugin.
 *
 * @group entity_block
 * @coversDefaultClass \Drupal\entity_block\Plugin\Block\EntityBlock
 */
final class EntityBlockTest extends EntityKernelTestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'entity_block',
  ];

  public function testBlockConfigSchema(): void {
    $entity = EntityTest::create([
      'name' => $this->randomString(),
    ]);
    $entity->save();
    $block = Block::create([
      'id' => strtolower($this->randomMachineName()),
      'theme' => 'stark',
      'weight' => 0,
      'status' => TRUE,
      'region' => 'content',
      'plugin' => 'entity_block:entity_test',
      'settings' => [
        'label' => $this->randomString(),
        'provider' => 'entity_block',
        'label_display' => FALSE,
        'entity' => $entity->id(),
      ],
      'visibility' => [],
    ]);
    $block->save();
    $this->assertConfigSchemaByName($block->getConfigDependencyName());
  }

  public function testBuild(): void {
    $entity = EntityTest::create([
      'name' => $this->randomString(),
    ]);
    $entity->save();
    $block = Block::create([
      'id' => strtolower($this->randomMachineName()),
      'theme' => 'stark',
      'weight' => 0,
      'status' => TRUE,
      'region' => 'content',
      'plugin' => 'entity_block:entity_test',
      'settings' => [
        'label' => $this->randomString(),
        'provider' => 'entity_block',
        'label_display' => FALSE,
        'entity' => $entity->id(),
      ],
      'visibility' => [],
    ]);
    $block->save();

    $view_builder_build = $this->container->get('entity_type.manager')
      ->getViewBuilder('entity_test')
      ->view($entity, 'default');
    unset($view_builder_build['#entity_test']);

    $block_build = $block->getPlugin()->build();
    unset($block_build['#entity_test']);
    self::assertEquals($view_builder_build, $block_build);

  }

}
