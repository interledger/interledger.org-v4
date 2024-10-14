<?php

namespace Drupal\Tests\feeds\Traits;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\feeds\FeedInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Provides methods useful for Kernel and Functional Feeds tests.
 *
 * This trait is meant to be used only by test classes.
 */
trait FeedsCommonTrait {

  /**
   * The node type to test with.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodeType;

  /**
   * Creates a default node type called 'article'.
   */
  protected function setUpNodeType() {
    // Create a content type.
    $this->nodeType = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $this->nodeType->save();
  }

  /**
   * Creates a new node with a feeds item field.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed with which the node was imported.
   * @param array $settings
   *   (optional) An associative array of settings for the node.
   *
   * @return \Drupal\node\NodeInterface
   *   The created node entity.
   */
  protected function createNodeWithFeedsItem(FeedInterface $feed, array $settings = []) {
    $settings += [
      'title'  => $this->randomMachineName(8),
      'type'  => 'article',
      'uid'  => 0,
      'feeds_item' => [
        'target_id' => $feed->id(),
        'imported' => 0,
        'guid' => 1,
        'hash' => static::randomString(),
      ],
    ];
    $node = Node::create($settings);
    $node->save();

    return $node;
  }

  /**
   * Creates a field and an associated field storage.
   *
   * @param string $field_name
   *   The name of the field.
   * @param array $config
   *   (optional) The field storage and instance configuration:
   *   - entity_type: (optional) the field's entity type. Defaults to 'node'.
   *   - bundle: (optional) the field's bundle. Defaults to 'article'.
   *   - type: (optional) the field's type. Defaults to 'text'.
   *   - label: (optional) the field's label. Defaults to the field's name +
   *     the string ' label'.
   *   - storage: (optional) additional keys for the field's storage.
   *   - field: (optional) additional keys for the field.
   */
  protected function createFieldWithStorage($field_name, array $config = []) {
    $config += [
      'entity_type' => 'node',
      'bundle' => 'article',
      'type' => 'text',
      'label' => $field_name . ' label',
      'storage' => [],
      'field' => [],
    ];

    FieldStorageConfig::create($config['storage'] + [
      'field_name' => $field_name,
      'entity_type' => $config['entity_type'],
      'type' => $config['type'],
      'settings' => [],
    ])->save();

    FieldConfig::create($config['field'] + [
      'entity_type' => $config['entity_type'],
      'bundle' => $config['bundle'],
      'field_name' => $field_name,
      'label' => $config['label'],
    ])->save();
  }

  /**
   * Moves a file from the resources directory to a public or private directory.
   *
   * This method is useful in combination with the upload fetcher.
   *
   * @param string $file
   *   The file to move.
   * @param string $dir
   *   (optional) The directory to move to. Defaults to 'public://feeds'.
   *
   * @return string
   *   The location to where the file was saved.
   */
  protected function copyResourceFileToDir(string $file, string $dir = NULL): string {
    $file_system = $this->container->get('file_system');

    if (is_null($dir)) {
      $dir = 'public://feeds';
    }
    $upload_destination = $dir . '/' . basename($file);

    $file_system->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);
    $file_system->saveData(file_get_contents($this->resourcesPath() . '/' . $file), $upload_destination);
    return $upload_destination;
  }

  /**
   * Reloads an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to reload.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The reloaded entity.
   */
  protected function reloadEntity(EntityInterface $entity): EntityInterface {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage($entity->getEntityTypeId());
    $storage->resetCache([$entity->id()]);
    return $storage->load($entity->id());
  }

  /**
   * Reloads an entity where null is an allowed return value.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to reload.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The reloaded entity or null, if the entity could not be found.
   */
  protected function reloadEntityAllowNull(EntityInterface $entity): ?EntityInterface {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage($entity->getEntityTypeId());
    $storage->resetCache([$entity->id()]);
    return $storage->load($entity->id());
  }

  /**
   * Asserts that the given number of nodes exist.
   *
   * @param int $expected_node_count
   *   The expected number of nodes in the node table.
   * @param string $message
   *   (optional) The message to assert.
   */
  protected function assertNodeCount($expected_node_count, $message = '') {
    if (!$message) {
      $message = '@expected nodes have been created (actual: @count).';
    }

    $node_count = $this->container->get('database')
      ->select('node')
      ->fields('node', [])
      ->countQuery()
      ->execute()
      ->fetchField();
    static::assertEquals($expected_node_count, $node_count, strtr($message, [
      '@expected' => $expected_node_count,
      '@count' => $node_count,
    ]));
  }

  /**
   * Asserts that the given number of terms exist.
   *
   * @param int $expected_term_count
   *   The expected number of terms in the taxonomy_term_data table.
   * @param string $message
   *   (optional) The message to assert.
   */
  protected function assertTermCount($expected_term_count, $message = '') {
    if (!$message) {
      $message = '@expected terms have been created (actual: @count).';
    }

    $term_count = $this->container->get('database')
      ->select('taxonomy_term_data')
      ->fields('taxonomy_term_data', [])
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEquals($expected_term_count, $term_count, strtr($message, [
      '@expected' => $expected_term_count,
      '@count' => $term_count,
    ]));
  }

  /**
   * Asserts that the given number of queue items exist for the specified queue.
   *
   * @param int $expected
   *   The expected number of queue items.
   * @param string $queue_name
   *   The queue to inspect the number of items for.
   * @param string $message
   *   (optional) The message to assert.
   */
  protected function assertQueueItemCount(int $expected, string $queue_name, string $message = '') {
    if (!$message) {
      $message = '@expected queue items exist on @queue (actual: @count).';
    }

    $queue = $this->container->get('queue')->get($queue_name);
    $item_count = $queue->numberOfItems();
    $this->assertEquals($expected, $item_count, strtr($message, [
      '@expected' => $expected,
      '@queue' => $queue_name,
      '@count' => $item_count,
    ]));
  }

  /**
   * Returns the absolute path to the Drupal root.
   *
   * @return string
   *   The absolute path to the directory where Drupal is installed.
   */
  protected function absolute() {
    return realpath(getcwd());
  }

  /**
   * Returns the absolute directory path of the Feeds module.
   *
   * @return string
   *   The absolute path to the Feeds module.
   */
  protected function absolutePath() {
    return $this->absolute() . '/' . $this->getModulePath('feeds');
  }

  /**
   * Returns the base url of the Drupal installation.
   *
   * @return string
   *   The Drupal base url.
   */
  protected function getBaseUrl(): string {
    return \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBaseUrl();
  }

  /**
   * Returns the url to the Feeds resources directory.
   *
   * @return string
   *   The url to the Feeds resources directory.
   */
  protected function resourcesUrl(): string {
    return $this->getBaseUrl() . '/' . $this->getModulePath('feeds') . '/tests/resources';
  }

  /**
   * Gets the path for the specified module.
   *
   * @param string $module_name
   *   The module name.
   *
   * @return string
   *   The Drupal-root relative path to the module directory.
   *
   * @throws \Drupal\Core\Extension\Exception\UnknownExtensionException
   *   If the module does not exist.
   */
  protected function getModulePath(string $module_name): string {
    return \Drupal::service('extension.list.module')->getPath($module_name);
  }

  /**
   * Returns the absolute directory path of the resources folder.
   *
   * @return string
   *   The absolute path to the resources folder.
   */
  protected function resourcesPath() {
    return $this->absolutePath() . '/tests/resources';
  }

  /**
   * Runs all items from one queue.
   *
   * @param string $queue_name
   *   The name of the queue to run all items from.
   */
  protected function runCompleteQueue($queue_name) {
    // Create queue.
    $queue = \Drupal::service('queue')->get($queue_name);
    $queue->createQueue();
    $queue_worker = \Drupal::service('plugin.manager.queue_worker')->createInstance($queue_name);

    // Process all items of queue.
    while ($item = $queue->claimItem()) {
      $queue_worker->processItem($item->data);
      $queue->deleteItem($item);
    }
  }

  /**
   * Runs specified number of items from one queue.
   *
   * @param string $queue_name
   *   The name of the queue to run all items from.
   * @param int $number
   *   The number of items to process from the queue.
   */
  protected function runQueue(string $queue_name, int $number) {
    // Create queue.
    $queue = $this->container->get('queue')->get($queue_name);
    $queue->createQueue();
    $queue_worker = $this->container->get('plugin.manager.queue_worker')->createInstance($queue_name);

    // Process all items of the queue.
    for ($i = 0; $i < $number; $i++) {
      $item = $queue->claimItem();
      $queue_worker->processItem($item->data);
      $queue->deleteItem($item);
    }
  }

  /**
   * Prints messages useful for debugging.
   */
  protected function printMessages() {
    $messages = \Drupal::messenger()->all();
    print_r($messages);
  }

}
