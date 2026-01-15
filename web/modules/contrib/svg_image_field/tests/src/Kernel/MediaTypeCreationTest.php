<?php

declare(strict_types=1);

namespace Drupal\Tests\svg_image_field\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Tests creation of media types.
 *
 * @group svg_image_field
 */
final class MediaTypeCreationTest extends KernelTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'file',
    'image',
    'media',
    'svg_image_field',
    'system',
    'user',
  ];

  /**
   * Tests that media types can be created with the "svg" source.
   */
  public function testMediaTypeCreation(): void {
    $svg_media_type = $this->createMediaType('svg', [
      'id' => 'svg',
      'label' => 'SVG',
    ]);
  }

}
