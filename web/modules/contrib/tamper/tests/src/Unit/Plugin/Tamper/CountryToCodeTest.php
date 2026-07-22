<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\tamper\Plugin\Tamper\CountryToCode;

/**
 * Tests the country to code plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\CountryToCode
 * @group tamper
 */
class CountryToCodeTest extends TamperPluginTestBase {

  /**
   * The mock country manager object.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManagerMock;

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    $config = [
      'source_definition' => $this->getMockSourceDefinition(),
    ];
    return new CountryToCode($config, 'country_to_code', [], $this->countryManagerMock);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Mock up Country Manager.
    $this->countryManagerMock = $this->createMock(CountryManagerInterface::class);
    $this->countryManagerMock->expects($this->any())
      ->method('getList')
      ->willReturn([
        'AG' => 'Antigua and Barbuda',
        'CA' => 'Canada',
        'US' => 'United States of America',
      ]);

    parent::setUp();
  }

  /**
   * Test with country received in title case.
   */
  public function testCountryCodeTitleCaseCountry() {
    $this->assertEquals('CA', $this->plugin->tamper('Canada'));
  }

  /**
   * Test with country received in upper case.
   */
  public function testCountryCodeUpperCaseCountry() {
    $this->assertEquals('US', $this->plugin->tamper('UNITED STATES OF AMERICA'));
  }

  /**
   * Test with country received in lower case.
   */
  public function testCountryCodeLowerCaseCountry() {
    $this->assertEquals('AG', $this->plugin->tamper('antigua and barbuda'));
  }

}
