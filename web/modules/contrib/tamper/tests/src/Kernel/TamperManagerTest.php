<?php

namespace Drupal\Tests\tamper\Kernel;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\tamper\TamperManager;
use Drupal\tamper_test\Plugin\Tamper\AttributeTamperPlugin;

/**
 * Tests config schema of each tamper plugin.
 *
 * @group tamper
 */
class TamperManagerTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['tamper', 'tamper_test'];

  /**
   * The Tamper plugin manager.
   */
  protected TamperManager $pluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Get a plugin manager for testing.
    $this->pluginManager = $this->container->get('plugin.manager.tamper');
  }

  /**
   * Tests if Tamper plugins defined with attributes can be found.
   */
  public function testFindAttributedPlugins() {
    if (!class_exists('\Drupal\Component\Plugin\Attribute\Plugin')) {
      // No need to execute test.
      $this->markTestSkipped('Attribute-plugins are not supported in Drupal 9.');
    }
    $definitions = $this->pluginManager->getDefinitions();

    $expected = [
      'id' => 'attribute_tamper',
      'label' => new TranslatableMarkup('Attribute Tamper plugin'),
      'description' => new TranslatableMarkup('Used for testing if this plugin is found by \\Drupal\\tamper\\TamperManager.'),
      'category' => new TranslatableMarkup('Other'),
      'handle_multiples' => TRUE,
      'itemUsage' => 'ignored',
      'provider' => 'tamper_test',
      'class' => AttributeTamperPlugin::class,
    ];
    $this->assertEquals($expected, $definitions['attribute_tamper']);
  }

}
