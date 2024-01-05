<?php

namespace Drupal\Tests\svg_image_field\Kernel;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\Tests\svg_image_field\Traits\SvgImageFieldCommonTrait;

/**
 * Tests validity of SVG files.
 *
 * @group svg_image_field
 */
class FileValidationTest extends FieldKernelTestBase {

  use StringTranslationTrait;
  use SvgImageFieldCommonTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['file', 'svg_image_field'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);

    FieldStorageConfig::create([
      'field_name' => 'file_test',
      'entity_type' => 'entity_test',
      'type' => 'file',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'file_test',
      'bundle' => 'entity_test',
      'settings' => ['file_directory' => $this->resourcesPath()],
    ])->save();
  }

  /**
   * Checks that a list of files are validated as expected.
   *
   * @covers svg_image_field_validate_mime_type
   */
  public function testFileValidation() {
    $files = scandir($this->resourcesPath());
    foreach ($files as $file_name) {
      if (strpos($file_name, 'valid_svg') === 0) {
        $file_path = realpath($this->resourcesPath() . '/' . $file_name);
        $file = File::create([
          'uri' => $file_path,
          'uid' => 1,
          'status' => 1,
        ]);
        $file->setFilename($file_name);
        $this->assertCount(0, svg_image_field_validate_mime_type($file), strtr('%file_name is valid.', [
          '%file_name' => $file_name,
        ]));
      }
      elseif (strpos($file_name, 'invalid_svg') === 0) {
        $file_path = realpath($this->resourcesPath() . '/' . $file_name);
        $file = File::create([
          'uri' => $file_path,
          'uid' => 1,
          'status' => 1,
        ]);
        $file->setFilename($file_name);
        $this->assertGreaterThanOrEqual(1, count(svg_image_field_validate_mime_type($file)), strtr('%file_name is invalid.', [
          '%file_name' => $file_name,
        ]));
      }
    }
  }

}
