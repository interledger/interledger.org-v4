<?php

declare(strict_types=1);

namespace Drupal\Tests\entity_block\Functional;

use Drupal\block\Entity\Block;
use Drupal\Tests\block_content\Functional\BlockContentTestBase;

/**
 * Tests UI when adding an Entity Block.
 *
 * @group entity_block
 */
class EntityBlockTest extends BlockContentTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_block',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests Entity block placement.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testEntityBlockPlacement(): void {
    $assert_session = $this->assertSession();

    // Create a node type.
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    $this->drupalLogin($this->adminUser);

    $title = $this->randomMachineName();
    // Create a node that will be placed.
    $this->drupalCreateNode(['type' => 'article', 'title' => $title]);

    $block = Block::create([
      'id' => strtolower($this->randomMachineName()),
      'theme' => 'stark',
      'weight' => 0,
      'status' => TRUE,
      'region' => 'content',
      'plugin' => 'entity_block:node',
      'settings' => [
        'label' => $this->randomString(),
        'provider' => 'entity_block',
        'label_display' => FALSE,
        'entity' => 1,
      ],
      'visibility' => [],
    ]);
    $block->save();

    $this->drupalGet('node/1');
    // Verify title now appears twice.
    $assert_session->pageTextMatchesCount(2, '/' . $title . '/');
  }

}
