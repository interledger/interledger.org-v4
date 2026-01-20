<?php

namespace Drupal\Tests\tamper\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\tamper\Traits\TamperCommonTrait;
use Drupal\Tests\tamper\Traits\TamperPluginDefinitionTestTrait;
use Drupal\tamper\Plugin\Tamper\AbsoluteUrl;
use Drupal\tamper\Plugin\Tamper\EntityFinder;
use Drupal\tamper\Plugin\Tamper\FindReplaceRegex;
use Drupal\tamper\TamperBase;
use Drupal\tamper\TamperManagerInterface;

/**
 * Tests the Tamper plugins metadata like itemUsage.
 *
 * @group tamper
 */
class TamperPluginDefinitionTest extends KernelTestBase {

  use TamperCommonTrait;
  use TamperPluginDefinitionTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'entity_test',
    'tamper',
    'tamper_test',
    'user',
  ];

  /**
   * The Tamper plugin manager.
   */
  protected TamperManagerInterface $pluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test_bundle');
    $this->installEntitySchema('entity_test_with_bundle');
    $this->installEntitySchema('entity_test_no_bundle');

    $this->pluginManager = $this->container->get('plugin.manager.tamper');
  }

  /**
   * Tests if the tamper definitions look correct.
   */
  public function testDefinitions() {
    $this->assertTamperPluginDefinitions('tamper');
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
  protected function getExamplePluginData(string $plugin_id) {
    static $map = [
      'aggregate' => [1, 2, 2, 3, 4, 7, 9],
      'array_filter' => ['foo', 0, '', 'bar', FALSE, 'baz', [], 'zip'],
      'country_to_code' => 'netherlands',
      'hash' => NULL,
      'math' => 13,
      'number_format' => 1234.56,
      'timetodate' => 7,
      'unique' => ['foo', 'foo', 'bar', 'baz', 'baz'],
    ];

    if (array_key_exists($plugin_id, $map)) {
      return $map[$plugin_id];
    }
    return 'Foo';
  }

  /**
   * Returns required configuration per plugin.
   *
   * @param string $plugin_id
   *   The plugin to provide config for.
   *
   * @return mixed
   *   A value to pass to tamper().
   */
  protected function getExamplePluginConfig(string $plugin_id): array {
    static $configs = [
      'absolute_url' => [
        AbsoluteUrl::SETTING_SOURCE => 'base_url',
      ],
      'entity_finder' => [
        EntityFinder::SETTING_ENTITY_TYPE => 'entity_test_no_bundle',
        EntityFinder::SETTING_FIELD => 'name',
      ],
      'find_replace_regex' => [
        FindReplaceRegex::SETTING_FIND => '/cat/',
      ],
    ];

    return $configs[$plugin_id] ?? [];
  }

  /**
   * Checks plugins using getSource*() override getUsedSourceProperties().
   */
  public function testPluginsUsingSpecificSourcePropertiesOverrideGetUsedSourceProperties(): void {
    // Get the directory where the Tamper plugins live.
    $plugin_dir = $this->absolutePath() . '/src/Plugin/Tamper';
    $plugin_files = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($plugin_dir)
    );

    foreach ($plugin_files as $file) {
      if ($file->getExtension() !== 'php') {
        continue;
      }

      $code = file_get_contents($file->getPathname());

      // Check if this file contains a Tamper plugin class.
      if (!preg_match('/class\s+([A-Za-z0-9_]+)/', $code, $matches)) {
        continue;
      }
      $class_name = $matches[1];

      // Fully qualify the class.
      $namespace = '';
      if (preg_match('/namespace\s+([^;]+);/', $code, $ns_match)) {
        $namespace = trim($ns_match[1]);
      }
      $fqcn = $namespace . '\\' . $class_name;

      // Skip if it's not a subclass of TamperBase.
      if (!class_exists($fqcn) || !is_subclass_of($fqcn, TamperBase::class)) {
        continue;
      }

      // Look for specific source property usage in the tamper() method.
      // 1. Direct `$item->getSourceProperty()` calls.
      $uses_specific_properties =
        preg_match('/getSourceProperty\s*\(/', $code) ||

        // 2. `$item->getSource()[ 'key' ]` inline usage.
        preg_match('/getSource\s*\(\s*\)\s*[^;]*\[[\'"]([^\'"]+)/', $code) ||

        // 3. `$data = $item->getSource(); ... $data['key']` in same method.
        (preg_match('/(\$[A-Za-z_][A-Za-z0-9_]*)\s*=\s*\$item->getSource\s*\(\s*\)/', $code, $var_match)
          && preg_match('/' . preg_quote($var_match[1], '/') . '\s*\[[\'"]([^\'"]+)/', $code));

      if ($uses_specific_properties) {
        $reflection = new \ReflectionClass($fqcn);
        $this->assertTrue(
          $reflection->getMethod('getUsedSourceProperties')->class === $fqcn,
          sprintf('Plugin %s must override getUsedSourceProperties()', $fqcn)
        );
      }
    }

    // If we get here without a fail(), the test passes.
    $this->assertTrue(TRUE);
  }

}
