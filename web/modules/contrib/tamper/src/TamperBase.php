<?php

namespace Drupal\tamper;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\AutowiringFailedException;

/**
 * Provides a base class to tamper data from.
 */
abstract class TamperBase extends PluginBase implements TamperInterface, ItemUsageInterface, ContainerFactoryPluginInterface {

  use ItemUsageTrait;

  /**
   * The source definition.
   *
   * @var \Drupal\tamper\SourceDefinitionInterface
   */
  protected $sourceDefinition;

  /**
   * Constructs a TamperBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\tamper\SourceDefinitionInterface|null $source_definition
   *   (optional) A definition of which sources there are that Tamper plugins
   *   can use. When omitted, the source definition is read from
   *   $configuration['source_definition'].
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ?SourceDefinitionInterface $source_definition = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $source_definition ??= $configuration['source_definition'] ?? NULL;
    if (!$source_definition instanceof SourceDefinitionInterface) {
      throw new \InvalidArgumentException("Missing source definition: pass it either as constructor argument or set it on the 'source_definition' key of \$configuration.");
    }

    $this->sourceDefinition = $source_definition;
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   *
   * Instantiates the plugin with autowired constructor arguments.
   *
   * Constructor parameters after $plugin_definition are resolved from the
   * container, based on their type or an explicit #[Autowire] attribute.
   * Parameters expecting a source definition are resolved from
   * $configuration['source_definition'] instead, as the source definition is
   * not a service.
   *
   * This replicates \Drupal\Core\DependencyInjection\AutowiredInstanceTrait,
   * which is only available since Drupal core 11.3.
   *
   * @todo Remove this method and rely on PluginBase::create() once the
   *   minimum supported core version is 11.3.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $args = [$configuration, $plugin_id, $plugin_definition];

    $constructor = new \ReflectionMethod(static::class, '__construct');
    foreach (array_slice($constructor->getParameters(), count($args)) as $parameter) {
      $service = ltrim((string) $parameter->getType(), '?');

      // The source definition is not a service: it gets passed in through
      // the plugin configuration.
      if (is_a($service, SourceDefinitionInterface::class, TRUE)) {
        $args[] = $configuration['source_definition'] ?? NULL;
        continue;
      }

      foreach ($parameter->getAttributes(Autowire::class) as $attribute) {
        $service = (string) $attribute->newInstance()->value;
      }

      if (!$container->has($service)) {
        if ($parameter->allowsNull()) {
          $args[] = NULL;
          continue;
        }
        throw new AutowiringFailedException($service, sprintf('Cannot autowire service "%s": argument "$%s" of method "%s::__construct()", you should configure its value explicitly.', $service, $parameter->getName(), static::class));
      }

      $args[] = $container->get($service);
    }

    // Calling new static() is safe here: constructor arguments are resolved
    // through reflection above, so subclasses may change the constructor
    // signature. And since this is an abstract class, create() can only get
    // called on (instantiable) subclasses.
    // @phpstan-ignore-next-line
    return new static(...$args);
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key) {
    return $this->configuration[$key] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    // Ignore source definition from configuration as that shouldn't be stored
    // on config files.
    unset($configuration['source_definition']);

    // Merge with default configuration.
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

}
