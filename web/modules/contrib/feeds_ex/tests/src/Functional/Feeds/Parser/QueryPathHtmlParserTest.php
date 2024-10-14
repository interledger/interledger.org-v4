<?php

namespace Drupal\Tests\feeds_ex\Functional\Feeds\Parser;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\QueryPathHtmlParser
 * @group feeds_ex
 */
class QueryPathHtmlParserTest extends ParserTestBase {

  use ContextTestTrait;

  /**
   * The ID of the parser to test.
   *
   * @var string
   */
  protected static $parserId = 'querypathhtml';

  /**
   * {@inheritdoc}
   */
  protected static $customSourceType = 'querypathxml';

  /**
   * {@inheritdoc}
   */
  public static function dataProviderValidContext() {
    return [
      ['.post'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function dataProviderInvalidContext() {
    return [
      ['!! ', 'CSS selector is not well formed.'],
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
        'attribute' => '',
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
