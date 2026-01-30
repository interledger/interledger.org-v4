<?php

namespace Drupal\Tests\tamper\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\tamper\ItemUsage;
use Drupal\tamper\ItemUsageInterface;
use Drupal\tamper\ItemUsageTrait;

/**
 * @coversDefaultClass \Drupal\tamper\ItemUsageTrait
 * @group tamper
 */
class ItemUsageTraitTest extends UnitTestCase {

  /**
   * Tests the ItemUsageTrait methods.
   *
   * @param string|null $rawValue
   *   The raw value stored in the plugin definition.
   * @param string $expectedEffective
   *   The expected effective value.
   * @param bool $expectedRequires
   *   Whether requiresItem() should return TRUE.
   * @param bool $expectedUses
   *   Whether usesItem() should return TRUE.
   * @param bool $expectedIgnores
   *   Whether ignoresItem() should return TRUE.
   *
   * @covers ::getItemUsage
   * @covers ::getEffectiveItemUsage
   * @covers ::requiresItem
   * @covers ::usesItem
   * @covers ::ignoresItem
   *
   * @dataProvider providerItemUsageTrait
   */
  public function testItemUsageTrait(?string $rawValue, string $expectedEffective, bool $expectedRequires, bool $expectedUses, bool $expectedIgnores): void {
    $plugin = new DummyItemUsage($rawValue);

    $this->assertSame($rawValue, $plugin->getItemUsage());
    $this->assertSame($expectedEffective, $plugin->getEffectiveItemUsage());
    $this->assertSame($expectedRequires, $plugin->requiresItem());
    $this->assertSame($expectedUses, $plugin->usesItem());
    $this->assertSame($expectedIgnores, $plugin->ignoresItem());
  }

  /**
   * Data provider for ::testItemUsageTrait().
   *
   * Each case describes how the trait should behave for a given raw value.
   *
   * @return array[]
   *   An array of test cases. Each case contains:
   *   - rawValue: The raw value stored in the plugin definition.
   *   - expectedEffective: The expected effective value.
   *   - expectedRequires: Whether requiresItem() should return TRUE.
   *   - expectedUses: Whether usesItem() should return TRUE.
   *   - expectedIgnores: Whether ignoresItem() should return TRUE.
   */
  public static function providerItemUsageTrait(): array {
    return [
      'raw NULL, defaults to OPTIONAL' => [
        'rawValue' => NULL,
        'expectedEffective' => ItemUsage::OPTIONAL,
        'expectedRequires' => FALSE,
        'expectedUses' => TRUE,
        'expectedIgnores' => FALSE,
      ],
      'raw REQUIRED' => [
        'rawValue' => ItemUsage::REQUIRED,
        'expectedEffective' => ItemUsage::REQUIRED,
        'expectedRequires' => TRUE,
        'expectedUses' => TRUE,
        'expectedIgnores' => FALSE,
      ],
      'raw OPTIONAL' => [
        'rawValue' => ItemUsage::OPTIONAL,
        'expectedEffective' => ItemUsage::OPTIONAL,
        'expectedRequires' => FALSE,
        'expectedUses' => TRUE,
        'expectedIgnores' => FALSE,
      ],
      'raw IGNORED' => [
        'rawValue' => ItemUsage::IGNORED,
        'expectedEffective' => ItemUsage::IGNORED,
        'expectedRequires' => FALSE,
        'expectedUses' => FALSE,
        'expectedIgnores' => TRUE,
      ],
    ];
  }

}

/**
 * Dummy plugin implementing ItemUsageInterface.
 */
class DummyItemUsage implements ItemUsageInterface {

  use ItemUsageTrait;

  /**
   * The definition of the plugin.
   */
  protected array $pluginDefinition = [];

  /**
   * Constructs a new DummyItemUsage object.
   *
   * @param string|null $itemUsage
   *   The itemUsage value to set on the plugin definition.
   */
  public function __construct(?string $itemUsage) {
    $this->pluginDefinition['itemUsage'] = $itemUsage;
  }

}
