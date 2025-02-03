<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Item;

use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;
use Drupal\feeds\Feeds\Item\ItemInterface;

/**
 * Base class for item tests.
 */
abstract class ItemTestBase extends FeedsUnitTestCase {

  /**
   * The item under test.
   *
   * @var \Drupal\feeds\Feeds\Item\ItemInterface
   */
  protected $item;

  /**
   * Tests if the item is implementing the expected interface.
   */
  public function testImplementingInterface() {
    $this->assertInstanceOf(ItemInterface::class, $this->item);
  }

  /**
   * Tests the set() and get() methods.
   */
  public function testSetAndGet() {
    $this->assertSame($this->item, $this->item->set('field', 'value'));
    $this->assertSame('value', $this->item->get('field'));
  }

  /**
   * Tests the toArray() method.
   */
  public function testToArray() {
    $this->item->set('field', 'value');
    $this->item->set('field2', 'value2');

    $expected = [
      'field' => 'value',
      'field2' => 'value2',
    ];
    $this->assertEquals($expected, $this->item->toArray());
  }

  /**
   * Tests the fromArray() and get() methods.
   */
  public function testFromArray() {
    $this->assertSame($this->item, $this->item->fromArray([
      'Foo' => 'Bar',
      'Baz' => 'Qux',
    ]));

    $this->assertSame('Bar', $this->item->get('Foo'));
    $this->assertSame('Qux', $this->item->get('Baz'));
    $this->assertNull($this->item->get('Bar'));
    $this->assertNull($this->item->get('Qux'));
  }

}
