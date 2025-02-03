<?php

namespace Drupal\feeds\Feeds\State;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\feeds\State;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * State for the clean stage.
 */
class CleanState extends State implements CleanStateInterface {

  use DependencySerializationTrait;

  /**
   * The database table name.
   */
  const TABLE_NAME = 'feeds_clean_list';

  /**
   * The number of Feed items cleaned.
   *
   * @var int
   */
  public $cleaned = 0;

  /**
   * The ID of the feed this state belongs to.
   *
   * @var int
   */
  protected $feedId;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Whether or not the list was initiated or not.
   *
   * @var bool
   */
  protected $initiated = FALSE;

  /**
   * The type of the entity ID's on the list.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Constructs a new CleanState object.
   *
   * @param int $feed_id
   *   The ID of the feed that this state belongs to.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger for the feeds channel.
   * @param \Drupal\Core\Database\Connection $connection
   *   The Connection object containing the feeds tables.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(int $feed_id, MessengerInterface $messenger, LoggerInterface $logger, Connection $connection, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($messenger, $logger);
    $this->feedId = $feed_id;
    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, int $feed_id) {
    return new static(
      $feed_id,
      $container->get('messenger'),
      $container->get('logger.factory')->get('feeds'),
      $container->get('database'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function progress($total, $progress) {
    if (!$this->count()) {
      $this->setCompleted();
    }
    return parent::progress($total, $progress);
  }

  /**
   * {@inheritdoc}
   */
  public function initiated() {
    return $this->initiated;
  }

  /**
   * {@inheritdoc}
   */
  public function setList(array $ids) {
    // Remove previous list first.
    $this->connection->delete(static::TABLE_NAME)
      ->condition('feed_id', $this->feedId)
      ->execute();

    // Insert the list into the database.
    if (!empty($ids)) {
      $query = $this->connection->insert(static::TABLE_NAME)
        ->fields(['feed_id', 'entity_id']);
      foreach ($ids as $id) {
        $query->values([
          'feed_id' => $this->feedId,
          'entity_id' => $id,
        ]);
      }
      $query->execute();
    }

    // Set flag that initiating is done.
    $this->initiated = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getList() {
    // Get all ID's.
    return $this->connection->select(static::TABLE_NAME)
      ->fields(static::TABLE_NAME, ['entity_id'])
      ->condition('feed_id', $this->feedId)
      ->execute()
      ->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function removeItem($entity_id) {
    $this->connection->delete(static::TABLE_NAME)
      ->condition('feed_id', $this->feedId)
      ->condition('entity_id', $entity_id)
      ->execute();

    $this->total = $this->count();
    $this->progress($this->total, $this->cleaned);
  }

  /**
   * {@inheritdoc}
   */
  public function nextEntity(?EntityStorageInterface $storage = NULL) {
    if (!$this->initiated()) {
      return;
    }

    $entity_id = $this->connection->queryRange('SELECT entity_id FROM {' . static::TABLE_NAME . '} WHERE feed_id = :feed_id', 0, 1, [':feed_id' => $this->feedId])
      ->fetchField();
    if (!$entity_id) {
      return;
    }

    // Claim the item, remove it from the list.
    $this->removeItem($entity_id);

    if (!$storage) {
      $entity_type_id = $this->getEntityTypeId();
      if (!$entity_type_id) {
        throw new \RuntimeException('The clean state does not have an entity type assigned.');
      }
      $storage = $this->entityTypeManager->getStorage($this->getEntityTypeId());
    }

    $entity = $storage->load($entity_id);
    if ($entity instanceof EntityInterface) {
      return $entity;
    }
    else {
      return $this->nextEntity($storage);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityTypeId($entity_type_id) {
    // @todo check for valid entity type id.
    $this->entityTypeId = $entity_type_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->entityTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator(): \Traversable {
    return new \ArrayIterator($this->getList());
  }

  /**
   * {@inheritdoc}
   */
  public function count(): int {
    return (int) $this->connection->query('SELECT COUNT(feed_id) FROM {' . static::TABLE_NAME . '} WHERE feed_id = :feed_id', [':feed_id' => $this->feedId])
      ->fetchField();
  }

}
