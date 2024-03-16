<?php

namespace Drupal\rename_admin_paths;

use Drupal\Core\Config\Config as CoreConfig;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;

/**
 * Config for rename_admin_paths module
 */
class Config {

  /**
   * Config storage key.
   */
  const CONFIG_KEY = 'rename_admin_paths.settings';

  /**
   * @var ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * @var CoreConfig
   */
  private $configEditable;

  /**
   * @var ImmutableConfig
   */
  private $configImmutable;

  /**
   * @param ConfigFactoryInterface $configFactory
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * @return CoreConfig
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
   * @return ImmutableConfig
   */
  private function getImmutableConfig(): ImmutableConfig {
    if (empty($this->configImmutable)) {
      $this->configImmutable = $this->configFactory->get(self::CONFIG_KEY);
    }

    return $this->configImmutable;
  }

  /**
   * @param string $path
   *
   * @return bool
   */
  public function isPathEnabled(string $path): bool {
    return !empty($this->getImmutableConfig()->get(sprintf('%s_path', $path)));
  }

  /**
   * @param string $path
   *
   * @return string
   */
  public function getPathValue(string $path): string {
    return $this->getImmutableConfig()->get(sprintf('%s_path_value', $path));
  }

  /**
   * @param string $path
   * @param string $enabled
   */
  public function setPathEnabled(string $path, string $enabled): void {
    $this->getEditableConfig()->set(sprintf('%s_path', $path), $enabled);
  }

  /**
   * @param string $path
   * @param string $value
   */
  public function setPathValue(string $path, string $value): void {
    $this->getEditableConfig()->set(sprintf('%s_path_value', $path), $value);
  }

  public function save(): void {
    $this->getEditableConfig()->save();
  }
}
