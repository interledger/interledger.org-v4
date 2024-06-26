<?php

namespace Drupal\Tests\feeds_ex\Unit\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\feeds_ex\Controller\DefaultController;
use Drupal\Tests\feeds_ex\Unit\UnitTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests editing the feed type edit form.
 *
 * @coversDefaultClass \Drupal\feeds_ex\Controller\DefaultController
 * @group feeds_ex
 */
class DefaultControllerTest extends UnitTestBase {

  /**
   * The controller.
   *
   * @var \Drupal\feeds_ex\Controller\DefaultController
   */
  protected $controller;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->controller = new DefaultController();
  }

  /**
   * Tests the autocomplete method.
   *
   * @param string $string
   *   The string entered into the autocomplete.
   * @param array $suggestions
   *   The array of expected suggestions.
   *
   * @covers ::encodingAutocomplete
   *
   * @dataProvider providerTestEncodingAutocompleteResults
   */
  public function testEncodingAutocompleteResults($string, $suggestions) {
    $suggestions = array_map(function ($suggestion) {
      return ['value' => $suggestion, 'label' => Html::escape($suggestion)];
    }, $suggestions);
    $result = $this->controller->encodingAutocomplete(new Request(['q' => $string]));
    $this->assertSame($suggestions, Json::decode($result->getContent()));
  }

  /**
   * Data provider for testEncodingAutocompleteResults().
   *
   * @return array
   *   The encoding autocomplete suggestions.
   */
  public function providerTestEncodingAutocompleteResults(): array {
    $test_parameters = [];
    $test_parameters[] = [
      'string' => 'Asc',
      'suggestions' => [
        'ASCII',
      ],
    ];
    $test_parameters[] = [
      'string' => 'UTF-3',
      'suggestions' => [
        'UTF-32',
        'UTF-32BE',
        'UTF-32LE',
      ],
    ];
    // Max 10 suggestions are returned.
    $test_parameters[] = [
      'string' => 'ISO-8859',
      'suggestions' => [
        'ISO-8859-1',
        'ISO-8859-2',
        'ISO-8859-3',
        'ISO-8859-4',
        'ISO-8859-5',
        'ISO-8859-6',
        'ISO-8859-7',
        'ISO-8859-8',
        'ISO-8859-9',
        'ISO-8859-10',
      ],
    ];
    // There is no Banana encoding.
    $test_parameters[] = [
      'string' => 'Banana',
      'suggestions' => [],
    ];
    return $test_parameters;
  }

}
