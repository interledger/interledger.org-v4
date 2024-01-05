<?php

namespace Drupal\Tests\svg_image_field\Traits;

/**
 * Provides methods useful for Kernel and Functional SVG Image Field tests.
 *
 * This trait is meant to be used only by test classes.
 */
trait SvgImageFieldCommonTrait {

  /**
   * Returns the absolute path to the Drupal root.
   *
   * @return string
   *   The absolute path to the directory where Drupal is installed.
   */
  protected function absolute(): string {
    return realpath(getcwd());
  }

  /**
   * Returns the absolute directory path of the SVG Image Field module.
   *
   * @return string
   *   The absolute path to the SVG Image Field module.
   */
  protected function absolutePath(): string {
    return $this->absolute() . '/' . $this->getModulePath('svg_image_field');
  }

  /**
   * Returns the url to the SVG Image Field resources directory.
   *
   * @return string
   *   The url to the SVG Image Field resources directory.
   */
  protected function resourcesUrl(): string {
    return \Drupal::request()->getSchemeAndHttpHost() . '/' . $this->getModulePath('svg_image_field') . '/tests/resources';
  }

  /**
   * Gets the path for the specified module.
   *
   * @param string $module_name
   *   The module name.
   *
   * @return string
   *   The Drupal-root relative path to the module directory.
   *
   * @throws \Drupal\Core\Extension\Exception\UnknownExtensionException
   *   If the module does not exist.
   */
  protected function getModulePath(string $module_name): string {
    return \Drupal::service('extension.list.module')->getPath($module_name);
  }

  /**
   * Returns the absolute directory path of the resources folder.
   *
   * @return string
   *   The absolute path to the resources folder.
   */
  protected function resourcesPath(): string {
    return $this->absolutePath() . '/tests/resources';
  }

}
