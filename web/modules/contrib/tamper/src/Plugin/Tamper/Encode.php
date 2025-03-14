<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperBase;
use Drupal\tamper\TamperableItemInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Plugin implementation for encoding / decoding.
 *
 * @Tamper(
 *   id = "encode",
 *   label = @Translation("Encode/Decode"),
 *   description = @Translation("Encode (or Decode) the field contents."),
 *   category = "Text",
 *   handle_multiples = TRUE
 * )
 */
class Encode extends TamperBase {

  const SETTING_MODE = 'mode';

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
    $config[self::SETTING_MODE] = 'serialize';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $modes = $this->getModes();

    $form[self::SETTING_MODE] = [
      '#type' => 'radios',
      '#title' => $this->t('Conversion mode:'),
      '#options' => array_combine(array_keys($modes), array_column($modes, 'label')),
      '#default_value' => $this->getSetting(self::SETTING_MODE),
    ];

    foreach ($modes as $key => $mode) {
      if (isset($mode['description'])) {
        $form[self::SETTING_MODE][$key]['#description'] = $mode['description'];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([self::SETTING_MODE => $form_state->getValue(self::SETTING_MODE)]);
  }

  /**
   * Returns a list of encode/decode modes.
   *
   * @return array
   *   A list of encode modes. The keys of the array are the machine names of
   *   the modes, and the values are an associative array with the following
   *   keys:
   *   - label: (string) the label of the mode.
   *   - description: (string) a short description about the encode mode.
   *   - callback: (callable) the function or method to call for doing the
   *     encode/decode operation.
   *   - handle_multiples: (bool) whether or not the encode mode can handle
   *     array values. If TRUE, the data is passed to the encode/decode function
   *     as is. If FALSE and if the data is an array, their array values are
   *     iterated over.
   */
  protected function getModes(): array {
    return [
      'serialize' => [
        'label' => $this->t('PHP Serialize'),
        'description' => $this->t('Generates a storable representation of a value.'),
        'callback' => 'serialize',
        'handle_multiples' => TRUE,
      ],
      'unserialize' => [
        'label' => $this->t('PHP Unserialize'),
        'description' => $this->t('Creates a PHP value from a stored representation.'),
        'callback' => 'unserialize',
        'handle_multiples' => FALSE,
      ],
      'json_encode' => [
        'label' => $this->t('Json Encode'),
        'description' => $this->t('Creates the JSON representation of a value.'),
        'callback' => [Json::class, 'encode'],
        'handle_multiples' => TRUE,
      ],
      'json_decode' => [
        'label' => $this->t('Json Decode'),
        'description' => $this->t('Takes a JSON encoded string and converts it into a PHP value.'),
        'callback' => [Json::class, 'decode'],
        'handle_multiples' => FALSE,
      ],
      'base64_encode' => [
        'label' => $this->t('Base64 Encode'),
        'description' => $this->t('Encodes data with MIME base64.'),
        'callback' => 'base64_encode',
        'handle_multiples' => FALSE,
      ],
      'base64_decode' => [
        'label' => $this->t('Base64 Decode'),
        'description' => $this->t('Decodes data encoded with MIME base64.'),
        'callback' => 'base64_decode',
        'handle_multiples' => FALSE,
      ],
      'yaml_encode' => [
        'label' => $this->t('YAML Encode'),
        'description' => $this->t('Creates the YAML representation of a value.'),
        'callback' => [Yaml::class, 'dump'],
        'handle_multiples' => TRUE,
      ],
      'yaml_decode' => [
        'label' => $this->t('YAML Decode'),
        'description' => $this->t('Takes a YAML encoded string and converts it into a PHP value.'),
        'callback' => [Yaml::class, 'parse'],
        'handle_multiples' => FALSE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, ?TamperableItemInterface $item = NULL) {
    $modes = $this->getModes();
    $selected_mode = $this->getSetting(self::SETTING_MODE);

    if (!isset($modes[$selected_mode])) {
      throw new TamperException(sprintf('The selected encode mode "%s" is invalid.', $selected_mode));
    }

    // Check if the selected mode supports arrays.
    $handles_multiples = $modes[$selected_mode]['handle_multiples'] ?? FALSE;
    if (!$handles_multiples && is_array($data)) {
      // Iterate over all values.
      foreach ($data as $key => $subvalues) {
        $data[$key] = $this->applyEncode($subvalues, $modes[$selected_mode], $selected_mode);
      }
    }
    else {
      $data = $this->applyEncode($data, $modes[$selected_mode], $selected_mode);
    }

    // Set flag for if the data returned is an array (and therefore multiple
    // values).
    $this->multiple = is_array($data);

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return $this->multiple;
  }

  /**
   * Applies the encoding.
   *
   * @param mixed $data
   *   The data to encode or decode.
   * @param array $mode
   *   The selected mode to apply.
   * @param string $selected_mode
   *   The selected mode.
   *
   * @return mixed
   *   The encoded or decoded data.
   *
   * @throws \LogicException
   *   In case no callback for the selected mode is defined.
   */
  protected function applyEncode($data, array $mode, string $selected_mode) {
    // All modes in ::getModes() should have a callback defined.
    if (!isset($mode['callback']) || !is_callable($mode['callback'])) {
      throw new \LogicException(sprintf('The encoding could not be applied because the selected mode "%s" has no valid callback.', $selected_mode));
    }

    return call_user_func($mode['callback'], $data);
  }

}
