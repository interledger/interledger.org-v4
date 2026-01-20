<?php

namespace Drupal\Tests\feeds\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests module installation.
 *
 * @group feeds
 */
class FeedsInstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'options',
  ];

  /**
   * Module handler to ensure installed modules.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->moduleHandler = $this->container->get('module_handler');
    $this->moduleInstaller = $this->container->get('module_installer');
  }

  /**
   * Tests module is installable.
   */
  public function testInstallation() {
    $this->assertFalse($this->moduleHandler->moduleExists('feeds'));
    $this->assertTrue($this->moduleInstaller->install(['feeds']));
    $this->assertTrue($this->moduleHandler->moduleExists('feeds'));
  }

  /**
   * Tests module is installable with views.
   */
  public function testInstallationWithViews() {
    $this->assertFalse($this->moduleHandler->moduleExists('views'));
    $this->assertFalse($this->moduleHandler->moduleExists('feeds'));
    $this->assertTrue($this->moduleInstaller->install(['views', 'feeds']));

    $this->rebuildContainer();
    $this->moduleHandler = $this->container->get('module_handler');

    $this->assertTrue($this->moduleHandler->moduleExists('views'));
    $this->assertTrue($this->moduleHandler->moduleExists('feeds'));
  }

}
