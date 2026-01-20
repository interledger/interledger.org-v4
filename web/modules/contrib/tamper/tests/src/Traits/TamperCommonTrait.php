<?php

namespace Drupal\Tests\tamper\Traits;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Provides methods useful for Kernel and Functional Tamper tests.
 *
 * This trait is meant to be used only by test classes.
 */
trait TamperCommonTrait {

  /**
   * Creates a field and an associated field storage.
   *
   * @param string $field_name
   *   The name of the field.
   * @param array $config
   *   (optional) The field storage and instance configuration:
   *   - entity_type: (optional) the field's entity type. Defaults to
   *     'entity_test'.
   *   - bundle: (optional) the field's bundle. Defaults to 'entity_test'.
   *   - type: (optional) the field's type. Defaults to 'text'.
   *   - label: (optional) the field's label. Defaults to the field's name +
   *     the string ' label'.
   *   - storage: (optional) additional keys for the field's storage.
   *   - field: (optional) additional keys for the field.
   */
  protected function createFieldWithStorage($field_name, array $config = []) {
    $config += [
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'type' => 'text',
      'label' => $field_name . ' label',
      'storage' => [],
      'field' => [],
    ];

    FieldStorageConfig::create($config['storage'] + [
      'field_name' => $field_name,
      'entity_type' => $config['entity_type'],
      'type' => $config['type'],
      'settings' => [],
    ])->save();

    FieldConfig::create($config['field'] + [
      'entity_type' => $config['entity_type'],
      'bundle' => $config['bundle'],
      'field_name' => $field_name,
      'label' => $config['label'],
    ])->save();
  }

  /**
   * Returns the absolute path to the Drupal root.
   *
   * @return string
   *   The absolute path to the directory where Drupal is installed.
   */
  protected function absolute() {
    return realpath(getcwd());
  }

  /**
   * Returns the absolute directory path of the Tamper module.
   *
   * @return string
   *   The absolute path to the Tamper module.
   */
  protected function absolutePath() {
    return $this->absolute() . '/' . $this->getModulePath('tamper');
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

}
