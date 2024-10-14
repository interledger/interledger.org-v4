<?php

namespace Drupal\Tests\feeds_ex\Unit\Feeds\Parser;

use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\StateInterface;
use Drupal\feeds_ex\Feeds\Parser\ParserBase;
use Drupal\feeds_ex\Messenger\TestMessenger;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\ParserBase
 * @group feeds_ex
 */
class ParserBaseTest extends ParserTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $configuration = ['feed_type' => $this->feedType];
    $this->parser = new TestParser($configuration, 'test', []);
    $this->parser->setStringTranslation($this->getStringTranslationStub());
    $this->parser->setFeedsExMessenger(new TestMessenger());
  }

  /**
   * Tests preparing raw.
   *
   * @covers ::prepareRaw
   */
  public function testPrepareRaw() {
    $fetcher_result = $this->prophesize(FetcherResultInterface::class);
    $fetcher_result->getRaw()
      ->willReturn('foobar');
    $raw = $this->callProtectedMethod($this->parser, 'prepareRaw', [
      $fetcher_result->reveal(),
    ]);
    $this->assertSame('foobar', $raw);
  }

  /**
   * Tests preparing raw with null bytes.
   *
   * @covers ::prepareRaw
   */
  public function testPrepareRawWithNullBytes() {
    $fetcher_result = $this->prophesize(FetcherResultInterface::class);
    $fetcher_result->getRaw()
      ->willReturn('foo' . chr(0) . 'bar');
    $raw = $this->callProtectedMethod($this->parser, 'prepareRaw', [
      $fetcher_result->reveal(),
    ]);
    $this->assertSame('foobar', $raw);
  }

  /**
   * Tests preparing raw when the raw fetcher result is not a string.
   *
   * @covers ::prepareRaw
   */
  public function testPrepareRawWithNullResult() {
    $this->expectException(EmptyFeedException::class);
    $fetcher_result = $this->prophesize(FetcherResultInterface::class);
    $this->callProtectedMethod($this->parser, 'prepareRaw', [
      $fetcher_result->reveal(),
    ]);
  }

  /**
   * Tests preparing raw when the raw fetcher result an empty string.
   *
   * @covers ::prepareRaw
   */
  public function testPrepareRawWithEmptyStringResult() {
    $this->expectException(EmptyFeedException::class);
    $fetcher_result = $this->prophesize(FetcherResultInterface::class);
    $fetcher_result->getRaw()
      ->willReturn('');
    $this->callProtectedMethod($this->parser, 'prepareRaw', [
      $fetcher_result->reveal(),
    ]);
  }

}

/**
 * Test parser to test ParserBase.
 */
class TestParser extends ParserBase {

  /**
   * {@inheritdoc}
   */
  protected function executeContext(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function executeSourceExpression($machine_name, $expression, $row) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function validateExpression(&$expression) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getErrors() {
    return [];
  }

}
