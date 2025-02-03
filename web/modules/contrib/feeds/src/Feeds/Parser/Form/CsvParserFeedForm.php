<?php

namespace Drupal\feeds\Feeds\Parser\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Parser\CsvParser;
use Drupal\feeds\Plugin\Type\ExternalPluginFormBase;

/**
 * Provides a form on the feed edit page for the CsvParser.
 */
class CsvParserFeedForm extends ExternalPluginFormBase {

  /**
   * Returns help text for the CSV parser.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed for which to return a help text.
   *
   * @return array
   *   A renderable array.
   */
  protected function getHelp(FeedInterface $feed): array {
    $build = [];
    $feed_type = $feed->getType();
    $columns = [];
    if ($this->plugin instanceof CsvParser) {
      $sources = $this->plugin->getMappingSourceValues($feed_type);
      foreach ($sources as $col) {
        if (strpos($col, ',') !== FALSE) {
          $col = '"' . str_replace('"', '""', $col) . '"';
        }

        // Prevent columns without headers from being added to the template.
        if (strlen(trim($col))) {
          $columns[$col] = $col;
        }
      }
      if (count($columns) > 0) {
        $build['description'] = [
          '#prefix' => '<p>',
          '#markup' => $this->t('Import <a href=":csv">CSV files</a> with one or more of these columns: @columns.', [
            ':csv' => Url::fromUri('http://en.wikipedia.org/wiki/Comma-separated_values')->toString(),
            '@columns' => implode(', ', $columns),
          ]),
          '#suffix' => '</p>',
        ];
      }
    }

    // Investigate sources marked as unique.
    $uniques = [];
    foreach ($feed_type->getMappings() as $delta => $mapping) {
      if (empty($mapping['unique'])) {
        continue;
      }

      foreach ($mapping['unique'] as $key => $true) {
        $source_key = $mapping['map'][$key];
        if (isset($sources[$source_key])) {
          $uniques[$sources[$source_key]] = $sources[$source_key];
        }
      }
    }

    $items = [];
    if ($uniques) {
      $items[] = $this->formatPlural(
        count($uniques),
        'Column <strong>@columns</strong> is mandatory and considered unique: only one item per @columns value will be created.',
        'Columns <strong>@columns</strong> are mandatory and values in these columns are considered unique: only one entry per value in one of these column will be created.',
        [
          '@columns' => implode(', ', $uniques),
        ]
      );
    }
    else {
      $items[] = $this->t('No columns are unique. The import will only create new items, no items will be updated.');
    }

    if ($feed->isNew()) {
      $items[] = [
        '#type' => 'link',
        '#title' => $this->t('Download a template'),
        '#url' => Url::fromRoute('entity.feeds_feed_type.template', [
          'feeds_feed_type' => $feed_type->id(),
        ]),
      ];
    }
    else {
      $items[] = [
        '#type' => 'link',
        '#title' => $this->t('Download a template'),
        '#url' => Url::fromRoute('entity.feeds_feed.template', [
          'feeds_feed' => $feed->id(),
        ]),
      ];
    }
    $build['list'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, ?FeedInterface $feed = NULL) {
    $feed_config = $feed->getConfigurationFor($this->plugin);

    $form['help'] = [
      '#prefix' => '<div class="help">',
      '#suffix' => '</div>',
    ] + $this->getHelp($feed);

    $form['delimiter'] = [
      '#type' => 'select',
      '#title' => $this->t('Delimiter'),
      '#description' => $this->t('The character that delimits fields in the CSV file.'),
      '#options'  => [
        ',' => ',',
        ';' => ';',
        'TAB' => 'TAB',
        '|' => '|',
        '+' => '+',
      ],
      '#default_value' => $feed_config['delimiter'],
    ];

    $form['no_headers'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('No Headers'),
      '#description' => $this->t("Check if the imported CSV file does not start with a header row. If checked, mapping sources must be named '0', '1', '2' etc."),
      '#default_value' => $feed_config['no_headers'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state, ?FeedInterface $feed = NULL) {
    $feed->setConfigurationFor($this->plugin, $form_state->getValues());
  }

}
