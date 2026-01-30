<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\TamperBase;
use Drupal\tamper\TamperableItemInterface;

/**
 * Plugin implementation for rewriting a value.
 *
 * Note: itemUsage is set to "required", but the plugin won't throw an exception
 * if no item is passed. It just doesn't do anything meaningful without it.
 *
 * @Tamper(
 *   id = "rewrite",
 *   label = @Translation("Rewrite"),
 *   description = @Translation("Rewrite a field using tokens."),
 *   category = @Translation("Other"),
 *   itemUsage = "required"
 * )
 */
class Rewrite extends TamperBase {

  const SETTING_TEXT = 'text';

  /**
   * Flag indicating whether there are multiple values.
   *
   * @var bool
   */
  protected $multiple = FALSE;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_TEXT] = '';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_TEXT] = [
      '#type' => 'textarea',
      '#title' => $this->t('Replacement pattern'),
      '#default_value' => $this->getSetting(self::SETTING_TEXT),
    ];

    $form[self::SETTING_TEXT]['#description'] = [
      '#theme' => 'item_list',
      '#items' => [
        $this->t('You can insert values using square brackets like @example (see replacement patterns below).', [
          '@example' => new FormattableMarkup('<code>[fieldname]</code>', []),
        ]),
        $this->t('You can also use nested values such as @example1 or @example2.', [
          '@example1' => new FormattableMarkup('<code>[author.name.first]</code>', []),
          '@example2' => new FormattableMarkup('<code>[address.street]</code>', []),
        ]),
        $this->t('If the input is an array, the plugin will apply the expression to each value.'),
        $this->t('You can also use the special placeholder @key to insert the current array key. For example: @example.', [
          '@key' => new FormattableMarkup('<code>{key}</code>', []),
          '@example' => new FormattableMarkup('<code>[prices.{key}.amount]</code>', []),
        ]),
      ],
    ];

    $replace = [
      '[_self]',
    ];
    foreach ($this->sourceDefinition->getList() as $key => $label) {
      $replace[] = '[' . $key . ']';
    }
    $form['replacement_patterns'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Available replacement patterns'),
      'list' => [
        '#theme' => 'item_list',
        '#items' => $replace,
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([
      self::SETTING_TEXT => $form_state->getValue(self::SETTING_TEXT),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getUsedSourceProperties(TamperableItemInterface $item): array {
    $properties = [];

    // Get the configured text pattern.
    $pattern = $this->getConfiguration()[static::SETTING_TEXT] ?? '';

    // Match tokens like [Foo], [articles], [metadata.category] and
    // [pricing.{key}.price].
    if (preg_match_all('/\[([^\]]+)\]/', $pattern, $matches)) {
      foreach ($matches[1] as $token) {
        // Extract only the part before the first dot or {.
        $top_level = preg_split('/[.{]/', $token, 2)[0];
        if ($top_level !== '') {
          $properties[] = $top_level;
        }
      }
    }

    // Ensure unique values in the same order they appeared.
    return array_values(array_unique($properties));
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, ?TamperableItemInterface $item = NULL) {
    $this->multiple = is_array($data);

    if (is_null($item)) {
      // Nothing to rewrite.
      return $data;
    }

    $source = array_merge($item->getSource(), [
      '_self' => $data,
    ]);
    $replacements = $this->extract($source);
    return $this->applyReplacements($data, $replacements);
  }

  /**
   * Extracts data recursively.
   *
   * @param array $data
   *   The data to extract.
   * @param array $prefixes
   *   (optional) The keys to use.
   *
   * @return array
   *   A list of replacement patterns.
   */
  protected function extract(array $data, array $prefixes = []): array {
    $replacements = [];

    foreach ($data as $key => $value) {
      $replacement_key_array = array_merge($prefixes, [$key]);
      $replacement_key_string = '[' . implode('.', $replacement_key_array) . ']';

      if (is_array($value)) {
        $replacements[$replacement_key_string] = $value;
        $replacements += $this->extract($value, $replacement_key_array);
      }
      else {
        $replacements[$replacement_key_string] = (string) $value;
      }
    }

    return $replacements;
  }

  /**
   * Applies replacements to the data.
   *
   * If the input data is an array, this method applies the replacements per
   * key. It also injects a {key} placeholder for use within expressions.
   *
   * @param mixed $data
   *   The input data, either a string or an array of values.
   * @param array $replacements
   *   An array of replacement tokens and their corresponding values.
   *
   * @return string|array
   *   The rewritten data, preserving the structure of the input.
   */
  protected function applyReplacements($data, array $replacements) {
    if (!is_array($data)) {
      // Since the data is singular, flatten any replacements that are an array.
      foreach ($replacements as $replacements_key => $replacements_value) {
        if (is_array($replacements_value)) {
          $replacements[$replacements_key] = reset($replacements_value);
        }
      }

      // Pick the pattern to replace, but replace {key} in pattern.
      $pattern = $this->getSetting(self::SETTING_TEXT);
      if (isset($replacements['{key}']) && strpos($pattern, '{key}') !== FALSE) {
        $pattern = str_replace('{key}', $replacements['{key}'], $pattern);
      }

      // Use empty strings for unresolved tokens.
      $matches = [];
      preg_match_all('/\[[^\]]+\]/', $pattern, $matches);
      if (isset($matches[0])) {
        $tokens = $matches[0];
        $replacements += array_fill_keys($tokens, '');
      }

      // Replace all [token] style patterns with resolved values.
      return strtr($pattern, $replacements);
    }

    foreach ($data as $data_key => $data_value) {
      $replacements_row = $replacements;

      // For replacements that are an array, pick the data key from it, if it
      // exists.
      foreach ($replacements_row as $replacements_key => $replacements_value) {
        if (is_array($replacements_value)) {
          $replacements_row[$replacements_key] = $replacements_value[$data_key] ?? '';
        }
      }

      $replacements_row['{key}'] = $data_key;
      $data[$data_key] = $this->applyReplacements($data_value, $replacements_row);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return $this->multiple;
  }

}
