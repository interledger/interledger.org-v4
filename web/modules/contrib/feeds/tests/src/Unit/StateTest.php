<?php

namespace Drupal\Tests\feeds\Unit;

use Drupal\Component\DependencyInjection\ReverseContainer;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\State;
use Drupal\feeds\StateInterface;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @coversDefaultClass \Drupal\feeds\State
 * @group feeds
 */
class StateTest extends FeedsUnitTestCase {

  /**
   * The event dispatcher.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The messenger service.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The logger for the feeds channel.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
    $this->messenger = $this->prophesize(MessengerInterface::class);
    $this->logger = $this->prophesize(LoggerInterface::class);
  }

  /**
   * Tests public progress property.
   *
   * @covers ::progress
   */
  public function testProgress() {
    $state = $this->createFeedsState();
    $state->progress(10, 10);
    $this->assertSame(StateInterface::BATCH_COMPLETE, $state->progress);

    $state->progress(20, 10);
    $this->assertSame(0.5, $state->progress);

    $state->progress(10, 30);
    $this->assertSame(StateInterface::BATCH_COMPLETE, $state->progress);

    $state->progress(0, 0);
    $this->assertSame(StateInterface::BATCH_COMPLETE, $state->progress);

    $state->progress(PHP_INT_MAX, PHP_INT_MAX - 1);
    $this->assertSame(.99, $state->progress);
  }

  /**
   * Tests that a State object can be serialized with injected services.
   *
   * @covers ::create
   * @covers ::displayMessages
   * @covers ::logMessages
   */
  public function testSerialize() {
    $this->doSerializeTest();
  }

  /**
   * Tests that the injected messenger service does not get serialized.
   *
   * @covers ::create
   * @covers ::displayMessages
   * @covers ::logMessages
   */
  public function testSerializeWithoutMessengerServicesBeingSerializedToo() {
    $messenger = $this->createMock(MockedMessengerInterface::class);
    $messenger->expects($this->never())
      ->method('__sleep');

    $this->doSerializeTest([
      'messenger' => $messenger,
    ]);
  }

  /**
   * Tests that the logger does not get serialized.
   *
   * @covers ::create
   * @covers ::displayMessages
   * @covers ::logMessages
   */
  public function testSerializeWithoutLoggerBeingSerializedToo() {
    $logger = $this->createMock(MockedLoggerInterface::class);
    $logger->expects($this->never())
      ->method('__sleep');

    $logger_factory = $this->prophesize(LoggerChannelFactoryInterface::class);
    $logger_factory->get('feeds')
      ->willReturn($logger);

    $this->doSerializeTest([
      'logger.factory' => $logger_factory->reveal(),
    ]);
  }

  /**
   * Do a serialize test for the State object.
   *
   * @param array $services
   *   (optional) The services to set on the container.
   */
  protected function doSerializeTest(array $services = []) {
    if (!isset($services['messenger'])) {
      $services['messenger'] = $this->createMock(MessengerInterface::class);
    }
    if (!isset($services['logger.factory'])) {
      $logger_factory = $this->prophesize(LoggerChannelFactoryInterface::class);
      $logger_factory->get('feeds')
        ->willReturn($this->createMock(LoggerInterface::class));
      $services['logger.factory'] = $logger_factory->reveal();
    }

    // Build a container.
    $container = new ContainerBuilder();
    foreach ($services as $key => $service) {
      $container->set($key, $service);
    }
    $container->set(ReverseContainer::class, new ReverseContainer($container));
    \Drupal::setContainer($container);

    $state = State::create($container, 0);
    $state->setMessage('A message.');

    $serialized = serialize($state);
    $this->assertIsString($serialized);

    // Unserialize again and assert that methods using services can be called
    // without issues.
    $state = unserialize($serialized);
    $state->displayMessages();
    $state->logMessages($this->createMock(FeedInterface::class));
  }

  /**
   * Tests displaying messages on a state object from an older Feeds version.
   */
  public function testDisplayMessagesOnOldSerializedStateObject() {
    $this->messenger->addMessage(Argument::type(FormattableMarkup::class), Argument::type('string'), Argument::type('bool'))
      ->shouldBeCalled();
    $this->buildContainer();

    $serialized = file_get_contents(__DIR__ . '/../../fixtures/feeds-8.x-3.0-beta5-state-serialized.txt');
    $state = unserialize($serialized);
    $state->displayMessages();
  }

  /**
   * Tests logging messages on a state object from an older Feeds version.
   */
  public function testLogMessagesOnOldSerializedStateObject() {
    $feed = $this->createMock(FeedInterface::class);
    $this->logger->log(Argument::type('string'), Argument::type(FormattableMarkup::class), ['feed' => $feed])
      ->shouldBeCalled();
    $this->buildContainer();

    $serialized = file_get_contents(__DIR__ . '/../../fixtures/feeds-8.x-3.0-beta5-state-serialized.txt');
    $state = unserialize($serialized);
    $state->logMessages($feed);
  }

  /**
   * Builds the Drupal service container.
   */
  protected function buildContainer() {
    $logger_factory = $this->prophesize(LoggerChannelFactoryInterface::class);
    $logger_factory->get('feeds')->willReturn($this->logger->reveal());

    $container = new ContainerBuilder();
    $container->set('messenger', $this->messenger->reveal());
    $container->set('logger.factory', $logger_factory->reveal());
    $container->set('event_dispatcher', $this->eventDispatcher->reveal());
    \Drupal::setContainer($container);
  }

}

/**
 * Extends MessengerInterface to be able to mock the __sleep() method.
 */
interface MockedMessengerInterface extends MessengerInterface {

  /**
   * Magic method __sleep() which should not be called.
   */
  public function __sleep();

}

/**
 * Extends LoggerInterface to be able to mock the __sleep() method.
 */
interface MockedLoggerInterface extends LoggerInterface {

  /**
   * Magic method __sleep() which should not be called.
   */
  public function __sleep();

}
