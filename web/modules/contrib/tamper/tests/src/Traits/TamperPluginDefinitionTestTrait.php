<?php

namespace Drupal\Tests\tamper\Traits;

use Drupal\tamper\Exception\SkipTamperDataException;
use Drupal\tamper\Exception\SkipTamperItemException;
use Drupal\tamper\ItemUsage;
use Drupal\tamper\SourceDefinitionInterface;
use Drupal\tamper_test\TamperableItemSpy;

/**
 * Provides reusable tests for Tamper plugin itemUsage definitions.
 *
 * This trait can be used in Kernel tests for tamper plugins in any module.
 * Example:
 * @code
 * class MyModuleTamperItemUsageTest extends KernelTestBase {
 *   use TamperItemUsageTestTrait;
 * }
 * @endcode
 */
trait TamperPluginDefinitionTestTrait {

  /**
   * Run the plugin definition tests.
   *
   * @param string|null $providerFilter
   *   Optional: limit tests to plugins from this provider/module.
   */
  protected function assertTamperPluginDefinitions(?string $providerFilter = NULL): void {
    /** @var \Drupal\tamper\Plugin\TamperManager $plugin_manager */
    $plugin_manager = $this->container->get('plugin.manager.tamper');

    foreach ($plugin_manager->getDefinitions() as $plugin_id => $definition) {
      // If requested, skip plugins from other modules.
      if ($providerFilter !== NULL && ($definition['provider'] ?? NULL) !== $providerFilter) {
        continue;
      }

      $this->assertArrayHasKey('itemUsage', $definition, sprintf(
        'Tamper plugin "%s" must define an "itemUsage" key in its definition.',
        $plugin_id
      ));

      $this->assertContains(
        $definition['itemUsage'],
        ItemUsage::cases(),
        sprintf(
          'Tamper plugin "%s" has invalid "itemUsage" value: %s',
          $plugin_id,
          var_export($definition['itemUsage'], TRUE)
        )
      );

      // Instantiate plugin with configuration.
      $plugin = $plugin_manager->createInstance($plugin_id, [
        'source_definition' => $this->getMockSourceDefinition(),
      ] + $this->getExamplePluginConfig($plugin_id));

      // Create a spy to detect if $item is actually used when calling tamper().
      $item_spy = new TamperableItemSpy();

      try {
        // Call the tamper method with test data and the item spy.
        $plugin->tamper($this->getExamplePluginData($plugin_id), $item_spy);
      }
      catch (SkipTamperDataException $e) {
        // Not an error.
      }
      catch (SkipTamperItemException $e) {
        // Not an error.
      }
      catch (\Throwable $e) {
        // Plugins might expect specific data types, so catch exceptions and
        // skip these.
        $this->markTestIncomplete("Plugin $plugin_id threw exception on tamper(): " . $e->getMessage());
        continue;
      }

      // If itemUsage is 'ignored', the spy should report no usage.
      if ($definition['itemUsage'] === 'ignored') {
        $this->assertFalse(
          $item_spy->wasUsed(),
          "Plugin $plugin_id declares 'ignored' but used the tamperable item."
        );
      }

      // If itemUsage is 'required', spy should report usage.
      if ($definition['itemUsage'] === 'required') {
        $this->assertTrue(
          $item_spy->wasUsed(),
          "Plugin $plugin_id declares 'required' but did not use the tamperable item."
        );
      }
    }
  }

  /**
   * Returns a value to pass to the Tamper plugin.
   *
   * @param string $plugin_id
   *   The plugin to check.
   *
   * @return mixed
   *   A value to pass to tamper().
   */
  abstract protected function getExamplePluginData(string $plugin_id);

  /**
   * Returns required configuration per plugin.
   *
   * @param string $plugin_id
   *   The plugin to provide config for.
   *
   * @return mixed
   *   Plugin configuration.
   */
  abstract protected function getExamplePluginConfig(string $plugin_id): array;

  /**
   * Returns a mocked source definition.
   *
   * @return \Drupal\tamper\SourceDefinitionInterface
   *   A source definition.
   */
  protected function getMockSourceDefinition() {
    $mock = $this->createMock(SourceDefinitionInterface::class);
    $mock->expects($this->any())
      ->method('getList')
      ->willReturn(['foo', 'bar']);
    return $mock;
  }

}
