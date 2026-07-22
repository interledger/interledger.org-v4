<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tamper\Attribute\Tamper;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\ItemUsage;
use Drupal\tamper\TamperBase;
use Drupal\tamper\TamperableItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Plugin implementation for converting country to ISO code.
 */
#[Tamper(
  id: 'country_to_code',
  label: new TranslatableMarkup('Country to ISO code'),
  description: new TranslatableMarkup('Converts this field from a country name string to the two character ISO 3166-1 alpha-2 code.'),
  category: new TranslatableMarkup('Text'),
  itemUsage: ItemUsage::IGNORED,
)]
class CountryToCode extends TamperBase {

  /**
   * Holds the CountryManager object so we can grab the country list.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManager;

  /**
   * Constructs a CountryToCode plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Locale\CountryManagerInterface $country_manager
   *   The country manager used to grab the country list.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    #[Autowire(service: 'country_manager')]
    CountryManagerInterface $country_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->countryManager = $country_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, ?TamperableItemInterface $item = NULL) {
    // Don't process empty or null values.
    if (is_null($data) || $data === '') {
      return $data;
    }

    if (!is_string($data)) {
      throw new TamperException('Input should be a string.');
    }

    /**
     * Holds the list of countries in an array.
     * @static
     */
    static $countries = [];

    if (empty($countries)) {
      $countries = $this->countryManager->getList();
      foreach ($countries as $country_code => $country_name) {
        $countries[$country_code] = mb_strtolower((string) $country_name);
      }
      $countries = array_flip($countries);
    }

    // If it's already a valid country code, leave it alone.
    if (in_array($data, $countries)) {
      return $data;
    }

    // Trim whitespace, set to lowercase.
    $country = mb_strtolower(trim($data));
    if (isset($countries[$country])) {
      return $countries[$country];
    }
    else {
      throw new TamperException('Could not find country name ' . $country . ' in list of countries.');
    }
  }

  /**
   * Setter function for the CountryManagerInterface.
   *
   * @param object $country_manager
   *   The country manager used to grab country list.
   */
  public function setCountryManager($country_manager) {
    $this->countryManager = $country_manager;
  }

}
