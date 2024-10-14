<?php

namespace Drupal\Tests\feeds\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\feeds\FeedImportHandler;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedsExecutableInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @coversDefaultClass \Drupal\feeds\FeedImportHandler
 * @group feeds
 */
class FeedImportHandlerTest extends FeedsUnitTestCase {

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
   * The handler to test.
   *
   * @var \Drupal\feeds\FeedImportHandler
   */
  protected $handler;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->dispatcher = new EventDispatcher();
    $this->handler = $this->getMockBuilder(FeedImportHandler::class)
      ->setConstructorArgs([
        $this->dispatcher,
        $this->createMock(Connection::class),
        $this->createMock(ClassResolverInterface::class),
        $this->createMock(KeyValueFactoryInterface::class),
        $this->createMock(TimeInterface::class),
      ])
      ->onlyMethods(['getRequestTime', 'getExecutable'])
      ->getMock();
    $this->handler->setStringTranslation($this->getStringTranslationStub());
    $this->handler->expects($this->any())
      ->method('getRequestTime')
      ->willReturn(time());

    $this->feed = $this->createMock(FeedInterface::class);
    $this->feed->expects($this->any())
      ->method('id')
      ->willReturn(10);
    $this->feed->expects($this->any())
      ->method('bundle')
      ->willReturn('test_feed');
  }

  /**
   * @covers ::import
   */
  public function testImport() {
    $this->handler->expects($this->once())
      ->method('getExecutable')
      ->willReturn($this->createMock(FeedsExecutableInterface::class));

    $this->handler->import($this->feed);
  }

  /**
   * @covers ::startBatchImport
   */
  public function testStartBatchImport() {
    $this->handler->expects($this->once())
      ->method('getExecutable')
      ->willReturn($this->createMock(FeedsExecutableInterface::class));

    $this->handler->startBatchImport($this->feed);
  }

  /**
   * @covers ::startCronImport
   */
  public function testStartCronImport() {
    $this->feed->expects($this->once())
      ->method('isLocked')
      ->willReturn(FALSE);

    $this->handler->expects($this->once())
      ->method('getExecutable')
      ->willReturn($this->createMock(FeedsExecutableInterface::class));

    $this->handler->startCronImport($this->feed);
  }

  /**
   * @covers ::pushImport
   */
  public function testPushImport() {
    $this->handler->expects($this->once())
      ->method('getExecutable')
      ->willReturn($this->createMock(FeedsExecutableInterface::class));
    $this->feed->expects($this->once())
      ->method('lock')
      ->willReturn($this->feed);

    $file = $this->resourcesPath() . '/csv/example.csv';
    $this->handler->pushImport($this->feed, file_get_contents($file), $this->createMock(FileSystemInterface::class));
  }

}
