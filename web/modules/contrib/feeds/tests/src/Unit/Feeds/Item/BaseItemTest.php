<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Item;

use Drupal\feeds\Feeds\Item\BaseItem;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Item\BaseItem
 * @group feeds
 */
class BaseItemTest extends ItemTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->item = new ItemMock();
  }

  /**
   * @covers ::get
   */
  public function testGetForNonExistingField() {
    $this->assertNull($this->item->get('non_existent'));
  }

  /**
   * @covers ::set
   * @covers ::get
   */
  public function testSetAndGet() {
    $this->assertSame($this->item, $this->item->set('foo', 'bar'));
    $this->assertSame('bar', $this->item->get('foo'));
  }

  /**
   * @covers ::toArray
   */
  public function testToArray() {
    $this->item->set('foo', 'bar');
    $this->item->set('baz', 'qux');

    $expected = [
      'foo' => 'bar',
      'baz' => 'qux',
    ];
    $this->assertSame($expected, $this->item->toArray());
  }

  /**
   * @covers ::fromArray
   */
  public function testFromArray() {
    $data = [
      'bar' => 'foo',
      'lorem' => 'ipsum',
    ];
    $this->assertSame($this->item, $this->item->fromArray($data));
    $this->assertSame('foo', $this->item->get('bar'));
    $this->assertSame('ipsum', $this->item->get('lorem'));
  }

}

/**
 * For testing methods from BaseItem.
 *
 * Abstract classes cannot be mocked.
 */
class ItemMock extends BaseItem {}
