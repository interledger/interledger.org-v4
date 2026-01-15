<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation for rendering using Twig.
 *
 * Note: itemUsage is set to "required", but the plugin won't throw an exception
 * if no item is passed. It just doesn't do anything meaningful without it.
 *
 * @Tamper(
 *   id = "twig",
 *   label = @Translation("Twig"),
 *   description = @Translation("Rewrite a field using twig."),
 *   category = @Translation("Other"),
 *   itemUsage = "required"
 * )
 */
class Twig extends TamperBase implements ContainerFactoryPluginInterface {

  const SETTING_TEMPLATE = 'template';

  /**
   * The twig variable to use for the current data value.
   */
  const TWIG_DATA_VARIABLE = '_tamper_data';

  /**
   * The twig variable to use for the current tamper item.
   */
  const TWIG_ITEM_VARIABLE = '_tamper_item';

  /**
   * The twig environment.
   *
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected $twigEnvironment;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition, $configuration['source_definition']);
    $instance->setTwigEnvironment($container->get('twig'));

    return $instance;
  }

  /**
   * Specifies the current twig environment.
   *
   * @param \Drupal\Core\Template\TwigEnvironment $twig_environment
   *   The current twig environment.
   */
  public function setTwigEnvironment(TwigEnvironment $twig_environment) {
    $this->twigEnvironment = $twig_environment;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[static::SETTING_TEMPLATE] = '';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[static::SETTING_TEMPLATE] = [
      '#type' => 'textarea',
      '#title' => $this->t('Twig template'),
      '#default_value' => $this->getSetting(static::SETTING_TEMPLATE),
    ];

    $tokens = [
      static::TWIG_DATA_VARIABLE => $this->t('{{ @token }} - @label', [
        '@token' => static::TWIG_DATA_VARIABLE,
        '@label' => $this->t('Current data'),
      ]),
      static::TWIG_ITEM_VARIABLE => $this->t('{{ @token }} - @label', [
        '@token' => static::TWIG_ITEM_VARIABLE,
        '@label' => $this->t('Current item object'),
      ]),
    ];
    foreach ($this->sourceDefinition->getList() as $name => $label) {
      if (!$name) {
        continue;
      }
      $tokens[] = $this->t('{{ @token }} - @label', [
        '@token' => $this->convertToTwigToken($name),
        '@label' => $label,
      ]);
    }

    $form['help'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Available replacement patterns'),
      'list' => [
        '#theme' => 'item_list',
        '#items' => $tokens,
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
      static::SETTING_TEMPLATE => $form_state->getValue(static::SETTING_TEMPLATE),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getUsedSourceProperties(TamperableItemInterface $item): array {
    $template = (string) $this->getSetting(static::SETTING_TEMPLATE);

    $used = $this->extractTwigVariables($template);

    // Match calls like _item.getSourceProperty('foo').
    if (preg_match_all('/getSourceProperty\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $template, $matches)) {
      foreach ($matches[1] as $property) {
        $used[] = $property;
      }
    }

    return array_values(array_unique($used));
  }

  /**
   * Extracts all Twig variables used in the template.
   *
   * @param string $template
   *   The twig template.
   *
   * @return array
   *   A list of used variables.
   */
  protected function extractTwigVariables(string $template): array {
    $variables = [];

    // Twig keywords and common filters to exclude.
    $excludedWords = [
      'if', 'else', 'endif', 'and', 'or', 'not', 'in', 'true', 'false', 'null',
      'upper', 'lower', 'capitalize', 'length', 'trim', 'default', 'join',
      'replace', 'abs', 'round', 'batch', 'first', 'last', 'slice', 'merge',
      'reverse',
      static::TWIG_DATA_VARIABLE,
      static::TWIG_ITEM_VARIABLE,
      '_item',
      '_context',
    ];

    // Extract all {{ ... }} and {% ... %} expressions.
    preg_match_all('/\{\{\s*(.*?)\s*\}\}|\{\%\s*(.*?)\s*\%\}/s', $template, $matches);

    // Combine inner parts of {{ ... }} and {% ... %}.
    $expressions = array_merge($matches[1], $matches[2]);

    foreach ($expressions as $expr) {
      if (empty($expr)) {
        continue;
      }

      // Remove quoted strings to avoid capturing them as variables.
      $expr = preg_replace('/(["\']).*?\1/', '', $expr);

      // Match all variable-like tokens (chains with letters, digits,
      // underscores, dots, pipes).
      preg_match_all('/[a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z0-9_]+)*(?:\|[a-zA-Z0-9_]+)*/', $expr, $tokens);

      foreach ($tokens[0] as $token) {
        // For each token, split by '.' and '|', take the first part as the root
        // variable.
        $root = preg_split('/[.\|]/', $token)[0];

        // Filter out keywords and filters.
        if (!in_array(strtolower($root), $excludedWords, TRUE)) {
          $variables[] = $root;
        }
      }
    }

    // Return unique variables with reset keys.
    return array_values(array_unique($variables));
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, ?TamperableItemInterface $item = NULL) {
    if (is_null($item)) {
      // Nothing to rewrite.
      return $data;
    }

    $context = [];

    foreach ($item->getSource() as $key => $value) {
      if (!$key) {
        continue;
      }
      $context[$key] = is_array($value) ? reset($value) : $value;
    }

    $template = $this->getSetting(static::SETTING_TEMPLATE);
    return (string) $this->twigEnvironment->renderInline($template, [
      static::TWIG_DATA_VARIABLE => $data,
      // For more advanced interaction with the data item object.
      static::TWIG_ITEM_VARIABLE => $item,
    ] + $context);
  }

  /**
   * Converts a given variable name to an accessible Twig token.
   */
  public function convertToTwigToken(string $variable): string {
    if (
      (
        is_numeric($variable) ||
        preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $variable) === 1
      ) &&
      // Certain variable names are reserved, so can't be accessed directly.
      !in_array($variable, [static::TWIG_DATA_VARIABLE, static::TWIG_ITEM_VARIABLE, '_context'], TRUE)
    ) {
      return $variable;
    }

    // If the variable name is not a valid Twig variable name, then access it
    // through the source item.
    return sprintf("%s.getSourceProperty('%s')", static::TWIG_ITEM_VARIABLE, str_replace("'", "\'", $variable));
  }

}
