<?php

namespace Drupal\Tests\feeds\Unit\Event;

use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;
use Drupal\feeds\Event\EventBase;
use Drupal\feeds\FeedInterface;

/**
 * @coversDefaultClass \Drupal\feeds\Event\EventBase
 * @group feeds
 */
class EventBaseTest extends FeedsUnitTestCase {

  /**
   * @covers ::getFeed
   */
  public function testGetFeed() {
    $feed = $this->createMock(FeedInterface::class);
    $event = new EventMock($feed);
    $this->assertSame($feed, $event->getFeed());
  }

}

/**
 * For testing methods from EventBase.
 *
 * Abstract classes cannot be mocked.
 */
class EventMock extends EventBase {}
