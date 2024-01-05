<?php

namespace Drupal\svg_image_field\Plugin\Field\FieldFormatter;

use Drupal\image\Plugin\Field\FieldFormatter\ImageUrlFormatter;

/**
 * Plugin implementation of the 'svg_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "svg_image_url_formatter",
 *   label = @Translation("SVG Image URL formatter"),
 *   field_types = {
 *     "svg_image_field"
 *   }
 * )
 */
class SvgImageUrlFormatter extends ImageUrlFormatter {

}
