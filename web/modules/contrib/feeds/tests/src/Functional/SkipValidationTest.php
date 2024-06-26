<?php

namespace Drupal\Tests\feeds\Functional;

use Drupal\Component\Utility\Xss;
use Drupal\node\Entity\Node;

/**
 * Tests skip validation.
 *
 * @group feeds
 */
class SkipValidationTest extends FeedsBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'feeds',
    'node',
    'user',
    'file',
  ];

  /**
   * Tests that entity validation can get skipped.
   *
   * By default, validation is not skipped, which is tested first. Later in the
   * test, the option "skip_validation" gets enabled and this should result into
   * Feeds ignoring any validation errors.
   */
  public function testSkipValidation() {
    $this->createFieldWithStorage('field_alpha', [
      'field' => [
        'required' => TRUE,
      ],
    ]);

    // Create and configure a feed type.
    $feed_type = $this->createFeedType([
      'fetcher' => 'upload',
      'fetcher_configuration' => [
        'allowed_extensions' => 'csv',
      ],
      'parser' => 'csv',
      'custom_sources' => [
        'guid' => [
          'label' => 'guid',
          'value' => 'guid',
          'machine_name' => 'guid',
        ],
        'title' => [
          'label' => 'title',
          'value' => 'title',
          'machine_name' => 'title',
        ],
        'alpha' => [
          'label' => 'alpha',
          'value' => 'alpha',
          'machine_name' => 'alpha',
        ],
      ],
      'mappings' => array_merge($this->getDefaultMappings(), [
        [
          'target' => 'field_alpha',
          'map' => ['value' => 'alpha'],
        ],
      ]),
      'processor_configuration' => [
        'authorize' => FALSE,
        'skip_validation' => FALSE,
        'values' => [
          'type' => 'article',
        ],
      ],
    ]);

    // Import CSV file.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->copyResourceFileToDir('csv/content-skip-validation.csv'),
    ]);
    $this->batchImport($feed);
    // Check that 2 of the 3 items were created.
    $page_text = Xss::filter($this->getSession()->getPage()->getContent(), []);
    $this->assertStringContainsString('Created 2 Article items.', $page_text);
    $this->assertStringContainsString('Failed importing 1 Article.', $page_text);
    $this->assertStringContainsString('The content Lorem ipsum failed to validate', $page_text);
    $this->assertStringContainsString('field_alpha label (field_alpha): This value should not be null.', $page_text);

    // Set 'skip_validation' setting to 'TRUE'.
    $config = $feed_type->getProcessor()->getConfiguration();
    $config['skip_validation'] = TRUE;
    $feed_type->getProcessor()->setConfiguration($config);
    $feed_type->save();

    // Import feed again.
    $this->batchImport($feed);

    // Check that the previously failed item was created.
    $page_text = Xss::filter($this->getSession()->getPage()->getContent(), []);
    $this->assertStringContainsString('Created 1 Article.', $page_text);
    $this->assertStringNotContainsString('The content Lorem ipsum failed to validate', $page_text);
  }

  /**
   * Tests skipping only the validation type "Length".
   *
   * We make sure that an other violation occurs by making a field required and
   * then provide no value for it. Because we're configuring the feed type to
   * only skip the validation type "Length", other validation types should still
   * be honored.
   */
  public function testSkipValidationTypesLength() {
    $this->createFieldWithStorage('field_alpha', [
      'field' => [
        'required' => TRUE,
      ],
      'storage' => [
        'settings' => [
          'max_length' => 3,
        ],
      ],
    ]);

    // Create and configure a feed type.
    $feed_type = $this->createFeedType([
      'parser' => 'csv',
      'custom_sources' => [
        'guid' => [
          'label' => 'guid',
          'value' => 'guid',
          'machine_name' => 'guid',
        ],
        'title' => [
          'label' => 'title',
          'value' => 'title',
          'machine_name' => 'title',
        ],
        'alpha' => [
          'label' => 'alpha',
          'value' => 'alpha',
          'machine_name' => 'alpha',
        ],
      ],
      'mappings' => array_merge($this->getDefaultMappings(), [
        [
          'target' => 'field_alpha',
          'map' => ['value' => 'alpha'],
        ],
      ]),
      'processor_configuration' => [
        'authorize' => FALSE,
        'skip_validation' => TRUE,
        'skip_validation_types' => ['Length'],
        'values' => [
          'type' => 'article',
        ],
      ],
    ]);

    // Import CSV file.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesUrl() . '/csv/content-skip-validation.csv',
    ]);
    $this->batchImport($feed);

    $page_text = Xss::filter($this->getSession()->getPage()->getContent(), []);
    // Assert that one failure is caused by a SQL error: one item contains a
    // value for field "field_alpha" that is longer than the maximum length of
    // that field (which is "3"). A violation of the length of a value is
    // ignored and this results into a SQL error instead.
    // An other item should fail because the field "field_alpha" is required and
    // we don't skip that validation.
    $this->assertStringContainsString('SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column', $page_text);
    $this->assertStringContainsString('The content Lorem ipsum failed to validate', $page_text);
    $this->assertStringContainsString('field_alpha label (field_alpha): This value should not be null.', $page_text);
    $this->assertStringContainsString('Failed importing 2 Article items.', $page_text);
    // Check that "Ut wisi enim ad minim veniam" was created. That item has the
    // exact maximum length for the field "field_alpha" and therefore it should
    // pass both validation and avoid SQL errors.
    $this->assertStringContainsString('Created 1 Article.', $page_text);
    $node = Node::load(1);
    $this->assertEquals('Ut wisi enim ad minim veniam', $node->title->value);
  }

}
