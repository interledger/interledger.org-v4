<?php

declare(strict_types=1);

namespace Drupal\Tests\svg_image_field\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\svg_image_field\Traits\SvgImageFieldCommonTrait;
use Drupal\views\Entity\View;
use Drupal\views\Views;

/**
 * Tests views integration for SVG image fields.
 *
 * @group svg_image_field
 */
final class ViewsIntegrationTest extends KernelTestBase {

  use SvgImageFieldCommonTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'file',
    'filter',
    'image',
    'node',
    'svg_image_field',
    'system',
    'text',
    'user',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['field', 'node', 'filter']);

    // Create content types.
    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();

    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();
  }

  /**
   * Creates an SVG image field on a content type.
   *
   * @param string $field_name
   *   The field name.
   * @param string $bundle
   *   The bundle (content type) to attach the field to.
   * @param string $label
   *   The field label.
   *
   * @return \Drupal\field\Entity\FieldStorageConfig
   *   The created field storage config.
   */
  protected function createSvgImageField(string $field_name, string $bundle, string $label): FieldStorageConfig {
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'svg_image_field',
      'settings' => ['uri_scheme' => 'public'],
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => $bundle,
      'label' => $label,
    ])->save();

    return $field_storage;
  }

  /**
   * Creates a test SVG file entity.
   *
   * @param string $filename
   *   The filename for the SVG file.
   *
   * @return \Drupal\file\Entity\File
   *   The created file entity.
   */
  protected function createSvgFile(string $filename = 'test.svg'): File {
    $classname = str_replace('.svg', '', $filename);
    $svg_content = '<svg class="' . $classname . '" xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="40"/></svg>';
    $uri = 'public://' . $filename;
    file_put_contents($uri, $svg_content);

    $file = File::create([
      'uri' => $uri,
      'filename' => $filename,
      'filemime' => 'image/svg+xml',
      'status' => 1,
    ]);
    $file->save();

    return $file;
  }

  /**
   * Tests that views data is generated correctly for SVG image fields.
   *
   * This test verifies that the correct service is used and that views data
   * is generated without errors, especially on Drupal 11.2+.
   *
   * @covers svg_image_field_field_views_data
   */
  public function testViewsDataGeneration(): void {
    $field_storage = $this->createSvgImageField('field_svg_test', 'article', 'SVG Test Field');

    // Test that views data can be generated without errors.
    // Call the hook directly to test it.
    $views_data = svg_image_field_field_views_data($field_storage);

    // Verify that views data was generated.
    $this->assertNotEmpty($views_data, 'Views data should be generated for SVG image field.');

    // Verify that the field data contains the expected structure.
    $field_name = $field_storage->getName();
    $found_table = FALSE;
    foreach ($views_data as $table_name => $table_data) {
      if (isset($table_data[$field_name . '_target_id'])) {
        $found_table = TRUE;
        // Verify that the relationship is added.
        $this->assertArrayHasKey('relationship', $table_data[$field_name . '_target_id'], 'Relationship should be added to target_id field.');
        $relationship = $table_data[$field_name . '_target_id']['relationship'];
        $this->assertEquals('standard', $relationship['id'], 'Relationship ID should be "standard".');
        $this->assertEquals('file_managed', $relationship['base'], 'Relationship base should be "file_managed".');
        $this->assertEquals('file', $relationship['entity type'], 'Relationship entity type should be "file".');
        $this->assertEquals('fid', $relationship['base field'], 'Relationship base field should be "fid".');
        break;
      }
    }
    $this->assertTrue($found_table, 'Views data table should contain the field.');
  }

  /**
   * Tests that all expected field columns are exposed in views data.
   *
   * @covers svg_image_field_field_views_data
   */
  public function testViewsDataFieldColumns(): void {
    $field_storage = $this->createSvgImageField('field_svg_columns', 'article', 'SVG Columns Test');

    $views_data = svg_image_field_field_views_data($field_storage);
    $field_name = $field_storage->getName();

    // Find the field's table.
    $table_data = NULL;
    foreach ($views_data as $table_name => $data) {
      if (isset($data[$field_name . '_target_id'])) {
        $table_data = $data;
        break;
      }
    }

    $this->assertNotNull($table_data, 'Field table should exist in views data.');

    // Verify expected columns are present.
    $expected_columns = [
      $field_name . '_target_id' => 'Target ID column',
      $field_name . '_alt' => 'Alt text column',
      $field_name . '_title' => 'Title column',
    ];

    foreach ($expected_columns as $column => $description) {
      $this->assertArrayHasKey($column, $table_data, "$description should exist in views data.");
    }
  }

  /**
   * Tests reverse relationship views data.
   *
   * @covers svg_image_field_field_views_data_views_data_alter
   */
  public function testReverseRelationshipViewsData(): void {
    $this->createSvgImageField('field_svg_reverse_test', 'article', 'SVG Reverse Test Field');

    // Get views data using the Views data manager.
    // This will trigger hook_field_views_data_views_data_alter().
    $views_data_manager = $this->container->get('views.views_data');
    $views_data = $views_data_manager->getAll();

    $this->assertArrayHasKey('file_managed', $views_data, 'file_managed table should exist in views data.');

    // Check if reverse relationship was added.
    $pseudo_field_name = 'reverse_field_svg_reverse_test_node';
    $this->assertArrayHasKey(
      $pseudo_field_name,
      $views_data['file_managed'],
      'Reverse relationship should be added to file_managed table.'
    );

    $relationship = $views_data['file_managed'][$pseudo_field_name]['relationship'];
    $this->assertEquals('entity_reverse', $relationship['id'], 'Reverse relationship ID should be "entity_reverse".');
    $this->assertEquals('node', $relationship['entity_type'], 'Reverse relationship entity type should be "node".');
    $this->assertEquals('field_svg_reverse_test', $relationship['field_name'], 'Reverse relationship field name should match.');
  }

  /**
   * Tests views data with multiple SVG fields on multiple bundles.
   *
   * @covers svg_image_field_field_views_data
   */
  public function testMultipleFieldsMultipleBundles(): void {
    // Create fields on different bundles.
    $field_storage_1 = $this->createSvgImageField('field_svg_logo', 'article', 'Article Logo');
    $field_storage_2 = $this->createSvgImageField('field_svg_icon', 'page', 'Page Icon');

    // Also attach first field to page bundle.
    FieldConfig::create([
      'field_name' => 'field_svg_logo',
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'Page Logo',
    ])->save();

    // Get views data.
    $views_data_manager = $this->container->get('views.views_data');
    $views_data = $views_data_manager->getAll();

    // Verify reverse relationships exist for both fields.
    $this->assertArrayHasKey(
      'reverse_field_svg_logo_node',
      $views_data['file_managed'],
      'Reverse relationship for field_svg_logo should exist.'
    );
    $this->assertArrayHasKey(
      'reverse_field_svg_icon_node',
      $views_data['file_managed'],
      'Reverse relationship for field_svg_icon should exist.'
    );

    // Verify views data for both field storages.
    $views_data_1 = svg_image_field_field_views_data($field_storage_1);
    $views_data_2 = svg_image_field_field_views_data($field_storage_2);

    $this->assertNotEmpty($views_data_1, 'Views data for field_svg_logo should exist.');
    $this->assertNotEmpty($views_data_2, 'Views data for field_svg_icon should exist.');
  }

  /**
   * Tests that SVG fields can be queried and filtered in Views.
   *
   * @covers svg_image_field_field_views_data
   */
  public function testViewsQueryWithSvgField(): void {
    $this->createSvgImageField('field_svg', 'article', 'SVG Field');

    // Create test SVG files.
    $file1 = $this->createSvgFile('logo.svg');
    $file2 = $this->createSvgFile('icon.svg');

    // Create nodes with SVG field values.
    $node1 = Node::create([
      'type' => 'article',
      'title' => 'Article with Logo',
      'status' => 1,
      'field_svg' => [
        'target_id' => $file1->id(),
        'alt' => 'Company Logo',
        'title' => 'Our Logo',
      ],
    ]);
    $node1->save();

    $node2 = Node::create([
      'type' => 'article',
      'title' => 'Article with Icon',
      'status' => 1,
      'field_svg' => [
        'target_id' => $file2->id(),
        'alt' => 'Menu Icon',
        'title' => 'Navigation',
      ],
    ]);
    $node2->save();

    // Node without SVG field value.
    $node3 = Node::create([
      'type' => 'article',
      'title' => 'Article without SVG',
      'status' => 1,
    ]);
    $node3->save();

    // Create and save a view that uses the SVG field.
    $view_id = 'test_svg_view';
    View::create([
      'id' => $view_id,
      'label' => 'Test SVG View',
      'base_table' => 'node_field_data',
      'display' => [
        'default' => [
          'display_plugin' => 'default',
          'id' => 'default',
          'display_options' => [
            'fields' => [
              'title' => [
                'id' => 'title',
                'table' => 'node_field_data',
                'field' => 'title',
                'plugin_id' => 'field',
              ],
              'field_svg' => [
                'id' => 'field_svg',
                'table' => 'node__field_svg',
                'field' => 'field_svg',
                'plugin_id' => 'field',
              ],
            ],
            'filters' => [
              'type' => [
                'id' => 'type',
                'table' => 'node_field_data',
                'field' => 'type',
                'value' => ['article' => 'article'],
                'plugin_id' => 'bundle',
              ],
            ],
          ],
        ],
      ],
    ])->save();

    // Execute the view.
    $view = Views::getView($view_id);
    $this->assertNotNull($view, 'View should be loadable.');

    $view->setDisplay('default');
    $view->execute();

    // Verify all 3 nodes are returned.
    $this->assertCount(3, $view->result, 'View should return all 3 article nodes.');
  }

  /**
   * Tests filtering views by SVG field alt text.
   *
   * @covers svg_image_field_field_views_data
   */
  public function testViewsFilterBySvgAltText(): void {
    $this->createSvgImageField('field_svg', 'article', 'SVG Field');

    // Create test files and nodes.
    $file1 = $this->createSvgFile('logo.svg');
    $file2 = $this->createSvgFile('icon.svg');

    Node::create([
      'type' => 'article',
      'title' => 'Article with Logo',
      'status' => 1,
      'field_svg' => [
        'target_id' => $file1->id(),
        'alt' => 'Company Logo',
        'title' => 'Our Logo',
      ],
    ])->save();

    Node::create([
      'type' => 'article',
      'title' => 'Article with Icon',
      'status' => 1,
      'field_svg' => [
        'target_id' => $file2->id(),
        'alt' => 'Menu Icon',
        'title' => 'Navigation',
      ],
    ])->save();

    // Create a view that filters by alt text.
    $view_id = 'test_svg_filter_view';
    View::create([
      'id' => $view_id,
      'label' => 'Test SVG Filter View',
      'base_table' => 'node_field_data',
      'display' => [
        'default' => [
          'display_plugin' => 'default',
          'id' => 'default',
          'display_options' => [
            'fields' => [
              'title' => [
                'id' => 'title',
                'table' => 'node_field_data',
                'field' => 'title',
                'plugin_id' => 'field',
              ],
            ],
            'filters' => [
              'field_svg_alt' => [
                'id' => 'field_svg_alt',
                'table' => 'node__field_svg',
                'field' => 'field_svg_alt',
                'value' => 'Company Logo',
                'plugin_id' => 'string',
              ],
            ],
          ],
        ],
      ],
    ])->save();

    $view = Views::getView($view_id);
    $view->setDisplay('default');
    $view->execute();

    $this->assertCount(1, $view->result, 'Filter by alt should return 1 result.');
  }

  /**
   * Tests the file relationship in views.
   *
   * @covers svg_image_field_field_views_data
   */
  public function testViewsFileRelationship(): void {
    $this->createSvgImageField('field_svg', 'article', 'SVG Field');

    $file = $this->createSvgFile('test-relationship.svg');

    Node::create([
      'type' => 'article',
      'title' => 'Article with SVG',
      'status' => 1,
      'field_svg' => [
        'target_id' => $file->id(),
        'alt' => 'Test Alt',
        'title' => 'Test Title',
      ],
    ])->save();

    // Create a view with the file relationship.
    $view_id = 'test_svg_relationship';
    View::create([
      'id' => $view_id,
      'label' => 'Test SVG Relationship',
      'base_table' => 'node_field_data',
      'display' => [
        'default' => [
          'display_plugin' => 'default',
          'id' => 'default',
          'display_options' => [
            'relationships' => [
              'field_svg_target_id' => [
                'id' => 'field_svg_target_id',
                'table' => 'node__field_svg',
                'field' => 'field_svg_target_id',
                'plugin_id' => 'standard',
                'required' => TRUE,
              ],
            ],
            'fields' => [
              'title' => [
                'id' => 'title',
                'table' => 'node_field_data',
                'field' => 'title',
                'plugin_id' => 'field',
              ],
              'filename' => [
                'id' => 'filename',
                'table' => 'file_managed',
                'field' => 'filename',
                'relationship' => 'field_svg_target_id',
                'plugin_id' => 'field',
              ],
            ],
          ],
        ],
      ],
    ])->save();

    $view = Views::getView($view_id);
    $view->setDisplay('default');
    $view->execute();

    $this->assertCount(1, $view->result, 'View with relationship should return the node.');
    // Verify the file relationship was used (filename field should be present).
    $this->assertArrayHasKey('filename', $view->field, 'Filename field from relationship should exist.');
  }

  /**
   * Tests views data relationship label contains field name.
   *
   * @covers svg_image_field_field_views_data
   */
  public function testRelationshipLabel(): void {
    $field_storage = $this->createSvgImageField('field_svg_label_test', 'article', 'SVG Label Test');

    $views_data = svg_image_field_field_views_data($field_storage);

    // Find the relationship and check its label.
    foreach ($views_data as $table_name => $table_data) {
      if (isset($table_data['field_svg_label_test_target_id']['relationship'])) {
        $relationship = $table_data['field_svg_label_test_target_id']['relationship'];
        // Verify label contains the field name.
        $this->assertStringContainsString(
          'field_svg_label_test',
          (string) $relationship['label'],
          'Relationship label should contain the field name.'
        );
        break;
      }
    }
  }

  /**
   * Tests that views data integrates properly with field cardinality.
   *
   * @covers svg_image_field_field_views_data
   */
  public function testMultiValueFieldViewsData(): void {
    // Create a multi-value SVG field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_svg_multi',
      'entity_type' => 'node',
      'type' => 'svg_image_field',
      'cardinality' => 3,
      'settings' => ['uri_scheme' => 'public'],
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_name' => 'field_svg_multi',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Multiple SVG Images',
    ])->save();

    $views_data = svg_image_field_field_views_data($field_storage);

    // Verify views data was generated.
    $this->assertNotEmpty($views_data, 'Views data should be generated for multi-value SVG field.');

    // Find the relationship.
    $found_relationship = FALSE;
    foreach ($views_data as $table_data) {
      if (isset($table_data['field_svg_multi_target_id']['relationship'])) {
        $found_relationship = TRUE;
        break;
      }
    }
    $this->assertTrue($found_relationship, 'Relationship should exist for multi-value field.');
  }

  /**
   * Tests the reverse relationship details.
   *
   * @covers svg_image_field_field_views_data_views_data_alter
   */
  public function testReverseRelationshipDetails(): void {
    $this->createSvgImageField('field_svg_details', 'article', 'SVG Details Test');

    $views_data_manager = $this->container->get('views.views_data');
    $views_data = $views_data_manager->getAll();

    $pseudo_field_name = 'reverse_field_svg_details_node';
    $this->assertArrayHasKey($pseudo_field_name, $views_data['file_managed']);

    $relationship = $views_data['file_managed'][$pseudo_field_name]['relationship'];

    // Check all expected relationship properties.
    $this->assertArrayHasKey('title', $relationship, 'Relationship should have a title.');
    $this->assertArrayHasKey('label', $relationship, 'Relationship should have a label.');
    $this->assertArrayHasKey('help', $relationship, 'Relationship should have help text.');
    $this->assertArrayHasKey('group', $relationship, 'Relationship should have a group.');
    $this->assertArrayHasKey('base', $relationship, 'Relationship should have a base table.');
    $this->assertArrayHasKey('base field', $relationship, 'Relationship should have a base field.');
    $this->assertArrayHasKey('field table', $relationship, 'Relationship should have a field table.');
    $this->assertArrayHasKey('field field', $relationship, 'Relationship should have a field field.');
    $this->assertArrayHasKey('join_extra', $relationship, 'Relationship should have join_extra.');

    // Verify the join_extra contains the deleted filter.
    $this->assertIsArray($relationship['join_extra']);
    $this->assertArrayHasKey(0, $relationship['join_extra']);
    $this->assertEquals('deleted', $relationship['join_extra'][0]['field']);
    $this->assertEquals(0, $relationship['join_extra'][0]['value']);
    $this->assertTrue($relationship['join_extra'][0]['numeric']);
  }

}
