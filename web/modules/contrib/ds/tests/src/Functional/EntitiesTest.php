<?php

namespace Drupal\Tests\ds\Functional;

/**
 * Tests for display of nodes and fields.
 *
 * @group ds
 */
class EntitiesTest extends TestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'field_ui',
    'taxonomy',
    'block',
    'ds',
    'ds_test',
    'ds_switch_view_mode',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setup(): void {
    parent::setup();

    // Enable field templates.
    \Drupal::configFactory()->getEditable('ds.settings')
      ->set('field_template', TRUE)
      ->save();

    $this->container->get('theme_installer')->install(['ds_test_layout_theme']);
    $config = \Drupal::configFactory()->getEditable('system.theme');
    $config->set('default', 'ds_test_layout_theme')->save();
  }

  /**
   * Test basic node display fields.
   */
  public function testDsNodeEntity() {

    $node = $this->entitiesTestSetup();

    // Test theme_hook_suggestions in ds_entity_variables().
    /*$this->drupalGet('node/' . $node->id(), ['query' => ['store_suggestions' => 1]]);
    $cache = $this->container->get('cache.default')->get('ds_test_suggestions');
    $hook_suggestions = $cache->data;
    $expected_hook_suggestions = [
      'ds_2col_stacked',
      'ds_2col_stacked__node',
      'ds_2col_stacked__node_full',
      'ds_2col_stacked__node_article',
      'ds_2col_stacked__node_article_full',
      'ds_2col_stacked__node__1',
    ];
    $this->assertEquals($hook_suggestions, $expected_hook_suggestions);*/

    // Look at node and verify token and block field.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains('node--view-mode-full');
    $this->assertSession()->responseContains('<div class="field field--name-dynamic-token-fieldnode-token-field field--type-ds field--label-hidden field__item">');
    $elements = $this->xpath('//div[@class="field field--name-dynamic-token-fieldnode-token-field field--type-ds field--label-hidden field__item"]');
    $this->assertEquals($elements[0]->find('xpath', 'p')->getText(), $node->getTitle(), 'Token field content found');
    $this->assertSession()->responseContains('group-header');
    $this->assertSession()->responseContains('group-footer');
    $this->assertSession()->responseContains('group-left');
    $this->assertSession()->responseContains('group-right');
    $this->assertSession()->responseContains('<div class="field field--name-node-submitted-by field--type-ds field--label-hidden field__item">');
    $elements = $this->xpath('//div[@class="field field--name-node-submitted-by field--type-ds field--label-hidden field__item"]');
    // Because the user has 'access user profiles' permission, the username is
    // rendered in a link.
    $this->assertSession()->pageTextContains('Submitted by ' . $elements[0]->find('xpath', 'a')->getText() . ' on');

    // Configure teaser layout.
    $teaser = [
      'ds_layout' => 'ds_2col',
    ];
    $teaser_assert = [
      'regions' => [
        'left' => '<td colspan="8">' . $this->t('Left') . '</td>',
        'right' => '<td colspan="8">' . $this->t('Right') . '</td>',
      ],
    ];
    $this->dsSelectLayout($teaser, $teaser_assert, 'admin/structure/types/manage/article/display/teaser');

    $fields = [
      'fields[dynamic_token_field:node-token_field][region]' => 'left',
      'fields[body][region]' => 'right',
      'fields[node_links][region]' => 'right',
    ];
    $this->dsConfigureUi($fields, 'admin/structure/types/manage/article/display/teaser');

    // Switch view mode on full node page.
    $edit = ['ds_switch' => 'teaser'];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->responseContains('node--view-mode-teaser');
    $this->assertSession()->responseContains('group-left');
    $this->assertSession()->responseContains('group-right');
    $this->assertSession()->responseNotContains('group-header');
    $this->assertSession()->responseNotContains('group-footer');

    $edit = ['ds_switch' => ''];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->responseContains('node--view-mode-full');

    // Test all options of a block field.
    $block = [
      'name' => 'Test block field',
    ];
    $this->dsCreateBlockField($block);
    $fields = [
      'fields[dynamic_block_field:node-test_block_field][region]' => 'left',
      'fields[dynamic_token_field:node-token_field][region]' => 'hidden',
      'fields[body][region]' => 'hidden',
      'fields[node_links][region]' => 'hidden',
    ];
    $this->dsConfigureUi($fields);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains('field--name-dynamic-block-fieldnode-test-block-field');

    // Test revisions. Enable the revision view mode.
    $edit = [
      'display_modes_custom[revision]' => '1',
    ];
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->submitForm($edit, 'Save');

    // Enable the override revision mode and configure it.
    $edit = [
      'fs3[override_node_revision]' => TRUE,
      'fs3[override_node_revision_view_mode]' => 'revision',
    ];
    $this->drupalGet('admin/structure/ds/settings');
    $this->submitForm($edit, 'Save configuration');

    // Select layout and configure fields.
    $edit = [
      'ds_layout' => 'ds_2col',
    ];
    $assert = [
      'regions' => [
        'left' => '<td colspan="8">' . $this->t('Left') . '</td>',
        'right' => '<td colspan="8">' . $this->t('Right') . '</td>',
      ],
    ];
    $this->dsSelectLayout($edit, $assert, 'admin/structure/types/manage/article/display/revision');
    $edit = [
      'fields[body][region]' => 'left',
      'fields[node_link][region]' => 'right',
      'fields[node_author][region]' => 'right',
    ];
    $this->dsConfigureUi($edit, 'admin/structure/types/manage/article/display/revision');

    // Create revision of the node.
    $edit = [
      'revision' => TRUE,
      'revision_log[0][value]' => 'Test revision',
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Verify the revision is created.
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($node->id());
    $revision = \Drupal::entityTypeManager()->getStorage('node')->loadRevision($node->getRevisionId());
    $this->assertEquals($revision->revision_log->value, 'Test revision');

    // Assert revision is using 2 col template.
    $this->drupalGet('node/' . $node->id() . '/revisions/1/view');
    $this->assertSession()->pageTextContains('Body');

    // Assert full view is using stacked template.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextNotContains('Body');

    // Test formatter limit on article with tags.
    $edit = [
      'ds_switch' => '',
      'field_tags[0][target_id]' => 'Tag 1',
      'field_tags[1][target_id]' => 'Tag 2',
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $edit = [
      'fields[field_tags][region]' => 'right',
      'fields[field_tags][type]' => 'entity_reference_label',
    ];
    $this->dsConfigureUi($edit);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains('Tag 1');
    $this->assertSession()->pageTextContains('Tag 2');
    $edit = [
      'fields[field_tags][settings_edit_form][third_party_settings][ds][ds_limit]' => '1',
    ];
    $this->dsEditLimitSettings($edit, 'field_tags');
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains('Tag 1');
    $this->assertSession()->pageTextNotContains('Tag 2');

    // Tests using the title field.
    $edit = [
      'fields[node_title][region]' => 'right',
    ];
    $this->dsConfigureUi($edit);

    // Test \Drupal\Component\Utility\Html::escape() on ds_render_field().
    $edit = [
      'title[0][value]' => 'Hi, I am an article <script>alert(\'with a javascript tag in the title\');</script>',
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    // No idea why, but this makes it work.
    drupal_flush_all_caches();
    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="field field--name-node-title field--type-ds field--label-hidden field__item"]/h2');
    $this->assertTrimEqual($elements[0]->getText(), 'Hi, I am an article <script>alert(\'with a javascript tag in the title\');</script>');

    // Test previews while using a ds field.
    $title_key = 'title[0][value]';
    $edit = [$title_key => $this->randomMachineName()];
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, 'Preview');

    $this->assertSession()->pageTextContains($edit[$title_key]);

    // Convert layout from test theme.
    $test_theme_template = [
      'ds_layout' => 'ds_test_layout_theme',
    ];
    $test_theme_template_assert = [
      'regions' => [
        'ds_content' => '<td colspan="8">' . $this->t('Content') . '</td>',
      ],
    ];
    $this->dsSelectLayout($test_theme_template, $test_theme_template_assert, 'admin/structure/types/manage/page/display');
    // Tests using the title field.
    $edit = [
      'fields[node_title][region]' => 'ds_content',
    ];
    $this->dsConfigureUi($edit, 'admin/structure/types/manage/page/display');
    $node = $this->drupalCreateNode(['type' => 'page']);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains('test-template-defined-in-theme-class');
    $this->assertSession()->pageTextContains($node->get('body')->value);
    $this->assertSession()->responseContains('div class="ds-content-wrapper"');

  }

}
