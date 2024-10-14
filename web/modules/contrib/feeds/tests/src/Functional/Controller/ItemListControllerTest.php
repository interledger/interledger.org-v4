<?php

namespace Drupal\Tests\feeds\Functional\Controller;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Lists the feed items belonging to a feed.
 *
 * @group feeds
 */
class ItemListControllerTest extends FeedsBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'feeds',
    'feeds_test_entity',
  ];

  /**
   * Tests listing items for an entity type without a link template.
   */
  public function testListItemsForAnEntityTypeWithoutLinkTemplate() {
    $feed_type = $this->createFeedType([
      'parser' => 'csv',
      'processor' => 'entity:feeds_test_entity_test_no_links',
      'processor_configuration' => [
        'authorize' => FALSE,
      ],
      'custom_sources' => [
        'title' => [
          'label' => 'title',
          'value' => 'title',
          'machine_name' => 'title',
        ],
      ],
      'mappings' => [
        [
          'target' => 'name',
          'map' => ['value' => 'title'],
        ],
      ],
    ]);

    // Import CSV file.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesUrl() . '/csv/content.csv',
    ]);
    $feed->import();

    // Go to the items page and assert that two items are shown there.
    $this->drupalGet('/feed/1/list');
    $this->assertSession()->responseNotContains('The website encountered an unexpected error.');
    $this->assertSession()->responseContains('Lorem ipsum');
    $this->assertSession()->responseContains('Ut wisi enim ad minim veniam');
  }

  /**
   * Tests listing items for an entity where url creation failed.
   *
   * @param string $exception_class
   *   The type of exception to throw.
   * @param string|int $exception_message
   *   The exception message or null.
   * @param bool $display_message
   *   Whether or not a message should be displayed.
   * @param array $args
   *   (optional) The arguments to use for constructing the exception.
   *
   * @dataProvider entityExceptionsProvider
   */
  public function testListItemsForAnEntityTypeWithUrlFailure(string $exception_class, ?string $exception_message, bool $display_message, array $args = []) {
    $feed_type = $this->createFeedType([
      'parser' => 'csv',
      'processor' => 'entity:feeds_test_entity_test_no_url',
      'processor_configuration' => [
        'authorize' => FALSE,
        'values' => [
          'type' => 'feeds_test_entity_test_no_url',
        ],
      ],
      'custom_sources' => [
        'title' => [
          'label' => 'title',
          'value' => 'title',
          'machine_name' => 'title',
        ],
      ],
      'mappings' => [
        [
          'target' => 'name',
          'map' => ['value' => 'title'],
        ],
      ],
    ]);

    // Import CSV file.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesUrl() . '/csv/content.csv',
    ]);
    $feed->import();

    if (count($args) < 1 && is_string($exception_message)) {
      $args[] = $exception_message;
    }
    \Drupal::state()->set('feeds_test_entity_test_no_url.exception', [
      'class' => $exception_class,
      'args' => $args,
    ]);

    // Go to the items page and assert that two items are shown there.
    $this->drupalGet('/feed/1/list');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Lorem ipsum');
    $this->assertSession()->pageTextContains('Ut wisi enim ad minim veniam');
    if (is_string($exception_message)) {
      if ($display_message) {
        $this->assertSession()->pageTextContains($exception_message);
      }
      else {
        $this->assertSession()->pageTextNotContains($exception_message);
      }
    }
  }

  /**
   * Data provider for testListItemsForAnEntityTypeWithUrlFailure().
   */
  public static function entityExceptionsProvider(): array {
    return [
      // A RouteNotFoundException can be thrown for some entity types and is not
      // considered an error.
      [
        'class' => RouteNotFoundException::class,
        'message' => 'No route',
        'display_message' => FALSE,
      ],
      // A MissingMandatoryParametersException can be thrown for some entity
      // types and is not considered an error.
      [
        'class' => MissingMandatoryParametersException::class,
        'message' => 'Some mandatory parameters are missing',
        'display_message' => FALSE,
        'args' => [
          'foo.route',
          [
            'bar',
          ],
        ],
      ],
      // An EntityMalformedException should be considered an error that should
      // be displayed and logged.
      [
        'class' => EntityMalformedException::class,
        'message' => 'The entity is malformed.',
        'display_message' => TRUE,
      ],
      // A RuntimeException should be considered an error that should be
      // displayed and logged. However, in this case there is no message to be
      // shown, so it should only get logged.
      [
        'class' => \RuntimeException::class,
        'message' => NULL,
        'display_message' => FALSE,
      ],
    ];
  }

}
