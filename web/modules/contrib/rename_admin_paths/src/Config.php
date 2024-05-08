<?php

namespace Drupal\rename_admin_paths;

use Drupal\Core\Config\Config as CoreConfig;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;

/**
 * Config for rename_admin_paths module.
 */
class Config {

  /**
   * Config storage key.
   */
  const CONFIG_KEY = 'rename_admin_paths.settings';

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * The module config in an editable state.
   *
   * @var \Drupal\Core\Config\Config
   */
  private $configEditable;

  /**
   * The module config in a non-editable state.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $configImmutable;

  /**
   * Constructs config for the Rename Admin Paths module.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * Returns the admin paths config in an editable state.
   *
   * @return \Drupal\Core\Config\Config
   *   The config in an editable state.
   */
  private function getEditableConfig(): CoreConfig {
    if (empty($this->configEditable)) {
      $this->configEditable = $this->configFactory->getEditable(
        self::CONFIG_KEY
      );
    }

    return $this->configEditable;
  }

  /**
   * Returns the admin paths config in a non-editable state.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The config in a non-editable state.
   */
  private function getImmutableConfig(): ImmutableConfig {
    if (empty($this->configImmutable)) {
      $this->configImmutable = $this->configFactory->get(self::CONFIG_KEY);
    }

    return $this->configImmutable;
  }

  /**
   * Checks whether a path is enabled.
   *
   * @param string $path
   *   The path to check.
   *
   * @return bool
   *   TRUE if the path is enabled.
   */
  public function isPathEnabled(string $path): bool {
    return !empty($this->getImmutableConfig()->get(sprintf('%s_path', $path)));
  }

  /**
   * Returns the value of a path.
   *
   * @param string $path
   *   The path to get the value of.
   *
   * @return string
   *   The value of the requested path.
   */
  public function getPathValue(string $path): string {
    return $this->getImmutableConfig()->get(sprintf('%s_path_value', $path));
  }

  /**
   * Sets a path to be overridden.
   *
   * @param string $path
   *   The path to override.
   * @param string $enabled
   *   The form value to use for the path (the new path).
   */
  public function setPathEnabled(string $path, string $enabled): void {
    $this->getEditableConfig()->set(sprintf('%s_path', $path), $enabled);
  }

  /**
   * Sets a path to a value.
   *
   * @param string $path
   *   The path to set.
   * @param string $value
   *   The value to set.
   */
  public function setPathValue(string $path, string $value): void {
    $this->getEditableConfig()->set(sprintf('%s_path_value', $path), $value);
  }

  /**
   * Saves the config.
   */
  public function save(): void {
    $this->getEditableConfig()->save();
  }

}
