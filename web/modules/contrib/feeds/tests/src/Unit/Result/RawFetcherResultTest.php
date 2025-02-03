<?php

namespace Drupal\Tests\feeds\Unit\Result;

use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;
use Drupal\feeds\Result\RawFetcherResult;

/**
 * @coversDefaultClass \Drupal\feeds\Result\RawFetcherResult
 * @group feeds
 */
class RawFetcherResultTest extends FeedsUnitTestCase {

  /**
   * @covers ::getRaw
   */
  public function testGetRaw() {
    $result = new RawFetcherResult('raw text', $this->getMockFileSystem());
    $this->assertSame($result->getRaw(), 'raw text');
  }

  /**
   * @covers ::getFilePath
   */
  public function testGetFilePath() {
    $result = new RawFetcherResult('raw text', $this->getMockFileSystem());
    $this->assertSame(file_get_contents($result->getFilePath()), 'raw text');

    // Call again to see if exception is thrown.
    $this->assertSame(file_get_contents($result->getFilePath()), 'raw text');
  }

}
