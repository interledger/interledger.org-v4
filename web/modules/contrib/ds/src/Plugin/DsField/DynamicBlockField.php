<?php

namespace Drupal\ds\Plugin\DsField;

use Drupal\ds\Attribute\DsField;
use Drupal\ds\Plugin\Derivative\DynamicBlockField as DynamicBlockFieldDerivative;
use Drupal\views\Plugin\Block\ViewsBlock;

/**
 * Defines a generic dynamic block field.
 */
#[DsField(
  id: 'dynamic_block_field',
  deriver: DynamicBlockFieldDerivative::class,
  provider: 'block'
)]
class DynamicBlockField extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockPluginId() {
    $definition = $this->getPluginDefinition();
    return $definition['properties']['block'];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockConfig() {
    $block_config = [];
    $definition = $this->getPluginDefinition();
    if (isset($definition['properties']['config'])) {
      $block_config = $definition['properties']['config'];
    }

    return $block_config;
  }

  /**
   * Returns the title of the block.
   */
  public function getTitle() {
    $field = $this->getFieldConfiguration();
    $title = $field['title'];

    if (isset($field['properties']['use_block_title']) && $field['properties']['use_block_title']) {
      /* @var $block \Drupal\Core\Block\BlockPluginInterface */
      $block = $this->getBlock();

      if ($block instanceof ViewsBlock) {
        $block_build = $block->build();
        if (!empty($block_build['#title'])) {
          $title = $block_build['#title'];
        }
      }
      else {
        $title = $block->label();
      }
    }

    return $title;
  }

}
