<?php

namespace Drupal\Tests\ds\Functional;

use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Tests DS layout plugins.
 *
 * @group ds
 */
class LayoutPluginTest extends TestBase {

  /**
   * Test basic Display Suite layout plugins.
   */
  public function testFieldPlugin() {
    // Assert our 2 tests layouts are found.
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertSession()->responseContains('Test One column');
    $this->assertSession()->responseContains('Test Two column');

    $layout = [
      'ds_layout' => 'dstest_2col',
    ];

    $assert = [
      'regions' => [
        'left' => '<td colspan="8">' . $this->t('Left') . '</td>',
        'right' => '<td colspan="8">' . $this->t('Right') . '</td>',
      ],
    ];

    $fields = [
      'fields[node_author][region]' => 'left',
      'fields[node_links][region]' => 'left',
      'fields[body][region]' => 'right',
    ];

    $this->dsSelectLayout($layout, $assert);
    $this->dsConfigureUi($fields);

    // Create a node.
    $settings = ['type' => 'article'];
    $node = $this->drupalCreateNode($settings);

    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains('group-left');
    $this->assertSession()->responseContains('group-right');
    $this->assertSession()->responseContains('dstest-2col.css');

    // Alter a region.
    $settings = [
      'type' => 'article',
      'title' => 'Alter me!',
    ];
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains('cool!');
  }

  /**
   * Test reset layout.
   */
  public function testResetLayout() {
    $layout = [
      'ds_layout' => 'ds_reset',
    ];

    $assert = [
      'regions' => [
        'ds_content' => '<td colspan="8">' . $this->t('Content') . '</td>',
      ],
    ];

    $fields = [
      'fields[node_author][region]' => 'ds_content',
    ];

    $this->dsSelectLayout($layout, $assert);
    $this->dsConfigureUi($fields);

    // Create a node.
    $settings = ['type' => 'article'];
    $node = $this->drupalCreateNode($settings);

    $this->drupalGet('node/' . $node->id());
  }

  /**
   * Tests settings default wrappers.
   */
  public function testDefaultWrappers() {
    // Create a node.
    $settings = ['type' => 'article'];
    $node = $this->drupalCreateNode($settings);

    // Select a layout.
    $this->dsSelectLayout();

    // Go to the node.
    $this->drupalGet('node/' . $node->id());

    // Check we don't have empty wrappers.
    $this->assertSession()->responseNotContains('<>');

    // Select 1 col wrapper.
    $assert = [
      'regions' => [
        'ds_content' => '<td colspan="8">' . $this->t('Content') . '</td>',
      ],
    ];
    $this->dsSelectLayout(['ds_layout' => 'ds_1col'], $assert);

    // Go to the node.
    $this->drupalGet('node/' . $node->id());

    // Check we don't have empty wrappers.
    $elements = $this->xpath('//div[@class="node node--type-article node--view-mode-full ds-1col clearfix"]/div/p');
    $this->assertCount(1, $elements);
    $this->assertTrimEqual($elements[0]->getText(), $node->get('body')->value);

    // Switch theme.
    $this->container->get('theme_installer')->install(['ds_test_layout_theme']);
    $config = \Drupal::configFactory()->getEditable('system.theme');
    $config->set('default', 'ds_test_layout_theme')->save();
    drupal_flush_all_caches();

    // Go to the node.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains('id="overridden-ds-1-col-template"');
    $elements = $this->xpath('//div[@class="node node--type-article node--view-mode-full ds-1col clearfix"]/div/p');
    $this->assertCount(1, $elements);
    $this->assertTrimEqual($elements[0]->getText(), $node->get('body')->value);
  }

  /**
   * Test extended layout class.
   */
  public function testExtendedLayout() {
    // Ensure schema validation passes when saving custom layout config */
    /* @see \Drupal\Core\Config\Development\ConfigSchemaChecker::onConfigSave */
    EntityViewDisplay::load('node.article.default')
      ->setThirdPartySetting('ds', 'layout', [
        'id' => 'dstest_1col_extended',
        'library' => NULL,
        'disable_css' => FALSE,
        'entity_classes' => 'all_classes',
        'settings' => [
          'classes' => [
            'layout_class' => [],
          ],
          'wrappers' => [
            'ds_content' => 'div',
          ],
          'outer_wrapper' => 'div',
          'attributes' => '',
          'link_attribute' => '',
          'link_custom' => '',
          'label' => '',
          'extra_config' => TRUE,
        ],
      ])
      ->setThirdPartySetting('ds', 'regions', [
        'ds_content' => [
          'node_title',
        ],
      ])
      ->save();
  }

}
