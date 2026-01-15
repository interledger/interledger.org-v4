<?php

/**
 * @file
 * Post-update functions for svg_image_field module.
 */

declare(strict_types=1);

/**
 * Invalidate plugin cache after renaming the SVG media source class.
 */
function svg_image_field_post_update_rename_svg_media_source() {
  // Check for expected file path.
  $path = \Drupal::service('extension.list.module')->getPath('svg_image_field');
  $expected_file = $path . '/src/Plugin/media/Source/ScalableVectorGraphic.php';

  if (!file_exists($expected_file)) {
    $message = 'Media Source plugin (svg) not found. The ScalableVectorGraphic class file is missing. If you recently upgraded the SVG Image Field module, Git or Composer may have failed to apply a case-only rename (SVG.php → Svg.php → ScalableVectorGraphic.php). To fix this, delete the old SVG.php or Svg.php file (if present), and ensure ScalableVectorGraphic.php exists in svg_image_field/src/Plugin/media/Source. One way to do this is to remove and re-require the module with composer. See <a href="@url">Issue #3516563</a> for more information.';
    $params = ['@url' => 'https://www.drupal.org/project/svg_image_field/issues/3516563'];
    \Drupal::logger('svg_image_field')->warning($message, $params);
    \Drupal::messenger()->addWarning($message, $params);
  }
  else {
    // Clear plugin manager cache to ensure discovery of the new class.
    if (\Drupal::moduleHandler()->moduleExists('media')) {
      \Drupal::service('plugin.manager.media.source')->clearCachedDefinitions();
      $message = 'The SVG media source class has been renamed. Cache has been rebuilt.';
    }
    else {
      $message = 'Media module is not enabled. Skipping media source plugin cache clear.';
    }
    \Drupal::logger('svg_image_field')->notice($message);
    \Drupal::messenger()->addStatus($message);
  }
}
