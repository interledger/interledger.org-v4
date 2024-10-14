<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\tamper\SourceDefinitionInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Base class for tamper plugin tests.
 */
abstract class TamperPluginTestBase extends UnitTestCase {

  /**
   * The tamper plugin under test.
   *
   * @var \Drupal\tamper\TamperInterface
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->plugin = $this->instantiatePlugin();
    $this->plugin->setStringTranslation($this->createMock(TranslationInterface::class));

    parent::setUp();
  }

  /**
   * Instantiates a plugin.
   *
   * @return \Drupal\tamper\TamperInterface
   *   A tamper plugin.
   */
  abstract protected function instantiatePlugin();

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

  /**
   * Covers ::getPluginId().
   */
  public function testGetPluginId() {
    $this->assertIsString($this->plugin->getPluginId());
  }

  /**
   * Covers ::getPluginDefinition().
   */
  public function testGetPluginDefinition() {
    $this->assertIsArray($this->plugin->getPluginDefinition());
  }

  /**
   * Covers ::getConfiguration().
   */
  public function testGetConfiguration() {
    $this->assertIsArray($this->plugin->getConfiguration());
  }

  /**
   * Covers ::defaultConfiguration().
   */
  public function testDefaultConfiguration() {
    $this->assertIsArray($this->plugin->defaultConfiguration());
  }

  /**
   * Covers ::buildConfigurationForm().
   */
  public function testBuildConfigurationForm() {
    $this->assertIsArray($this->plugin->buildConfigurationForm([], $this->createMock(FormStateInterface::class)));
  }

  /**
   * Covers ::multiple().
   */
  public function testMultiple() {
    $this->assertIsBool($this->plugin->multiple());
  }

}
