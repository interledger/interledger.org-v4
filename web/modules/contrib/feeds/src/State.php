<?php

namespace Drupal\feeds;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\feeds\Event\EventDispatcherTrait;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ReportEvent;
use Drupal\feeds\Feeds\State\ContainerStateInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Status of the import or clearing operation of a Feed.
 */
#[\AllowDynamicProperties]
class State implements StateInterface, ContainerStateInterface {

  use DependencySerializationTrait {
    __sleep as dependencySerializationTraitSleep;
    __wakeup as dependencySerializationTraitWakeUp;
  }
  use EventDispatcherTrait;

  /**
   * Denotes the progress made.
   *
   * 0.0 meaning no progress. 1.0 = StateInterface::BATCH_COMPLETE meaning
   * finished.
   *
   * @var float
   */
  public $progress = StateInterface::BATCH_COMPLETE;

  /**
   * Used as a pointer to store where left off. Must be serializable.
   *
   * @var scalar
   */
  public $pointer;

  /**
   * The total number of items being processed.
   *
   * @var int
   */
  public $total = 0;

  /**
   * The number of Feed items created.
   *
   * @var int
   */
  public $created = 0;

  /**
   * The number of Feed items updated.
   *
   * @var int
   */
  public $updated = 0;

  /**
   * The number of Feed items deleted.
   *
   * @var int
   */
  public $deleted = 0;

  /**
   * The number of Feed items skipped.
   *
   * @var int
   */
  public $skipped = 0;

  /**
   * The number of failed Feed items.
   *
   * @var int
   */
  public $failed = 0;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The logger for the feeds channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The list of messages to display to the user.
   *
   * Each entry on the array is expected to have the following values:
   * - message (string|\Drupal\Component\Render\MarkupInterface): the translated
   *   message to be displayed to the user;
   * - type (string): the message's type. These values are supported:
   *   - 'status'
   *   - 'warning'
   *   - 'error'
   * - repeat (bool): whether or not showing the same message more than once.
   *
   * @var array
   */
  protected $messages = [];

  /**
   * Constructs a new State object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger for the feeds channel.
   */
  public function __construct(MessengerInterface $messenger, LoggerInterface $logger) {
    $this->messenger = $messenger;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, int $feed_id) {
    return new static(
      $container->get('messenger'),
      $container->get('logger.factory')->get('feeds')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    $vars = $this->dependencySerializationTraitSleep();

    // Do not serialize the logger object.
    $key = array_search('logger', $vars);
    if ($key !== FALSE) {
      unset($vars[$key]);
    }

    return $vars;
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function __wakeup() {
    $this->dependencySerializationTraitWakeUp();

    // Restore the logger.
    $container = \Drupal::getContainer();
    $this->logger = $container->get('logger.factory')->get('feeds');

    // If the messenger service did not get restored because the State object
    // was serialized in a Feeds version before 8.x-3.0-rc1, make sure that the
    // messenger service does get restored.
    if (!$this->messenger instanceof MessengerInterface) {
      $this->messenger = $container->get('messenger');
    }
  }

  /**
   * Reports a processed item.
   *
   * @param string $code
   *   What happened to the imported item.
   * @param string|\Drupal\Component\Render\MarkupInterface $message
   *   (optional) The reported message.
   * @param array $context
   *   (optional) Context data.
   */
  public function report($code, $message = '', array $context = []) {
    $this->$code++;

    if (isset($context['feed']) && $context['feed'] instanceof FeedInterface) {
      $feed = $context['feed'];
      unset($context['feed']);
      $this->dispatchEvent(FeedsEvents::REPORT, new ReportEvent($feed, $code, $message, $context));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function progress($total, $progress) {
    if ($progress > $total || $total === $progress) {
      $this->setCompleted();
    }
    elseif ($total) {
      $this->progress = (float) ($progress / $total);
      if ($this->progress === StateInterface::BATCH_COMPLETE && $total !== $progress) {
        $this->progress = 0.99;
      }
    }
    else {
      $this->setCompleted();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setCompleted() {
    $this->progress = StateInterface::BATCH_COMPLETE;
  }

  /**
   * {@inheritdoc}
   */
  public function setMessage($message = NULL, $type = 'status', $repeat = FALSE) {
    $this->messages[] = [
      'message' => $message,
      'type' => $type,
      'repeat' => $repeat,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function displayMessages() {
    foreach ($this->messages as $message) {
      $this->messenger->addMessage($message['message'], $message['type'], $message['repeat']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function logMessages(FeedInterface $feed) {
    foreach ($this->messages as $message) {
      switch ($message['type']) {
        case 'status':
          $message['type'] = 'info';
          break;
      }
      $this->logger->log($message['type'], $message['message'], [
        'feed' => $feed,
      ]);
    }
  }

}
