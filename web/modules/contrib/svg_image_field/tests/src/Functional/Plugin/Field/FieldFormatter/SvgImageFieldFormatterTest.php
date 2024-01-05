<?php

namespace Drupal\Tests\svg_image_field\Functional\Plugin\Field\FieldFormatter;

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
  protected $svg_filename = 'valid_svg--with-xml-doctype.svg';

  /**
   * The alternate text for the SVG file.
   *
   * @var string
   */
  protected $svg_alt_text = 'a valid svg with xml doctype';

  /**
   * Tests displaying a SVG image using the default settings.
   */
  public function testDisplayImage() {
    $storage_settings = ['uri_scheme' => 'public'];
    $this->createSvgImageField('field_svg', 'article', $storage_settings);

    // Create a node.
    $image_uri = $this->resourcesPath() . '/' . $this->svg_filename;
    $this->uploadNodeImage($image_uri, 'field_svg', 'article', $this->svg_alt_text);

    // Load the file.
    $file = $this->container->get('entity_type.manager')
      ->getStorage('file')
      ->load(1);

    // Assert that the SVG image is displayed.
    $this->assertSession()->elementsCount('xpath', '//img[@src="' . $file->createFileUrl() . '" and @alt="' . $this->svg_alt_text . '" and @width="25" and @height="25"]', 1);

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
    $image_uri = $this->resourcesPath() . '/' . $this->svg_filename;
    $this->uploadNodeImage($image_uri, 'field_svg', 'article', $this->svg_alt_text);

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
    $image_uri = $this->resourcesPath() . '/' . $this->svg_filename;
    $nid = $this->uploadNodeImage($image_uri, 'field_svg', 'article', $this->svg_alt_text);

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

}
