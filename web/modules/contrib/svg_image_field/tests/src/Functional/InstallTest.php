<?php

namespace Drupal\Tests\svg_image_field\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests module installation.
 *
 * @group svg_image_field
 */
class InstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'image',
  ];

  /**
   * The module handler used to check for installed modules.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The module installer.
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
   * Tests installing the svg_image_field module.
   */
  public function testInstallation() {
    $this->assertFalse($this->moduleHandler->moduleExists('svg_image_field'));
    $this->assertTrue($this->moduleInstaller->install(['svg_image_field']));
    $this->assertTrue($this->moduleHandler->moduleExists('svg_image_field'));
    $this->drupalGet('<front>');
  }

  /**
   * Tests installing the svg_image_field with the media module.
   */
  public function testInstallationWithMediaModule() {
    $this->assertFalse($this->moduleHandler->moduleExists('svg_image_field'));
    $this->assertTrue($this->moduleInstaller->install(['media', 'svg_image_field']));

    $this->rebuildContainer();
    $this->assertTrue($this->container->get('module_handler')->moduleExists('svg_image_field'));
    $this->drupalGet('<front>');
  }

}
