<?php

namespace Drupal\Tests\feeds\Functional;

use Drupal\Tests\file\Functional\FileFieldTestBase;
use Drupal\Tests\feeds\Traits\FeedCreationTrait;
use Drupal\Tests\feeds\Traits\FeedsCommonTrait;
use Drupal\feeds\Entity\Feed;
use Drupal\file\Entity\File;

/**
 * Tests private files work with the Feeds module.
 *
 * @group feeds
 */
class PrivateFileTest extends FileFieldTestBase {

  use FeedCreationTrait;
  use FeedsCommonTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'file',
    'file_module_test',
    'field_ui',
    'feeds',
    'options',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // This test expects unused managed files to be marked as a temporary file.
    $this->config('file.settings')
      ->set('make_unused_managed_files_temporary', TRUE)
      ->save();
  }

  /**
   * Tests private files work with the Feeds module.
   *
   * @see feeds_file_download()
   */
  public function testPrivateFile() {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $type_name = 'article';
    $field_name = strtolower($this->randomMachineName());
    $this->createFileField($field_name, 'node', $type_name, ['uri_scheme' => 'private']);

    $test_file = $this->getTestFile('text');
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name, TRUE, ['private' => TRUE]);
    $this->container->get('entity_type.manager')->getStorage('node')->resetCache([$nid]);
    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->load($nid);
    $node_file = File::load($node->{$field_name}->target_id);
    // Ensure the file can be viewed.
    $this->drupalGet('node/' . $node->id());
    // File reference is displayed after attaching it.
    $this->assertSession()->responseContains($node_file->getFilename());
    // Ensure the file can be downloaded.
    $this->drupalGet($node_file->createFileUrl());
    // Confirmed that the generated URL is correct by downloading the shipped
    // file.
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests that uploaded files can be downloaded.
   *
   * @see feeds_file_download()
   */
  public function testDownloadFetcherFile() {
    // Create a user with Feeds admin privileges.
    $admin_user = $this->drupalCreateUser([
      'administer feeds',
      'access feed overview',
    ]);
    $this->drupalLogin($admin_user);

    // Create a feed type using the upload fetcher.
    $feed_type = $this->createFeedType([
      'fetcher' => 'upload',
      'fetcher_configuration' => [
        'allowed_extensions' => 'csv',
        'directory' => 'private://feeds',
      ],
    ]);

    // Create feed and save.
    $source_file = $this->resourcesPath() . '/csv/content.csv';
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'files[plugin_fetcher_source]' => $this->container->get('file_system')->realpath($source_file),
    ];
    $this->drupalGet('feed/add/' . $feed_type->id());
    $this->submitForm($edit, 'Save');

    // Load feed.
    $feed = Feed::load(1);

    // Generate file link.
    $uri = $this->container->get('file_url_generator')->generateAbsoluteString($feed->getSource());

    // Download the file and assert it is the same as the one uploaded. In the
    // comparison, the data is trimmed because additional whitespace exists in
    // the file that gets uploaded.
    $this->drupalGet($uri);
    $this->assertSession()->statusCodeEquals(200);
    $expected = trim(file_get_contents($source_file));
    $actual = trim($this->getSession()->getPage()->getContent());
    $this->assertSame($expected, $actual, 'The uploaded file does not match with the source file.');
  }

}
