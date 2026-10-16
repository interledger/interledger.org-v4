<?php

namespace Drupal\Tests\svg_image_field\Functional\Plugin\Field\FieldFormatter;

use Drupal\file\Entity\File;
use Drupal\Tests\svg_image_field\Functional\SvgImageFieldBrowserTestBase;
use Drupal\Tests\svg_image_field\Traits\SvgImageFieldCommonTrait;

/**
 * Tests for the field formatter "svg_image_field_formatter".
 *
 * @group svg_image_field
 */
class SvgImageFieldFormatterTest extends SvgImageFieldBrowserTestBase {

  use SvgImageFieldCommonTrait;

  /**
   * A valid SVG file in the resources folder.
   *
   * @var string
   */
  protected $svgFilename = 'valid_svg--with-xml-doctype.svg';

  /**
   * The alternate text for the SVG file.
   *
   * @var string
   */
  protected $svgAltText = 'a valid svg with xml doctype';

  /**
   * Tests displaying a SVG image using the default settings.
   */
  public function testDisplayImage() {
    $storage_settings = ['uri_scheme' => 'public'];
    $this->createSvgImageField('field_svg', 'article', $storage_settings);

    // Create a node.
    $image_uri = $this->resourcesPath() . '/' . $this->svgFilename;
    $this->uploadNodeImage($image_uri, 'field_svg', 'article', $this->svgAltText);

    // Load the file.
    $file = $this->container->get('entity_type.manager')
      ->getStorage('file')
      ->load(1);

    // Assert that the SVG image is displayed.
    $this->assertSession()->elementsCount('xpath', '//img[@src="' . $file->createFileUrl() . '" and @alt="' . $this->svgAltText . '" and @width="25" and @height="25"]', 1);

    // Verify that the image can be downloaded.
    $this->assertEquals(file_get_contents($image_uri), $this->drupalGet($file->createFileUrl(FALSE)), 'File was downloaded successfully.');
  }

  /**
   * Tests displaying a SVG image inline.
   */
  public function testDisplayImageInline() {
    $storage_settings = ['uri_scheme' => 'public'];
    $formatter_settings = [
      'inline' => TRUE,
    ];
    $this->createSvgImageField('field_svg', 'article', $storage_settings, [], [], $formatter_settings);

    // Create a node.
    $image_uri = $this->resourcesPath() . '/' . $this->svgFilename;
    $this->uploadNodeImage($image_uri, 'field_svg', 'article', $this->svgAltText);

    // Assert that the SVG is displayed inline.
    $this->assertSession()->elementsCount('xpath', '//svg[@width="25" and @height="25"]', 1);
  }

  /**
   * Tests that node gets displayed when a SVG image field is missing.
   */
  public function testDisplayMissingImageInline() {
    $storage_settings = ['uri_scheme' => 'public'];
    $formatter_settings = [
      'inline' => TRUE,
    ];
    $this->createSvgImageField('field_svg', 'article', $storage_settings, [], [], $formatter_settings);

    // Create a node.
    $image_uri = $this->resourcesPath() . '/' . $this->svgFilename;
    $nid = $this->uploadNodeImage($image_uri, 'field_svg', 'article', $this->svgAltText);

    // Remove the image and clear caches.
    $file = $this->container->get('entity_type.manager')
      ->getStorage('file')
      ->load(1);
    unlink($file->uri->value);
    drupal_flush_all_caches();

    // Assert that the file no longer exists.
    $this->drupalGet($file->createFileUrl(FALSE));
    $this->assertSession()->statusCodeEquals(404);

    // Assert that the node still can get displayed.
    $this->drupalGet('node/' . $nid);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Foo');
  }

  /**
   * Tests missing SVG image with stage_file_proxy module enabled.
   */
  public function testDisplayMissingImageInlineWithStageFileProxy() {
    $this->assertTrue($this->container->get('module_installer')->install(['stage_file_proxy']));
    $this->testDisplayMissingImageInline();
  }

  /**
   * Tests displaying a default SVG image when field is empty.
   */
  public function testDefaultImage() {
    // Create a default image file.
    $default_image = $this->createDefaultImageFile();

    $storage_settings = [
      'uri_scheme' => 'public',
      'default_image' => [
        'uuid' => $default_image->uuid(),
        'alt' => 'default svg alt',
        'title' => 'default svg title',
      ],
    ];
    $this->createSvgImageField('field_svg', 'article', $storage_settings);

    // Create a node without uploading an image.
    $this->drupalGet('node/add/article');
    $this->submitForm(['title[0][value]' => 'Test node without image'], 'Save');

    // Assert that the default SVG image is displayed.
    $this->assertSession()->elementExists('xpath', '//img[contains(@src, "valid_svg--no-doctype.svg") and @alt="default svg alt" and @width="25" and @height="25"]');
  }

  /**
   * Tests displaying default SVG image inline when field is empty.
   */
  public function testDefaultImageInline() {
    // Create a default image file.
    $default_image = $this->createDefaultImageFile();

    $storage_settings = [
      'uri_scheme' => 'public',
      'default_image' => [
        'uuid' => $default_image->uuid(),
        'alt' => 'default svg alt',
        'title' => 'default svg title',
      ],
    ];
    $formatter_settings = [
      'inline' => TRUE,
    ];
    $this->createSvgImageField('field_svg', 'article', $storage_settings, [], [], $formatter_settings);

    // Create a node without uploading an image.
    $this->drupalGet('node/add/article');
    $this->submitForm(['title[0][value]' => 'Test node without image'], 'Save');

    // Assert that the default SVG is displayed inline.
    $this->assertSession()->elementExists('xpath', '//svg[@width="25" and @height="25"]');
  }

  /**
   * Tests that uploaded image takes precedence over default image.
   */
  public function testUploadedImageOverridesDefault() {
    // Create a default image file.
    $default_image = $this->createDefaultImageFile();

    $storage_settings = [
      'uri_scheme' => 'public',
      'default_image' => [
        'uuid' => $default_image->uuid(),
        'alt' => 'default svg alt',
        'title' => 'default svg title',
      ],
    ];
    $this->createSvgImageField('field_svg', 'article', $storage_settings);

    // Create a node with an uploaded image.
    $image_uri = $this->resourcesPath() . '/' . $this->svgFilename;
    $this->uploadNodeImage($image_uri, 'field_svg', 'article', $this->svgAltText);

    // Load the uploaded file.
    $file = $this->container->get('entity_type.manager')
      ->getStorage('file')
      ->loadByProperties(['filename' => $this->svgFilename]);
    $file = reset($file);

    // Assert that the uploaded SVG image is displayed, not the default.
    $this->assertSession()->elementsCount('xpath', '//img[@src="' . $file->createFileUrl() . '" and @alt="' . $this->svgAltText . '"]', 1);
    // Assert that the default image is not displayed.
    $this->assertSession()->elementNotExists('xpath', '//img[@alt="default svg alt"]');
  }

  /**
   * Tests default image configured at field instance level.
   */
  public function testDefaultImageAtFieldLevel() {
    // Create a default image file.
    $default_image = $this->createDefaultImageFile();

    $storage_settings = [
      'uri_scheme' => 'public',
    ];
    $field_settings = [
      'default_image' => [
        'uuid' => $default_image->uuid(),
        'alt' => 'field level default alt',
        'title' => 'field level default title',
      ],
    ];
    $this->createSvgImageField('field_svg', 'article', $storage_settings, $field_settings);

    // Create a node without uploading an image.
    $this->drupalGet('node/add/article');
    $this->submitForm(['title[0][value]' => 'Test node without image'], 'Save');

    // Assert that the default SVG image is displayed with field-level alt.
    $this->assertSession()->elementExists('xpath', '//img[contains(@src, "valid_svg--no-doctype.svg") and @alt="field level default alt"]');
  }

  /**
   * Tests uploading a default image via the field settings form.
   *
   * This ensures the upload validators are correctly configured for Drupal
   * 10.2+ which uses class-based constraint validators.
   *
   * @see https://www.drupal.org/project/svg_image_field/issues/2987590
   */
  public function testDefaultImageUploadInFieldSettings() {
    $storage_settings = ['uri_scheme' => 'public'];
    $this->createSvgImageField('field_svg', 'article', $storage_settings);

    // Navigate to the field edit page (contains both storage and instance
    // settings in Drupal 10+).
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.field_svg');
    $this->assertSession()->statusCodeEquals(200);

    // Verify the storage settings section exists.
    $this->assertSession()->elementExists('css', '#edit-field-storage');

    // Upload a default image to the storage-level default image field.
    $image_uri = $this->resourcesPath() . '/valid_svg--no-doctype.svg';
    $edit = [
      'files[field_storage_subform_settings_default_image_uuid]' => $image_uri,
    ];
    $this->submitForm($edit, 'Upload');

    // Verify no AJAX/500 errors - the upload should succeed.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('PluginNotFoundException');
    $this->assertSession()->pageTextNotContains('does not exist');

    // Fill in alt text and save.
    $edit = [
      'field_storage[subform][settings][default_image][alt]' => 'Storage default SVG alt',
    ];
    $this->submitForm($edit, 'Save settings');

    // Verify settings were saved.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Saved');
  }

  /**
   * Tests uploading a default image via the field instance settings.
   */
  public function testDefaultImageUploadInFieldInstanceSettings() {
    $storage_settings = ['uri_scheme' => 'public'];
    $this->createSvgImageField('field_svg', 'article', $storage_settings);

    // Navigate to field edit page.
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.field_svg');
    $this->assertSession()->statusCodeEquals(200);

    // Upload a default image to the instance-level default image field.
    $image_uri = $this->resourcesPath() . '/valid_svg--no-doctype.svg';
    $edit = [
      'files[settings_default_image_uuid]' => $image_uri,
    ];
    $this->submitForm($edit, 'Upload');

    // Verify no AJAX/500 errors.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('PluginNotFoundException');
    $this->assertSession()->pageTextNotContains('does not exist');

    // Fill in alt text and save.
    $edit = [
      'settings[default_image][alt]' => 'Instance default SVG alt',
    ];
    $this->submitForm($edit, 'Save settings');

    // Verify settings were saved.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Saved');
  }

  /**
   * Creates a default image file for testing.
   *
   * @return \Drupal\file\Entity\File
   *   The created file entity.
   */
  protected function createDefaultImageFile(): File {
    $source = $this->resourcesPath() . '/valid_svg--no-doctype.svg';
    $destination = 'public://valid_svg--no-doctype.svg';
    $this->container->get('file_system')->copy($source, $destination, TRUE);

    $file = File::create([
      'uri' => $destination,
      'filename' => 'valid_svg--no-doctype.svg',
      'status' => 1,
    ]);
    $file->save();

    return $file;
  }

}
