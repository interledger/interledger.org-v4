<?php

namespace Drupal\Tests\feeds\Unit;

use Drupal\Core\Database\Connection;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\FeedClearHandler;
use Drupal\feeds\StateInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @coversDefaultClass \Drupal\feeds\FeedClearHandler
 * @group feeds
 */
class FeedClearHandlerTest extends FeedsUnitTestCase {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $dispatcher;

  /**
   * The feed entity.
   *
   * @var \Drupal\feeds\FeedInterface
   */
  protected $feed;

  /**
   * Status of the batch.
   *
   * @var array
   */
  protected $context;

  /**
   * The handler to test.
   *
   * @var \Drupal\feeds\FeedClearHandler
   */
  protected $handler;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->dispatcher = new EventDispatcher();
    $this->context = [];
    $this->handler = $this->getMockBuilder(FeedClearHandler::class)
      ->setConstructorArgs([
        $this->dispatcher,
        $this->createMock(Connection::class),
      ])
      ->onlyMethods(['batchSet'])
      ->getMock();
    $this->handler->setStringTranslation($this->getStringTranslationStub());

    $state = $this->createFeedsState();

    $this->feed = $this->createMock('Drupal\feeds\FeedInterface');
    $this->feed->expects($this->any())
      ->method('getState')
      ->with(StateInterface::CLEAR)
      ->willReturn($state);
  }

  /**
   * @covers ::startBatchClear
   */
  public function testStartBatchClear() {
    $this->feed
      ->expects($this->once())
      ->method('lock')
      ->willReturn($this->feed);

    $this->handler->startBatchClear($this->feed);
  }

  /**
   * @covers ::clear
   */
  public function testClear() {
    $this->feed->expects($this->exactly(2))
      ->method('progressClearing')
      ->willReturn(0.5, 1.0);

    $this->handler->clear($this->feed, $this->context);
    $this->assertSame($this->context['finished'], 0.5);
    $this->handler->clear($this->feed, $this->context);
    $this->assertSame($this->context['finished'], 1.0);
  }

  /**
   * @covers ::clear
   */
  public function testException() {
    $this->dispatcher->addListener(FeedsEvents::CLEAR, function ($event) {
      throw new \Exception();
    });

    $this->feed->expects($this->once())
      ->method('unlock');

    $this->expectException(\Exception::class);
    $this->handler->clear($this->feed, $this->context);
  }

}
