<?php

namespace Drupal\feeds\Feeds\Parser;

use Drupal\feeds\Component\CsvParser as CsvFileParser;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Feeds\Item\DynamicItem;
use Drupal\feeds\Plugin\Type\Parser\ParserWithTemplateInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\StateInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines a CSV feed parser.
 *
 * @FeedsParser(
 *   id = "csv",
 *   title = "CSV",
 *   description = @Translation("Parse CSV files."),
 *   form = {
 *     "configuration" = "Drupal\feeds\Feeds\Parser\Form\CsvParserForm",
 *     "feed" = "Drupal\feeds\Feeds\Parser\Form\CsvParserFeedForm",
 *   },
 * )
 */
class CsvParser extends ParserBase implements ParserWithTemplateInterface {

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    // Get sources.
    $sources = [];
    $skip_sources = [];
    foreach ($feed->getType()->getMappingSources() as $key => $info) {
      if (isset($info['type']) && $info['type'] != 'csv') {
        $skip_sources[$key] = $key;
        continue;
      }
      if (isset($info['value']) && trim(strval($info['value'])) !== '') {
        $sources[$info['value']] = $key;
      }
    }

    $feed_config = $feed->getConfigurationFor($this);

    if (!filesize($fetcher_result->getFilePath())) {
      throw new EmptyFeedException();
    }

    // Load and configure parser.
    $parser = CsvFileParser::createFromFilePath($fetcher_result->getFilePath())
      ->setDelimiter($feed_config['delimiter'] === 'TAB' ? "\t" : $feed_config['delimiter'])
      ->setHasHeader(!$feed_config['no_headers'])
      ->setStartByte((int) $state->pointer);

    // Wrap parser in a limit iterator.
    $parser = new \LimitIterator($parser, 0, $this->configuration['line_limit']);

    $header = !$feed_config['no_headers'] ? $parser->getHeader() : [];
    $result = new ParserResult();

    foreach ($parser as $row) {
      $item = new DynamicItem();

      foreach ($row as $delta => $cell) {
        $key = $header[$delta] ?? $delta;
        if (isset($skip_sources[$key])) {
          // Skip custom sources that are not of type "csv".
          continue;
        }
        // Pick machine name of source, if one is found.
        if (isset($sources[$key])) {
          $key = $sources[$key];
        }
        $item->set($key, $cell);
      }

      $result->addItem($item);
    }

    // Report progress.
    $state->total = filesize($fetcher_result->getFilePath());
    $state->pointer = $parser->lastLinePos();
    $state->progress($state->total, $state->pointer);

    // Set progress to complete if no more results are parsed. Can happen with
    // empty lines in CSV.
    if (!$result->count()) {
      $state->setCompleted();
    }

    return $result;
  }

  /**
   * Returns a list mapping sources that are used by the CSV parser.
   *
   * @param \Drupal\feeds\FeedTypeInterface $feed_type
   *   The feed type to get the CSV sources for.
   *
   * @return array
   *   A list of sources keyed by machine name.
   */
  public function getMappingSourceValues(FeedTypeInterface $feed_type): array {
    $sources = [];
    foreach ($feed_type->getMappingSources() as $key => $info) {
      if (isset($info['type']) && $info['type'] != 'csv') {
        continue;
      }
      if (isset($info['value']) && trim(strval($info['value'])) !== '') {
        $sources[$key] = $info['value'];
      }
    }
    return $sources;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedCustomSourcePlugins(): array {
    return ['csv'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultFeedConfiguration() {
    return [
      'delimiter' => $this->configuration['delimiter'],
      'no_headers' => $this->configuration['no_headers'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'delimiter' => ',',
      'no_headers' => 0,
      'line_limit' => 100,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplate(FeedTypeInterface $feed_type, ?FeedInterface $feed = NULL): Response {
    $feed_config = $this->configuration;
    if ($feed instanceof FeedInterface) {
      $feed_config = $feed->getConfigurationFor($this);
    }

    $template_file_details = $this->getTemplateFileDetails($feed_config);
    $filename = "{$this->getPluginId()}_template.{$template_file_details['extension']}";

    $headers = [
      'Cache-Control' => 'max-age=60, must-revalidate',
      'Content-Disposition' => 'attachment; filename="' . $filename . '"',
      'Content-type' => "{$template_file_details['mime_type']}; charset=utf-8",
    ];

    return new Response($this->getTemplateContents($feed_type, $feed), 200, $headers);
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplateContents(FeedTypeInterface $feed_type, ?FeedInterface $feed = NULL): string {
    $feed_config = $this->configuration;
    if ($feed instanceof FeedInterface) {
      $feed_config = $feed->getConfigurationFor($this);
    }
    $delimiter = $feed_config['delimiter'] === 'TAB' ? "\t" : $feed_config['delimiter'];

    $columns = [];
    foreach ($this->getMappingSourceValues($feed_type) as $col) {
      if (strpos($col, $delimiter) !== FALSE) {
        $col = '"' . str_replace('"', '""', $col) . '"';
      }

      // Prevent columns without headers from being added to the template.
      if (strlen(trim($col))) {
        $columns[$col] = $col;
      }
    }

    // Add a newline at the end of the file.
    return implode($delimiter, $columns) . "\n";
  }

  /**
   * Gets details about the template file, for the delimiter in the config.
   *
   * The resulting details indicate the file extension and mime type for the
   * delimiter type.
   *
   * @param array $config
   *   The configuration for the parser.
   *
   * @return array
   *   An array with the following information:
   *     - 'extension': The file extension for the template ('tsv', 'csv', etc).
   *     - 'mime-type': The mime type for the template
   *       ('text/tab-separated-values', 'text/csv', etc).
   */
  protected function getTemplateFileDetails(array $config) {
    switch ($config['delimiter']) {
      case 'TAB':
        $extension = 'tsv';
        $mime_type = 'text/tab-separated-values';
        break;

      default:
        $extension = 'csv';
        $mime_type = 'text/csv';
        break;
    }

    return [
      'extension' => $extension,
      'mime_type' => $mime_type,
    ];
  }

}
