<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\SkipTamperItemException;
use Drupal\tamper\TamperBase;
use Drupal\tamper\TamperableItemInterface;

/**
 * Plugin implementation for filtering based on a list of words/phrases.
 *
 * @Tamper(
 *   id = "keyword_filter",
 *   label = @Translation("Keyword filter"),
 *   description = @Translation("Filter based on a list of words/phrases."),
 *   category = "Filter",
 *   handle_multiples = TRUE
 * )
 */
class KeywordFilter extends TamperBase {

  /**
   * A list of words/phrases appearing in the text. Enter one value per line.
   *
   * @deprecated in tamper:8.x-1.0-alpha6 and is removed from tamper:2.0.0. Use
   *   the 'words_list' setting instead.
   *
   * @see https://www.drupal.org/node/3485191
   */
  const WORDS = 'words';

  /**
   * Index for the word list configuration option.
   */
  const WORD_LIST = 'words_list';

  /**
   * If checked, then "book" will match "book" but not "bookcase".
   */
  const WORD_BOUNDARIES = 'word_boundaries';

  /**
   * A list of words/phrases appearing in the text. Enter one value per line.
   */
  const EXACT = 'exact';

  /**
   * If checked -> "book" === "book". Override the "Respect word boundaries".
   */
  const CASE_SENSITIVE = 'case_sensitive';

  /**
   * If checked, then "book" will match "book" but not "Book" or "BOOK".
   */
  const INVERT = 'invert';

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
    $config[self::WORDS] = '';
    $config[self::WORD_LIST] = [];
    $config[self::WORD_BOUNDARIES] = FALSE;
    $config[self::EXACT] = FALSE;
    $config[self::CASE_SENSITIVE] = FALSE;
    $config[self::INVERT] = FALSE;

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::WORDS] = [
      '#type' => 'textarea',
      '#title' => $this->t('Words or phrases to filter on'),
      '#default_value' => implode("\n", $this->getWordList()),
      '#description' => $this->t('A list of words/phrases that need to appear in the text. Enter one value per line.'),
      '#required' => TRUE,
    ];

    $form[self::WORD_BOUNDARIES] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Respect word boundaries'),
      '#default_value' => $this->getSetting(self::WORD_BOUNDARIES),
      '#description' => $this->t('If checked, then "book" will match "book" but not "bookcase".'),
    ];

    $form[self::EXACT] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exact'),
      '#default_value' => $this->getSetting(self::EXACT),
      '#description' => $this->t('If checked, then "book" will only match "book". This will override the "Respect word boundaries" setting above.'),
    ];

    $form[self::CASE_SENSITIVE] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Case sensitive'),
      '#default_value' => $this->getSetting(self::CASE_SENSITIVE),
      '#description' => $this->t('If checked, then "book" will match "book" but not "Book" or "BOOK".'),
    ];

    $form[self::INVERT] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Invert filter'),
      '#default_value' => $this->getSetting(self::INVERT),
      '#description' => $this->t('Inverting the filter will remove items with the specified text.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $word_list = $this->wordsToArray($form_state->getValue(self::WORDS));

    // Check when the word boundaries setting is enabled that each word starts
    // and ends with a letter.
    if ($form_state->getValue(self::WORD_BOUNDARIES)) {
      foreach ($word_list as $word) {
        if (!preg_match('/^\w(.*\w)?$/u', $word)) {
          $form_state->setErrorByName(self::WORDS, $this->t('Search text must begin and end with a letter, number, or underscore to use the %option option.', ['%option' => $this->t('Respect word boundaries')]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([
      self::WORD_LIST => $this->wordsToArray($form_state->getValue(self::WORDS)),
      self::WORD_BOUNDARIES => $form_state->getValue(self::WORD_BOUNDARIES),
      self::EXACT => $form_state->getValue(self::EXACT),
      self::CASE_SENSITIVE => $form_state->getValue(self::CASE_SENSITIVE),
      self::INVERT => $form_state->getValue(self::INVERT),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, ?TamperableItemInterface $item = NULL) {
    $match_func = $this->getFunction();

    $match = FALSE;
    $word_list = $this->getWordList();

    if (is_array($data)) {
      // Set flag that the data is multivalued.
      $this->multiple = is_array($data);

      foreach ($data as $value) {
        if ($this->match($match_func, (string) $value, $word_list)) {
          $match = TRUE;
          break;
        }
      }
      reset($data);
    }
    else {
      $match = $this->match($match_func, (string) $data, $word_list);
    }

    if (!$match && empty($this->getSetting(self::INVERT))) {
      throw new SkipTamperItemException('Item does not contain one of the configured keywords.');
    }

    if ($match && !empty($this->getSetting(self::INVERT))) {
      throw new SkipTamperItemException('Item contains one of the configured keywords.');
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return $this->multiple;
  }

  /**
   * Returns the list of configured keywords to filter on.
   *
   * @return array
   *   A list of keywords.
   */
  protected function getWordList(): array {
    $word_list = $this->getSetting(self::WORD_LIST);
    if (count($word_list) > 0) {
      return $word_list;
    }

    // Get words from old setting for backwards compatibility.
    return $this->wordsToArray($this->getSetting(self::WORDS));
  }

  /**
   * Converts words in a string to an array.
   *
   * @param string $words
   *   The inputted words, as a string.
   *
   * @return array
   *   A list of words.
   */
  protected function wordsToArray(string $words): array {
    if (strlen($words) < 1) {
      return [];
    }
    $words = str_replace("\r", '', $words);
    $word_list = explode("\n", $words);
    $word_list = array_map('trim', $word_list);
    // Remove empty words from the list.
    $word_list = array_filter($word_list);

    return $word_list;
  }

  /**
   * Converts the word to a regular expression based on the settings.
   *
   * @param string $word
   *   The word to create a regex for.
   *
   * @return string
   *   The regular expression.
   *
   * @throws \RuntimeException
   *   In case the word could not be converted to a regular expression.
   */
  protected function getRegex(string $word): string {
    if ($this->getSetting(self::EXACT)) {
      $regex = '/^' . preg_quote($word, '/') . '$/u';
    }
    elseif ($this->getSetting(self::WORD_BOUNDARIES)) {
      // Word boundaries can only match a word with letters at the end.
      if (!preg_match('/^\w(.*\w)?$/u', $word)) {
        throw new \RuntimeException('Search text must begin and end with a letter, number, or underscore when word boundaries should be respected.');
      }
      $regex = '/\b' . preg_quote($word, '/') . '\b/u';
    }
    else {
      // This case can only occur when code from outside this class calls
      // this method.
      $regex = '/' . preg_quote($word, '/') . '/u';
    }
    if (!$this->getSetting(self::CASE_SENSITIVE)) {
      $regex .= 'i';
    }

    return $regex;
  }

  /**
   * Checks if a regular expression is needed based on the settings.
   *
   * @return bool
   *   True if a regular expression is needed. False otherwise.
   */
  protected function needsRegex(): bool {
    return (bool) $this->getSetting(self::WORD_BOUNDARIES) || (bool) $this->getSetting(self::EXACT);
  }

  /**
   * Checks which function should be used for matching.
   *
   * @return callable
   *   A callable function.
   */
  protected function getFunction(): callable {
    if ($this->needsRegex()) {
      return [$this, 'matchRegex'];
    }

    $is_multibyte = (Unicode::getStatus() == Unicode::STATUS_MULTIBYTE) ? TRUE : FALSE;
    if ($this->getSetting(self::CASE_SENSITIVE)) {
      // The text to look for must match uppercase or lowercase characters.
      return $is_multibyte ? 'mb_strpos' : 'strpos';
    }

    // The text search is case-insensitive.
    return $is_multibyte ? 'mb_stripos' : 'stripos';
  }

  /**
   * Determines whether we get a keyword filter match.
   *
   * @param callable $function
   *   The function to call.
   * @param string $field
   *   The source field data.
   * @param array $word_list
   *   The list of words to filter on.
   *
   * @return bool
   *   True if the source contains one of the configured keywords. False
   *   otherwise.
   */
  protected function match(callable $function, string $field, array $word_list): bool {
    foreach ($word_list as $word) {
      if (call_user_func($function, $field, $word) !== FALSE) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Determines whether we get a keyword filter match using regex.
   *
   * @param string $field
   *   The source field data.
   * @param string $word
   *   The word to filter on, passed as a regex pattern.
   *
   * @return bool
   *   True if the source contains the provided word.
   */
  protected function matchRegex(string $field, string $word): bool {
    return preg_match($this->getRegex($word), $field) > 0;
  }

}
