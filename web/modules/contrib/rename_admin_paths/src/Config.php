<?php

declare(strict_types=1);

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
  private ConfigFactoryInterface $configFactory;

  /**
   * The module config in an editable state.
   *
   * @var \Drupal\Core\Config\Config
   */
  private CoreConfig $configEditable;

  /**
   * The module config in a non-editable state.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private ImmutableConfig $configImmutable;

  /**
   * Constructs config for the Rename Admin Paths module.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
    $this->configEditable = $this->configFactory->getEditable(
      self::CONFIG_KEY
    );
    $this->configImmutable = $this->configFactory->get(self::CONFIG_KEY);
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
    return (int) $this->configImmutable->get(sprintf('%s_path', $path)) === 1;
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
    return $this->configImmutable->get(sprintf('%s_path_value', $path));
  }

  /**
   * Sets a path to be overridden.
   *
   * @param string $path
   *   The path to override.
   * @param int $enabled
   *   Zero if disabled, one if enabled.
   */
  public function setPathEnabled(string $path, int $enabled): void {
    $this->configEditable->set(sprintf('%s_path', $path), $enabled);
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
    $this->configEditable->set(sprintf('%s_path_value', $path), $value);
  }

  /**
   * Saves the config.
   */
  public function save(): void {
    $this->configEditable->save();
  }

}
