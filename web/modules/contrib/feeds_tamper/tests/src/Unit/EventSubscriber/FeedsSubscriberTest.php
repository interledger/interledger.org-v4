<?php

namespace Drupal\Tests\feeds_tamper\Unit\EventSubscriber;

use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\feeds_tamper\Unit\FeedsTamperTestCase;
use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\Feeds\Item\DynamicItem;
use Drupal\feeds\Feeds\Item\ValidatableItemInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\StateInterface;
use Drupal\feeds_tamper\Adapter\TamperableFeedItemAdapter;
use Drupal\feeds_tamper\EventSubscriber\FeedsSubscriber;
use Drupal\feeds_tamper\FeedTypeTamperManagerInterface;
use Drupal\feeds_tamper\FeedTypeTamperMetaInterface;
use Drupal\tamper\Exception\SkipTamperDataException;
use Drupal\tamper\Exception\SkipTamperItemException;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperInterface;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\feeds_tamper\EventSubscriber\FeedsSubscriber
 * @group feeds_tamper
 */
class FeedsSubscriberTest extends FeedsTamperTestCase {

  /**
   * The subscriber under test.
   *
   * @var \Drupal\feeds_tamper\EventSubscriber\FeedsSubscriber
   */
  protected $subscriber;

  /**
   * The feed.
   *
   * @var \Drupal\feeds\FeedInterface
   */
  protected $feed;

  /**
   * The parse event.
   *
   * @var \Drupal\feeds\Event\ParseEvent
   */
  protected $event;

  /**
   * The tamper meta.
   *
   * @var \Drupal\feeds_tamper\FeedTypeTamperMetaInterface
   */
  protected $tamperMeta;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create a feed.
    $this->feed = $this->getMockFeed();

    // Create parse event.
    $this->event = new ParseEvent($this->feed, $this->createMock(FetcherResultInterface::class));
    $this->event->setParserResult(new ParserResult());

    // Create tamper meta.
    $this->tamperMeta = $this->createMock(FeedTypeTamperMetaInterface::class);

    // Create feed type tamper manager.
    $tamper_manager = $this->createMock(FeedTypeTamperManagerInterface::class);
    $tamper_manager->expects($this->any())
      ->method('getTamperMeta')
      ->willReturn($this->tamperMeta);

    // And finally, create the subscriber to test.
    $this->subscriber = new FeedsSubscriber($tamper_manager);
    $this->subscriber->setStringTranslation($this->createMock(TranslationInterface::class));
  }

  /**
   * Creates a tamper mock with a return value for the tamper() method.
   *
   * @param mixed $return_value
   *   (optional) The value that the tamper plugin must return when tamper()
   *   gets called on it.
   *
   * @return \Drupal\tamper\TamperInterface
   *   A mocked tamper plugin.
   */
  protected function createTamperMock($return_value = NULL) {
    $tamper = $this->createMock(TamperInterface::class);
    $tamper->expects($this->any())
      ->method('tamper')
      ->willReturn($return_value);

    return $tamper;
  }

  /**
   * @covers ::afterParse
   * @covers ::alterItem
   */
  public function testAfterParse() {
    $tamper = $this->createMock(TamperInterface::class);
    $tamper->expects($this->any())
      ->method('tamper')
      ->willReturn('Foo');

    $this->tamperMeta->expects($this->once())
      ->method('getTampersGroupedBySource')
      ->willReturn([
        'alpha' => [
          $this->createTamperMock('Foo'),
        ],
      ]);

    // Add an item to the parser result.
    $item = new DynamicItem();
    $item->set('alpha', 'Bar');
    $this->event->getParserResult()->addItem($item);

    $this->subscriber->afterParse($this->event);
    $this->assertEquals('Foo', $item->get('alpha'));
  }

  /**
   * @covers ::afterParse
   */
  public function testAfterParseWithNoItems() {
    $this->tamperMeta->expects($this->once())
      ->method('getTampersGroupedBySource')
      ->willReturn([
        'alpha' => [
          $this->createTamperMock('Foo'),
        ],
      ]);

    $this->subscriber->afterParse($this->event);
  }

  /**
   * @covers ::afterParse
   */
  public function testAfterParseWithEmptyArray() {
    $tamper = $this->createMock(TamperInterface::class);
    $tamper->expects($this->any())
      ->method('tamper')
      ->willReturn('Foo');

    $this->tamperMeta->expects($this->once())
      ->method('getTampersGroupedBySource')
      ->willReturn([
        'alpha' => [
          $this->createTamperMock('Foo'),
        ],
      ]);

    // Add an item to the parser result.
    $item = new DynamicItem();
    $item->set('alpha', []);
    $this->event->getParserResult()->addItem($item);

    $this->subscriber->afterParse($this->event);
    $this->assertEquals('Foo', $item->get('alpha'));
  }

  /**
   * @covers ::afterParse
   * @covers ::alterItem
   */
  public function testAfterParseWithNoTampers() {
    $this->tamperMeta->expects($this->once())
      ->method('getTampersGroupedBySource')
      ->willReturn([]);

    // Add an item to the parser result.
    $item = new DynamicItem();
    $item->set('alpha', 'Bar');
    $this->event->getParserResult()->addItem($item);

    // Run event callback.
    $this->subscriber->afterParse($this->event);
    $this->assertEquals('Bar', $item->get('alpha'));
  }

  /**
   * @covers ::afterParse
   * @covers ::alterItem
   */
  public function testAfterParseWithMultiValueTampers() {
    // Create a tamper that turns an input value into an array.
    $tamper1 = $this->prophesize(TamperInterface::class);
    $tamper1->tamper('Bar', Argument::type(TamperableFeedItemAdapter::class))
      ->willReturn(['Bar', 'Bar']);
    $tamper1->getPluginDefinition()->willReturn([
      'handle_multiples' => FALSE,
    ]);
    $tamper1->multiple()->willReturn(TRUE);
    $tamper1 = $tamper1->reveal();

    // Create a tamper that returns 'Foo'.
    $tamper2 = $this->prophesize(TamperInterface::class);
    $tamper2->tamper('Bar', Argument::type(TamperableFeedItemAdapter::class))
      ->willReturn('Foo');
    $tamper2->getPluginDefinition()->willReturn([
      'handle_multiples' => FALSE,
    ]);
    $tamper2->multiple()->willReturn(FALSE);
    $tamper2 = $tamper2->reveal();

    // Create a tamper that returns 'FooFoo'.
    $tamper3 = $this->prophesize(TamperInterface::class);
    $tamper3->tamper('Foo', Argument::type(TamperableFeedItemAdapter::class))
      ->willReturn('FooFoo');
    $tamper3->getPluginDefinition()->willReturn([
      'handle_multiples' => FALSE,
    ]);
    $tamper3->multiple()->willReturn(FALSE);
    $tamper3 = $tamper3->reveal();

    $this->tamperMeta->expects($this->once())
      ->method('getTampersGroupedBySource')
      ->willReturn([
        'alpha' => [$tamper1, $tamper2, $tamper3],
      ]);

    // Add an item to the parser result.
    $item = new DynamicItem();
    $item->set('alpha', 'Bar');
    $this->event->getParserResult()->addItem($item);

    // Run event callback.
    $this->subscriber->afterParse($this->event);
    $this->assertEquals(['FooFoo', 'FooFoo'], $item->get('alpha'));
  }

  /**
   * @covers ::afterParse
   * @covers ::alterItem
   */
  public function testAfterParseWithTamperItem() {
    // Create a tamper plugin that manipulates the whole item.
    $tamper = $this->createMock(TamperInterface::class);
    $tamper->expects($this->once())
      ->method('tamper')
      ->willReturnCallback([$this, 'callbackWithTamperItem']);

    $this->tamperMeta->expects($this->once())
      ->method('getTampersGroupedBySource')
      ->willReturn([
        'alpha' => [$tamper],
      ]);

    // Add an item to the parser result.
    $item = new DynamicItem();
    $item->set('alpha', 'Foo');
    $item->set('beta', 'Bar');
    $item->set('gamma', 'Qux');
    $this->event->getParserResult()->addItem($item);

    // Run event callback.
    $this->subscriber->afterParse($this->event);
    $this->assertEquals('Fooing', $item->get('alpha'));
    $this->assertEquals('Baring', $item->get('beta'));
    $this->assertEquals('Quxing', $item->get('gamma'));
  }

  /**
   * Callback for testAfterParseWithTamperItem().
   */
  public function callbackWithTamperItem($data, TamperableFeedItemAdapter $item) {
    // Add "ing" to each property.
    foreach ($item->getSource() as $key => $value) {
      $item->setSourceProperty($key, $value . 'ing');
    }

    // Make sure that "ing" is also added to the field that is being tampered.
    return $data . 'ing';
  }

  /**
   * @covers ::afterParse
   * @covers ::alterItem
   */
  public function testAfterParseWithSkippingItem() {
    // Create a tamper plugin that will throw a SkipTamperItemException for some
    // values.
    $tamper = $this->createMock(TamperInterface::class);
    $tamper->expects($this->exactly(2))
      ->method('tamper')
      ->willReturnCallback([$this, 'callbackSkipItem']);

    $this->tamperMeta->expects($this->once())
      ->method('getTampersGroupedBySource')
      ->willReturn([
        'alpha' => [$tamper],
      ]);

    // Create three items. The first item should get removed.
    $item1 = new DynamicItem();
    $item1->set('alpha', 'Foo');
    $this->event->getParserResult()->addItem($item1);
    $item2 = new DynamicItem();
    $item2->set('alpha', 'Bar');
    $this->event->getParserResult()->addItem($item2);

    $this->subscriber->afterParse($this->event);

    // Assert that only item 2 still exists.
    $this->assertEquals(1, $this->event->getParserResult()->count());
    $this->assertSame($item2, $this->event->getParserResult()->offsetGet(0));
  }

  /**
   * Callback for testAfterParseWithSkippingItem().
   */
  public function callbackSkipItem($data, TamperableFeedItemAdapter $item) {
    if ($data == 'Foo') {
      throw new SkipTamperItemException();
    }
  }

  /**
   * @covers ::afterParse
   * @covers ::alterItem
   */
  public function testAfterParseWithSkippingData() {
    // Create a tamper plugin that will throw a SkipTamperDataException for some
    // values.
    $tamper1 = $this->createMock(TamperInterface::class);
    $tamper1->expects($this->exactly(2))
      ->method('tamper')
      ->willReturnCallback([$this, 'callbackSkipData']);

    // Create a second tamper plugin that will just set the value to 'Qux'.
    $tamper2 = $this->createMock(TamperInterface::class);
    $tamper2->expects($this->once())
      ->method('tamper')
      ->willReturn('Qux');

    // Create a third tamper plugin that operates on the 'beta' field, to ensure
    // skipping on the 'alpha' field does not skip the 'beta' field.
    $tamper3 = $this->createMock(TamperInterface::class);
    $tamper3->expects($this->exactly(2))
      ->method('tamper')
      ->willReturn('Baz');

    $this->tamperMeta->expects($this->once())
      ->method('getTampersGroupedBySource')
      ->willReturn([
        'alpha' => [$tamper1, $tamper2],
        'beta' => [$tamper3],
      ]);

    // Create two items. The first item should get the value unset.
    $item1 = new DynamicItem();
    $item1->set('alpha', 'Foo');
    $item1->set('beta', 'Foo');
    $this->event->getParserResult()->addItem($item1);
    $item2 = new DynamicItem();
    $item2->set('alpha', 'Bar');
    $item2->set('beta', 'Bar');
    $this->event->getParserResult()->addItem($item2);

    $this->subscriber->afterParse($this->event);

    // Assert that 2 items still exist.
    $this->assertEquals(2, $this->event->getParserResult()->count());
    // And assert that item 1 no longer has an alpha value.
    $this->assertNull($item1->get('alpha'));
    // Assert other values.
    $this->assertEquals($item1->get('beta'), 'Baz');
    $this->assertEquals($item2->get('alpha'), 'Qux');
    $this->assertEquals($item2->get('beta'), 'Baz');
  }

  /**
   * Callback for testAfterParseWithSkippingData().
   */
  public function callbackSkipData($data, TamperableFeedItemAdapter $item) {
    if ($data == 'Foo') {
      throw new SkipTamperDataException();
    }
  }

  /**
   * Tests that the tamperable item is updated after each applied tamper.
   *
   * Some Tamper plugins, like the Rewrite plugin and the Copy plugin operate
   * mostly on the tamperable item, and less on the passed value. If these
   * plugins are used in a chain, we need to make sure that they have the most
   * up to date item.
   */
  public function testValueSavingToItem() {
    // Create a Tamper plugin that performs a simple calculation.
    $tamper1 = $this->createMock(TamperInterface::class);
    $tamper1->expects($this->once())
      ->method('tamper')
      ->willReturnCallback([$this, 'callbackMath']);

    // Create a Tamper plugin that concatenates two source values by
    // accessing the tamperable item directly.
    $tamper2 = $this->createMock(TamperInterface::class);
    $tamper2->expects($this->once())
      ->method('tamper')
      ->willReturnCallback([$this, 'callbackConcatenate']);

    $this->tamperMeta->expects($this->once())
      ->method('getTampersGroupedBySource')
      ->willReturn([
        'alpha' => [$tamper1, $tamper2],
      ]);

    // Add an item to the parser result.
    $item = new DynamicItem();
    $item->set('alpha', 314);
    $item->set('beta', 'Bar');
    $item->set('gamma', 'Qux');
    $this->event->getParserResult()->addItem($item);

    // Run event callback.
    $this->subscriber->afterParse($this->event);
    $this->assertEquals('At Bar we have 315 Qux.', $item->get('alpha'));
  }

  /**
   * Callback for testValueSavingToItem().
   */
  public function callbackMath($data, TamperableFeedItemAdapter $item) {
    return $data + 1;
  }

  /**
   * Callback for testValueSavingToItem().
   */
  public function callbackConcatenate($data, TamperableFeedItemAdapter $item) {
    return strtr('At @beta we have @alpha @gamma.', [
      '@alpha' => $item->getSourceProperty('alpha'),
      '@beta' => $item->getSourceProperty('beta'),
      '@gamma' => $item->getSourceProperty('gamma'),
    ]);
  }

  /**
   * Tests the catching of tamper exceptions.
   */
  public function testTamperExceptionCatching() {
    // Create a tamper plugin that adds a '1' at the end of a string.
    $tamper1 = $this->createMock(TamperInterface::class);
    $tamper1->expects($this->exactly(4))
      ->method('tamper')
      ->willReturnCallback([$this, 'callbackTamperAddValue']);

    // Create a tamper plugin that will throw a TamperException for some
    // values. For this test that is when the value passed to the Tamper plugin
    // is 'Foo1'.
    $tamper2 = $this->createMock(TamperInterface::class);
    $tamper2->expects($this->exactly(4))
      ->method('tamper')
      ->willReturnCallback([$this, 'callbackTamperException']);

    // Create a tamper plugin that sets the value to 'Baz'.
    $tamper3 = $this->createMock(TamperInterface::class);
    $tamper3->expects($this->once())
      ->method('tamper')
      ->willReturn('Baz');

    // Apply all tampers to source 'alpha' and only the first two tampers to
    // source 'beta'. Note: normally tampers are applied to only one source at a
    // time, but in this test we want to check both the result of all tampers
    // combined and the result of only tampers 1 and 2 combined.
    $this->tamperMeta->expects($this->once())
      ->method('getTampersGroupedBySource')
      ->willReturn([
        'alpha' => [$tamper1, $tamper2, $tamper3],
        'beta' => [$tamper1, $tamper2],
      ]);

    // Since one tamper will fail to apply, a message is expected to be set on
    // the feeds state.
    $state = $this->createMock(StateInterface::class);
    $state->expects($this->once())
      ->method('setMessage');

    $this->feed->expects($this->once())
      ->method('getState')
      ->with(StateInterface::PARSE)
      ->willReturn($state);

    // Create two items. On the first item, the source 'alpha' has the value
    // 'Foo'. Tamper 1 will change that into 'Foo1'. Tamper 2 will throw a
    // TamperException because of that.
    $item1 = new DynamicItem();
    $item1->set('alpha', 'Foo');
    $item1->set('beta', 'Bar');
    $this->event->getParserResult()->addItem($item1);
    $item2 = new DynamicItem();
    $item2->set('alpha', 'Bar');
    $item2->set('beta', 'Qux');
    $this->event->getParserResult()->addItem($item2);

    $this->subscriber->afterParse($this->event);

    // Assert that 2 items still exist.
    $this->assertEquals(2, $this->event->getParserResult()->count());
    // And assert expected values.
    $this->assertEquals('Bar1', $item1->get('beta'));
    $this->assertEquals('Baz', $item2->get('alpha'));
    $this->assertEquals('Qux1', $item2->get('beta'));
  }

  /**
   * Tests that an item gets marked as invalid upon an exception.
   */
  public function testMarkItemInvalidUponException() {
    if (!interface_exists(ValidatableItemInterface::class)) {
      require_once __DIR__ . '/../../../stubs/ValidatableItemInterface.php';
    }

    // Create an item that can be validated.
    $item = $this->prophesize(ValidatableItemInterface::class);
    $item->get('alpha')
      ->willReturn('Foo1');
    $item->markInvalid('Applying tamper "qux" on source "alpha" failed with the error "Invalid data".')
      ->willReturn($item)
      ->shouldBeCalled();

    // Create a tamper plugin that will throw a TamperException for some values.
    $tamper = $this->createMock(TamperInterface::class);
    $tamper->expects($this->once())
      ->method('tamper')
      ->willReturnCallback([$this, 'callbackTamperException']);
    $tamper->expects($this->once())
      ->method('getPluginId')
      ->willReturn('qux');

    $this->tamperMeta->expects($this->once())
      ->method('getTampersGroupedBySource')
      ->willReturn([
        'alpha' => [$tamper],
      ]);

    // Since the tamper will fail to apply, a message is expected to be set on
    // the feeds state.
    $state = $this->createMock(StateInterface::class);
    $state->expects($this->once())
      ->method('setMessage');

    $this->feed->expects($this->once())
      ->method('getState')
      ->with(StateInterface::PARSE)
      ->willReturn($state);

    $this->event->getParserResult()->addItem($item->reveal());
    $this->subscriber->afterParse($this->event);
  }

  /**
   * Tests the message that is set when an item gets marked as invalid.
   *
   * @param string $message
   *   The expected error message.
   * @param mixed $label
   *   The tamper label.
   *
   * @dataProvider tamperLabelProvider
   */
  public function testMessageForInvalidItems(string $message, $label) {
    if (!interface_exists(ValidatableItemInterface::class)) {
      require_once __DIR__ . '/../../../stubs/ValidatableItemInterface.php';
    }

    // Create an item that can be validated.
    $item = $this->prophesize(ValidatableItemInterface::class);
    $item->get('alpha')
      ->willReturn('Foo1');

    // Set the expected message when the item gets marked as invalid.
    $item->markInvalid($message)
      ->willReturn($item)
      ->shouldBeCalled();

    // Create a tamper plugin that will throw a TamperException for some values.
    $tamper = $this->prophesize(TamperInterface::class);
    $tamper->tamper('Foo1', Argument::type(TamperableFeedItemAdapter::class))
      ->willThrow(new TamperException('Invalid data'));
    $tamper->getPluginId()
      ->willReturn('qux');
    $tamper->getSetting('label')
      ->willReturn($label);
    $tamper->getPluginDefinition()->willReturn(['id' => 'foo']);

    $this->tamperMeta->expects($this->once())
      ->method('getTampersGroupedBySource')
      ->willReturn([
        'alpha' => [$tamper->reveal()],
      ]);

    $state = $this->createMock(StateInterface::class);
    $state->expects($this->once())
      ->method('setMessage');
    $this->feed->expects($this->once())
      ->method('getState')
      ->with(StateInterface::PARSE)
      ->willReturn($state);

    $this->event->getParserResult()->addItem($item->reveal());
    $this->subscriber->afterParse($this->event);
  }

  /**
   * Data provider for testMessageForInvalidItems().
   */
  public static function tamperLabelProvider(): array {
    return [
      [
        'message' => 'Applying tamper "qux" on source "alpha" failed with the error "Invalid data".',
        'label' => NULL,
      ],
      [
        'message' => 'Applying tamper "qux" on source "alpha" failed with the error "Invalid data".',
        'label' => '',
      ],
      [
        'message' => 'Applying tamper "My Tamper" on source "alpha" failed with the error "Invalid data".',
        'label' => 'My Tamper',
      ],
      [
        'message' => 'Applying tamper "0" on source "alpha" failed with the error "Invalid data".',
        'label' => '0',
      ],
    ];
  }

  /**
   * Callback for testTamperExceptionCatching().
   *
   * Adds a '1' to the data.
   *
   * @return string
   *   The tampered data.
   */
  public function callbackTamperAddValue($data, TamperableFeedItemAdapter $item) {
    return $data . '1';
  }

  /**
   * Callback for testTamperExceptionCatching().
   *
   * @throws \Drupal\tamper\TamperException
   *   In case the data is 'Foo1'.
   */
  public function callbackTamperException($data, TamperableFeedItemAdapter $item) {
    if ($data == 'Foo1') {
      throw new TamperException('Invalid data');
    }
    return $data;
  }

}
