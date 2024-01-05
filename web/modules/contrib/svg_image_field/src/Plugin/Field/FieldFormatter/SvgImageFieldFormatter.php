<?php

namespace Drupal\svg_image_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use enshrined\svgSanitize\Sanitizer;

/**
 * Plugin implementation of the 'svg_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "svg_image_field_formatter",
 *   label = @Translation("SVG Image Field formatter"),
 *   field_types = {
 *     "svg_image_field"
 *   }
 * )
 */
class SvgImageFieldFormatter extends FormatterBase implements ContainerFactoryPluginInterface {
  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  public $logger;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
      'inline' => FALSE,
      'apply_dimensions' => TRUE,
      'width' => 25,
      'height' => 25,
      'enable_alt' => TRUE,
      'enable_title' => TRUE,
      'link' => '',
      'force_fill' => FALSE,
      'sanitize' => TRUE,
      'sanitize_remote' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * Link types.
   *
   * @return array
   *   Link type options for formatter setting
   */
  private function getLinkTypes() {
    return [
      'content' => $this->t('Content'),
      'file' => $this->t('File'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['inline'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Output SVG inline'),
      '#default_value' => $this->getSetting('inline'),
      '#description' => $this->t('Check this option if you want to manipulate the SVG image with CSS and JavaScript.
       Notice only trusted users should use fields with this option enabled because of
       <a href="@svg_security_link">inline svg security</a>', ['@svg_security_link' => 'https://www.w3.org/wiki/SVG_Security']),
    ];
    $inline_name = '[settings_edit_form][settings][inline]';
    $form['apply_dimensions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Apply dimensions.'),
      '#default_value' => $this->getSetting('apply_dimensions'),
    ];
    $dimensions_name = '[settings_edit_form][settings][apply_dimensions]';
    $form['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Image width.'),
      '#default_value' => $this->getSetting('width'),
      '#states' => [
        'visible' => [
          ':input[name$="' . $dimensions_name . '"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Image height.'),
      '#default_value' => $this->getSetting('height'),
      '#states' => [
        'visible' => [
          ':input[name$="' . $dimensions_name . '"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['force_fill'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force the fill to currentColor'),
      '#description' => $this->t('This can allow the SVG to inherit coloring from the enclosing tag, such as a link tag.'),
      '#default_value' => $this->getSetting('force_fill'),
      '#states' => [
        'visible' => [
          ':input[name$="' . $inline_name . '"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['sanitize'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sanitize SVG code'),
      '#description' => $this->t('Sanitize the SVG XML code and prevent XSS attacks.'),
      '#default_value' => $this->getSetting('sanitize'),
      '#states' => [
        'visible' => [
          ':input[name$="' . $inline_name . '"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $sanitize_name = '[settings_edit_form][settings][sanitize]';
    $form['sanitize_remote'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sanitize: Remove remote references'),
      '#description' => $this->t('Remove attributes that reference remote files, this will stop HTTP leaks but will add an overhead to the sanitizer.'),
      '#default_value' => $this->getSetting('sanitize_remote'),
      '#states' => [
        'visible' => [
          ':input[name$="' . $inline_name . '"]' => ['checked' => TRUE],
          ':input[name$="' . $sanitize_name . '"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['link'] = [
      '#title' => $this->t('Link image to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('link'),
      '#empty_option' => $this->t('Nothing'),
      '#options' => $this->getLinkTypes(),
    ];
    $form['enable_alt'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable alt attribute.'),
      '#default_value' => $this->getSetting('enable_alt'),
    ];
    $form['enable_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable title attribute.'),
      '#default_value' => $this->getSetting('enable_title'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.
    if ($this->getSetting('inline')) {
      $summary[] = $this->t('Inline SVG');
    }
    if ($this->getSetting('apply_dimensions') && $this->getSetting('width')) {
      $summary[] = $this->t('Image width: @width', ['@width' => $this->getSetting('width')]);
    }
    if ($this->getSetting('apply_dimensions') && $this->getSetting('width')) {
      $summary[] = $this->t('Image height: @height', ['@height' => $this->getSetting('height')]);
    }
    if ($this->getSetting('enable_alt')) {
      $summary[] = $this->t('Alt enabled');
    }
    if ($this->getSetting('enable_title')) {
      $summary[] = $this->t('Title enabled');
    }
    $link_types = $this->getLinkTypes();
    // Display this setting only if image is linked.
    $image_link_setting = $this->getSetting('link');
    if (isset($link_types[$image_link_setting])) {
      $summary[] = $link_types[$image_link_setting];
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $attributes = [];
    if ($this->getSetting('apply_dimensions')) {
      $attributes['width'] = $this->getSetting('width');
      $attributes['height'] = $this->getSetting('height');
    }

    $url = NULL;
    $image_link_setting = $this->getSetting('link');
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $entity = $items->getEntity();
      if ($langcode && $entity->hasTranslation($langcode)) {
        $entity = $entity->getTranslation($langcode);
      }
      if (!$entity->isNew()) {
        $url = $entity->toUrl();
      }
    }

    foreach ($items as $delta => $item) {
      if (!$item->entity) {
        continue;
      }
      $uri = $item->entity->getFileUri();
      if (!is_file($uri) && !$this->isStageFileProxyConfigured()) {
        $this->logger->error('File %file could not be displayed by image formatter because it does not exist on server.', ['%file' => $uri]);
        continue;
      }
      if ($this->getSetting('enable_alt')) {
        $alt = $item->alt;
        if ($alt == '""' || empty($alt)) {
          $alt = '';
        }
        $attributes['alt'] = $alt;
      }
      if ($this->getSetting('enable_title') && !empty($item->title)) {
        $attributes['title'] = $item->title;
      }

      $cache_contexts = [];
      if ($image_link_setting == 'file') {
        // @todo Wrap in file_url_transform_relative(). This is currently
        // impossible. As a work-around, we currently add the 'url.site' cache
        // context to ensure different file URLs are generated for different
        // sites in a multisite setup, including HTTP and HTTPS versions of the
        // same site. Fix in https://www.drupal.org/node/2646744.
        $url = \Drupal::service('file_url_generator')->generate($uri);
        $cache_contexts[] = 'url.site';
      }

      $element = [
        '#theme' => 'svg_image_field_formatter',
        '#attributes' => $attributes,
        '#link_url' => $url,
        '#cache' => [
          'tags' => $item->entity->getCacheTags(),
          'contexts' => $cache_contexts,
        ],
        '#uri' => $uri,
        '#inline' => FALSE,
      ];

      // Set properties based on if the SVG is displayed inline or not.
      if (
        $this->getSetting('inline') &&
        $svg_data = $this->loadInlineSvgData($uri, $attributes)
      ) {
        $element['#inline'] = TRUE;
        $element['#svg_data'] = $svg_data;
      }

      $elements[$delta] = $element;
    }

    return $elements;
  }

  /**
   * Determine if the Stage File Proxy module is enabled and configured.
   *
   * @return bool
   *   True if Stage File Proxy module is enabled and configured.
   */
  protected function isStageFileProxyConfigured() {
    return (
      $this->moduleHandler->moduleExists('stage_file_proxy') &&
      $this->configFactory->get('stage_file_proxy.settings')->get('origin')
    );
  }

  /**
   * Load SVG file data for inline display.
   *
   * @param string $uri
   *   The uri to the SVG file.
   * @param array $attributes
   *   Attributes to set for the SVG html tag.
   *
   * @return string|null
   *   The loaded SVG XML, or null if the file is missing or empty.
   */
  protected function loadInlineSvgData(string $uri, array $attributes): ?string {
    // If the file is missing or has empty contents, temporarily disable
    // inline SVG and instead render an <img> tag containing the URL to the
    // SVG in the src attribute. This approach allows Stage File Proxy to
    // intercept the request and obtain the original SVG file from the origin
    // server. And as long as the Stage File Proxy 'hotlink' option is not
    // enabled, subsequent page loads will render the SVG inline again.
    if (!is_file($uri) || !$svg_file = @file_get_contents($uri)) {
      $this->logger->warning('Inline file %file is missing or empty. Inline display will be disabled and an image tag used instead.', ['%file' => $uri]);
      return NULL;
    }

    $dom = new \DOMDocument();
    libxml_use_internal_errors(TRUE);
    $dom->loadXML($svg_file);

    $element = null;
    if (isset($dom->documentElement)) {
      if ($this->getSetting('force_fill')) {
        $dom->documentElement->setAttribute('fill', 'currentColor');
      }
      if ($this->getSetting('apply_dimensions')) {
        if ($attributes['height']) {
          $dom->documentElement->setAttribute('height', $attributes['height']);
        }
        if ($attributes['width']) {
          $dom->documentElement->setAttribute('width', $attributes['width']);
        }
      }
      $element = $dom->documentElement;
    }
    $svg_data = $dom->saveXML($element);

    if ($this->getSetting('sanitize')) {
      $svgSanitizer = new Sanitizer();
      if ($this->getSetting('sanitize_remote')) {
        $svgSanitizer->removeRemoteReferences(TRUE);
      }
      $svg_data = $svgSanitizer->sanitize($svg_data);
    }

    // Remove the XML declaration.
    $lines = explode("\n", $svg_data, 2);
    if (preg_match('/\<\?xml/', $lines[0])) {
      $svg_data = $lines[1];
    }

    return $svg_data;
  }

  /**
   * Constructs a SvgImageFieldFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    LoggerChannelFactoryInterface $logger,
    ModuleHandlerInterface $module_handler,
    FileUrlGeneratorInterface $file_url_generator,
    ConfigFactoryInterface $config_factory,
  ) {
    $this->logger = $logger->get('svg_image_field');
    $this->moduleHandler = $module_handler;
    $this->fileUrlGenerator = $file_url_generator;
    $this->configFactory = $config_factory;
    parent::__construct($plugin_id, $plugin_definition, $field_definition,
      $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('logger.factory'),
      $container->get('module_handler'),
      $container->get('file_url_generator'),
      $container->get('config.factory'),
    );
  }

}
