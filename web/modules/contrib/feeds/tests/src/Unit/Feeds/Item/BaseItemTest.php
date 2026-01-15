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
   * @covers ::get
   */
  public function testGetDataFieldWhenNotSet() {
    $this->assertNull($this->item->get('data'));
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
   * @covers ::set
   */
  public function testSetDataField() {
    $this->item->set('data', 'bar');
    $this->assertSame($this->item, $this->item->set('data', 'bar'));
  }

  /**
   * @covers ::toArray
   */
  public function testToArray() {
    $this->item->set('foo', 'bar');
    $this->item->set('baz', 'qux');

    $expected = [
      'title' => NULL,
      'foo' => 'bar',
      'baz' => 'qux',
    ];
    $this->assertSame($expected, $this->item->toArray());
  }

  /**
   * @covers ::toArray
   */
  public function testToArrayWithTitleSet() {
    $this->item->set('foo', 'bar');
    $this->item->set('baz', 'qux');
    $this->item->set('title', 'Lorem ipsum');

    $expected = [
      'title' => 'Lorem ipsum',
      'foo' => 'bar',
      'baz' => 'qux',
    ];
    $this->assertSame($expected, $this->item->toArray());
  }

  /**
   * @covers ::toArray
   */
  public function testToArrayWithDataField() {
    $this->item->set('foo', 'bar');
    $this->item->set('baz', 'qux');
    $this->item->set('data', 'bar');

    $expected = [
      'title' => NULL,
      'foo' => 'bar',
      'baz' => 'qux',
      'data' => 'bar',
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

  /**
   * @covers ::fromArray
   */
  public function testFromArrayWithTitle() {
    $data = [
      'bar' => 'foo',
      'lorem' => 'ipsum',
      'title' => 'Lorem Ipsum',
    ];
    $this->assertSame($this->item, $this->item->fromArray($data));
    $this->assertSame('foo', $this->item->get('bar'));
    $this->assertSame('ipsum', $this->item->get('lorem'));
    $this->assertSame('Lorem Ipsum', $this->item->get('title'));
  }

  /**
   * @covers ::fromArray
   */
  public function testFromWithDataField() {
    $data = [
      'bar' => 'foo',
      'lorem' => 'ipsum',
      'data' => ['some' => 'thing'],
    ];
    $this->assertSame($this->item, $this->item->fromArray($data));
    $this->assertSame('foo', $this->item->get('bar'));
    $this->assertSame('ipsum', $this->item->get('lorem'));
    $this->assertSame(['some' => 'thing'], $this->item->get('data'));
  }

  /**
   * @covers ::fromArray
   */
  public function testFromArrayWithEmptyArray() {
    $this->assertSame($this->item, $this->item->fromArray([]));

    $expected = [
      'title' => NULL,
    ];
    $this->assertSame($expected, $this->item->toArray());
  }

}

/**
 * For testing methods from BaseItem.
 *
 * Abstract classes cannot be mocked.
 */
class ItemMock extends BaseItem {

  /**
   * The title of the item.
   *
   * @var string
   */
  protected $title;

}
