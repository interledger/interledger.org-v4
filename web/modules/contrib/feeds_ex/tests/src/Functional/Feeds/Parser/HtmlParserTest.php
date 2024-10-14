<?php

namespace Drupal\Tests\feeds_ex\Functional\Feeds\Parser;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\HtmlParser
 * @group feeds_ex
 */
class HtmlParserTest extends ParserTestBase {

  use ContextTestTrait;

  /**
   * The ID of the parser to test.
   *
   * @var string
   */
  protected static $parserId = 'html';

  /**
   * {@inheritdoc}
   */
  protected static $customSourceType = 'xml';

  /**
   * {@inheritdoc}
   */
  public static function dataProviderValidContext() {
    return [
      ['//div[@class="post"]'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function dataProviderInvalidContext() {
    return [
      ['!! ', 'Invalid expression'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function testMapping() {
    $expected_sources = [
      'name' => [
        'label' => 'Name',
        'value' => 'name',
        'machine_name' => 'name',
        'type' => static::$customSourceType,
        'raw' => FALSE,
        'inner' => FALSE,
      ],
    ];
    $custom_source = [
      'label' => 'Name',
      'value' => 'name',
      'machine_name' => 'name',
    ];

    $this->setupContext();
    $this->doMappingTest($expected_sources, $custom_source);
  }

}
