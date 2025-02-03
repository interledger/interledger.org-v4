<?php

namespace Drupal\Tests\feeds\Unit\Plugin\QueueWorker;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Plugin\QueueWorker\FeedQueueWorkerBase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @coversDefaultClass \Drupal\feeds\Plugin\QueueWorker\FeedQueueWorkerBase
 * @group feeds
 */
class FeedQueueWorkerBaseTest extends FeedsUnitTestCase {

  /**
   * Tests various methods on the FeedQueueWorkerBase class.
   */
  public function test() {
    $container = new ContainerBuilder();
    $container->set('queue', $this->createMock(QueueFactory::class));
    $container->set('event_dispatcher', new EventDispatcher());
    $container->set('account_switcher', $this->getMockedAccountSwitcher());
    $container->set('entity_type.manager', $this->createMock(EntityTypeManagerInterface::class));
    $container->set('class_resolver', $this->createMock(ClassResolverInterface::class));

    $plugin = QueueWorkerMock::create($container, [], '', []);

    $method = $this->getProtectedClosure($plugin, 'handleException');
    $method($this->getMockFeed(), new EmptyFeedException());

    $this->expectException(\RuntimeException::class);
    $method($this->getMockFeed(), new \RuntimeException());
  }

}

/**
 * For testing methods from FeedQueueWorkerBase.
 *
 * Abstract classes cannot be mocked.
 */
class QueueWorkerMock extends FeedQueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {}

}
