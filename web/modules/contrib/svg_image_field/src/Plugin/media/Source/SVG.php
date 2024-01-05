<?php

namespace Drupal\svg_image_field\Plugin\media\Source;

use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\media\Plugin\media\Source\File;

/**
 * Provides media type plugin for SVG image field.
 *
 * @MediaSource(
 *   id = "svg",
 *   label = @Translation("SVG"),
 *   description = @Translation("Provides business logic and metadata for SVG files."),
 *   allowed_field_types = {"svg_image_field"},
 *   default_thumbnail_filename = "generic.png",
 *   thumbnail_alt_metadata_attribute = "thumbnail_alt_value"
 * )
 */
class SVG extends File {

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type) {
    return parent::createSourceField($type)->set('settings', ['file_extensions' => 'svg']);
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $name) {
    /** @var \Drupal\file\FileInterface $file */
    $file = $media->get($this->configuration['source_field'])->entity;

    // If the source field is not required, it may be empty.
    if (!$file) {
      return parent::getMetadata($media, $name);
    }

    // Use the SVG file as the thumbnail. This may cause no thumbnail to be
    // output depending on your image processor and image style output for
    // the thumbnail.
    // See https://drupal.org/i/3105482 for more info.
    if ($name === 'thumbnail_uri') {
      return $file->getFileUri();
    }

    if ($name === 'thumbnail_alt_value') {
      return $media->get($this->configuration['source_field'])->alt ?: parent::getMetadata($media, $name);
    }

    return parent::getMetadata($media, $name);
  }

}
