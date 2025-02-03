<?php

namespace Drupal\Tests\feeds\Unit\Event;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;
use Drupal\feeds\Event\EventDispatcherTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @coversDefaultClass \Drupal\feeds\Event\EventDispatcherTrait
 * @group feeds
 */
class EventDispatcherTraitTest extends FeedsUnitTestCase {

  /**
   * @covers ::getEventDispatcher
   * @covers ::dispatchEvent
   */
  public function test() {
    $mock = $this->createMock(EventDispatcherTraitMock::class);
    $dispatcher = $this->createMock(EventDispatcherInterface::class);

    $container = new ContainerBuilder();
    $container->set('event_dispatcher', $dispatcher);
    \Drupal::setContainer($container);
    $method = $this->getMethod(get_class($mock), 'getEventDispatcher');
    $this->assertSame($dispatcher, $method->invokeArgs($mock, []));

    $mock->setEventDispatcher($dispatcher);
    $this->assertSame($dispatcher, $method->invokeArgs($mock, []));

    $event = new Event();
    $dispatcher->expects($this->once())
      ->method('dispatch')
      ->with($event, 'test_event');
    $method = $this->getMethod(get_class($mock), 'dispatchEvent');
    $method->invokeArgs($mock, ['test_event', $event]);
  }

}

/**
 * Mock for EventDispatcherTrait.
 */
class EventDispatcherTraitMock {

  use EventDispatcherTrait;

}
