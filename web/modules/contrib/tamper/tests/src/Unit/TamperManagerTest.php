<?php

namespace Drupal\Tests\tamper\Unit;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\tamper\Plugin\Tamper\Copy;
use Drupal\tamper\TamperManager;

/**
 * @coversDefaultClass \Drupal\tamper\TamperManager
 * @group tamper
 */
class TamperManagerTest extends UnitTestCase {

  /**
   * The Tamper plugin manager.
   *
   * @var \Drupal\tamper\TamperManager
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Get a plugin manager for testing.
    $namespaces = new \ArrayObject();
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $cache_backend = $this->createMock(CacheBackendInterface::class);
    $this->pluginManager = new TamperManager($namespaces, $cache_backend, $module_handler);
  }

  /**
   * Tests that a plugin with a correct itemUsage value parses correctly.
   */
  public function testProcessDefinitionWithCorrectItemUsage() {
    $expected = $definition = [
      'id' => 'copy',
      'label' => 'Copy',
      'description' => 'Copy value from one source to another.',
      'category' => 'Other',
      'itemUsage' => 'required',
      'class' => Copy::class,
      'provider' => 'tamper',
    ];
    $this->pluginManager->processDefinition($definition, 'copy');
    $this->assertEquals($expected, $definition);
  }

  /**
   * Tests that a plugin gets a value for itemUsage.
   */
  public function testProcessDefinitionWithUnspecifiedItemUsage() {
    $definition = [
      'id' => 'copy',
      'label' => 'Copy',
      'description' => 'Copy value from one source to another.',
      'category' => 'Other',
      'class' => Copy::class,
      'provider' => 'tamper',
    ];
    $expected = $definition + [
      'itemUsage' => NULL,
    ];
    $this->pluginManager->processDefinition($definition, 'copy');
    $this->assertEquals($expected, $definition);
  }

  /**
   * Tests that an exception is thrown for an invalid itemUsage value.
   */
  public function testProcessDefinitionWithInvalidItemUsage() {
    $definition = [
      'id' => 'dummy',
      'label' => 'Dummy',
      'description' => 'Copy value from one source to another.',
      'category' => 'Other',
      'itemUsage' => 'illegal_value',
      'class' => Copy::class,
      'provider' => 'tamper',
    ];
    $this->expectException(PluginException::class);
    $this->expectExceptionMessage('Plugin "dummy" has invalid itemUsage "illegal_value". Allowed: required, optional, ignored.');
    $this->pluginManager->processDefinition($definition, 'dummy');
  }

}
