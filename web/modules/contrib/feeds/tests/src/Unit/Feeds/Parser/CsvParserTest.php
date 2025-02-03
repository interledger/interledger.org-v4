<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Parser;

use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Feeds\Parser\CsvParser;
use Drupal\feeds\Result\FetcherResult;
use Drupal\feeds\StateInterface;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Parser\CsvParser
 * @group feeds
 */
class CsvParserTest extends FeedsUnitTestCase {

  /**
   * The Feeds parser plugin under test.
   *
   * @var \Drupal\feeds\Feeds\Parser\CsvParser
   */
  protected $parser;

  /**
   * The feed type entity.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * The feed entity.
   *
   * @var \Drupal\feeds\FeedInterface
   */
  protected $feed;

  /**
   * The state object.
   *
   * @var \Drupal\feeds\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->feedType = $this->createMock(FeedTypeInterface::class);
    $configuration = ['feed_type' => $this->feedType, 'line_limit' => 3];
    $this->parser = new CsvParser($configuration, 'csv', []);
    $this->parser->setStringTranslation($this->getStringTranslationStub());

    $this->state = $this->createFeedsState();

    $this->feed = $this->createMock(FeedInterface::class);
    $this->feed->expects($this->any())
      ->method('getType')
      ->willReturn($this->feedType);
  }

  /**
   * Tests parsing a CSV file that succeeds.
   *
   * @covers ::parse
   */
  public function testParse() {
    $this->feedType->method('getMappingSources')
      ->willReturn([]);

    $this->feed->expects($this->any())
      ->method('getConfigurationFor')
      ->with($this->parser)
      ->willReturn($this->parser->defaultFeedConfiguration());

    $file = $this->resourcesPath() . '/csv/example.csv';
    $fetcher_result = new FetcherResult($file);

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);

    $this->assertSame(count($result), 3);
    $this->assertSame($result[0]->get('Header A'), '"1"');

    // Parse again. Tests batching.
    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);

    $this->assertSame(count($result), 3);
    $this->assertSame($result[0]->get('Header B'), "new\r\nline 2");
  }

  /**
   * Tests parsing with the "no_headers" option enabled.
   */
  public function testParseWithoutHeaders() {
    // Enable "no_headers" option.
    $config = [
      'no_headers' => TRUE,
    ] + $this->parser->defaultFeedConfiguration();

    $this->feed->expects($this->any())
      ->method('getConfigurationFor')
      ->with($this->parser)
      ->willReturn($config);

    // Provide mapping sources.
    $this->feedType->method('getMappingSources')
      ->willReturn([
        'column1' => [
          'label' => 'Column 1',
          'value' => 0,
          'machine_name' => 'column1',
        ],
        'column2' => [
          'label' => 'Column 2',
          'value' => 1,
          'machine_name' => 'column2',
        ],
      ]);

    $file = $this->resourcesPath() . '/csv/content.csv';
    $fetcher_result = new FetcherResult($file);

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);

    // Assert that there are three items.
    $this->assertSame(count($result), 3);
    // Assert that each item has the expected value on the machine name.
    $this->assertSame('guid', $result[0]->get('column1'));
    $this->assertSame('title', $result[0]->get('column2'));
    $this->assertSame('1', $result[1]->get('column1'));
    $this->assertSame('Lorem ipsum', $result[1]->get('column2'));
    $this->assertSame('2', $result[2]->get('column1'));
    $this->assertSame('Ut wisi enim ad minim veniam', $result[2]->get('column2'));
  }

  /**
   * Tests parsing an empty CSV file.
   *
   * @covers ::parse
   */
  public function testEmptyFeed() {
    $this->feedType->method('getMappingSources')
      ->willReturn([]);

    touch('vfs://feeds/empty_file');
    $result = new FetcherResult('vfs://feeds/empty_file');

    $this->expectException(EmptyFeedException::class);
    $this->parser->parse($this->feed, $result, $this->state);
  }

  /**
   * Tests parsing a file with a few extra blank lines.
   */
  public function testFeedWithExtraBlankLines() {
    $this->feedType->method('getMappingSources')
      ->willReturn([]);

    // Set an high line limit.
    $configuration = ['feed_type' => $this->feedType, 'line_limit' => 100];
    $this->parser = new CsvParser($configuration, 'csv', []);
    $this->parser->setStringTranslation($this->getStringTranslationStub());

    $this->feed->expects($this->any())
      ->method('getConfigurationFor')
      ->with($this->parser)
      ->willReturn($this->parser->defaultFeedConfiguration());

    $file = $this->resourcesPath() . '/csv/with-empty-lines.csv';
    $fetcher_result = new FetcherResult($file);

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertCount(9, $result);

    // Parse again.
    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertCount(0, $result);

    // Assert that parsing has finished.
    $this->assertEquals(StateInterface::BATCH_COMPLETE, $this->state->progress);
  }

  /**
   * @covers ::getMappingSources
   */
  public function testGetMappingSources() {
    // Not really much to test here.
    $this->assertSame([], $this->parser->getMappingSources());
  }

  /**
   * @covers ::getTemplateContents
   *
   * @param string $expected
   *   The expected contents of the CSV template.
   * @param string $delimiter
   *   The delimiter to test with.
   * @param array $columns
   *   The CSV columns, keyed by machine name.
   *
   * @dataProvider getTemplateDataProvider
   */
  public function testGetTemplateContentsForFeedType($expected, $delimiter, array $columns) {
    // Set mapping sources.
    $sources = [];
    foreach ($columns as $machine_name => $column) {
      $sources[$machine_name] = [
        'label' => $column,
        'value' => $column,
        'machine_name' => $machine_name,
      ];
    }

    // Also add custom sources of a different type.
    $sources['blank'] = [
      'label' => 'Blank',
      'value' => 'blank',
      'machine_name' => 'blank',
      'type' => 'blank',
    ];
    $sources['parent:title'] = [
      'label' => 'Feed: @label',
      'description' => 'The title of this feed, always treated as non-markup plain text.',
      'id' => 'basic_field',
      'type' => 'Feed entity',
    ];

    $this->feedType->method('getMappingSources')
      ->willReturn($sources);

    // Set delimiter config.
    $config = $this->parser->getConfiguration();
    $config['delimiter'] = $delimiter;
    $this->parser->setConfiguration($config);

    $this->assertSame($expected, $this->parser->getTemplateContents($this->feedType));
  }

  /**
   * Data provider for ::testGetTemplate().
   */
  public static function getTemplateDataProvider(): array {
    $default_columns = [
      'title_' => 'title+;|',
      'alpha_beta_gamma' => 'alpha, beta + gamma',
      'guid' => 'guid',
    ];

    return [
      // Delimiter ',' test. Source keys containing a ',' should be wrapped in
      // quotes.
      [
        'expected' => 'title+;|,"alpha, beta + gamma",guid' . "\n",
        'delimiter' => ',',
        'columns' => $default_columns,
      ],

      // Delimiter ';' test. Source keys containing a ';' should be wrapped in
      // quotes.
      [
        'expected' => '"title;)";alpha, beta + gamma;guid' . "\n",
        'delimiter' => ';',
        'columns' => [
          'title_' => 'title;)',
        ] + $default_columns,
      ],

      // Delimiter 'TAB' test.
      [
        'expected' => 'title,;|	alpha, beta + gamma	guid' . "\n",
        'delimiter' => 'TAB',
        'columns' => [
          'title_' => 'title,;|',
        ] + $default_columns,
      ],

      // Delimiter '|' test. Source keys containing a '|' should be wrapped in
      // quotes.
      [
        'expected' => 'title+;,|"alpha|beta|gamma"|guid' . "\n",
        'delimiter' => '|',
        'columns' => [
          'title_' => 'title+;,',
          'alpha_beta_gamma' => 'alpha|beta|gamma',
        ] + $default_columns,
      ],

      // Delimiter '+' test. Source keys containing a '+' should be wrapped in
      // quotes.
      [
        'expected' => 'title,;|+"alpha, beta + gamma"+guid' . "\n",
        'delimiter' => '+',
        'columns' => [
          'title_' => 'title,;|',
        ] + $default_columns,
      ],

      // Ensure that when a source key is used multiple times in mapping, the
      // key is only printed once in the CSV template.
      [
        'expected' => 'text,guid,date' . "\n",
        'delimiter' => ',',
        'columns' => [
          'text' => 'text',
          'guid' => 'guid',
          'date' => 'date',
        ],
      ],

      // Special characters. Things like '&' shouldn't be converted to '&amp;'
      // for example.
      [
        'expected' => '&,alpha&beta,<created>,guid' . "\n",
        'delimiter' => ',',
        'columns' => [
          'title_' => '&',
          'alpha_beta_gamma' => 'alpha&beta',
          'created' => '<created>',
          'guid' => 'guid',
        ],
      ],

      // Blank sources (source which name only contains spaces) should not end
      // up in the template, but a zero should.
      [
        'expected' => '0' . "\n",
        'delimiter' => ',',
        'columns' => [
          '0' => '0',
          'empty' => ' ',
        ],
      ],
    ];
  }

  /**
   * @covers ::getTemplateContents
   */
  public function testGetTemplateContentsForFeed() {
    // Set mapping sources.
    $sources = [
      'foo_1' => [
        'label' => 'Foo 1',
        'value' => 'Foo',
        'machine_name' => 'foo_1',
      ],
      'bar' => [
        'label' => 'Bar',
        'value' => 'bar',
        'machine_name' => 'bar',
      ],
      'alpha_beta_gamma' => [
        'label' => 'Alpha, Beta plus Gamma',
        'value' => 'alpha, beta + gamma',
        'machine_name' => 'alpha_beta_gamma',
      ],
    ];

    // Also add custom sources of a different type.
    $sources['blank'] = [
      'label' => 'Blank',
      'value' => 'blank',
      'machine_name' => 'blank',
      'type' => 'blank',
    ];
    $sources['parent:title'] = [
      'label' => 'Feed: @label',
      'description' => 'The title of this feed, always treated as non-markup plain text.',
      'id' => 'basic_field',
      'type' => 'Feed entity',
    ];

    $this->feedType->method('getMappingSources')
      ->willReturn($sources);

    // Set delimiter config on the parser.
    $config = $this->parser->getConfiguration();
    $config['delimiter'] = ',';
    $this->parser->setConfiguration($config);

    // Set delimiter config on the feed.
    $this->feed->expects($this->any())
      ->method('getConfigurationFor')
      ->with($this->parser)
      ->willReturn([
        'delimiter' => 'TAB',
      ] + $this->parser->defaultFeedConfiguration());

    $expected = 'Foo	bar	alpha, beta + gamma' . "\n";
    $this->assertSame($expected, $this->parser->getTemplateContents($this->feedType, $this->feed));
  }

}
