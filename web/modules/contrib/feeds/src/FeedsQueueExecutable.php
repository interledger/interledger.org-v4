<?php

namespace Drupal\feeds;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\Utility\Error;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\FetchException;
use Drupal\feeds\Exception\FileException;
use Drupal\feeds\Result\FetcherResultInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Import feeds using the queue API.
 */
class FeedsQueueExecutable extends FeedsExecutable {

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The logger channel for 'feeds'.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new FeedsQueueExecutable object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Session\AccountSwitcherInterface $account_switcher
   *   The account switcher.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The Feeds logger.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, AccountSwitcherInterface $account_switcher, MessengerInterface $messenger, QueueFactory $queue_factory, LoggerInterface $logger) {
    parent::__construct($entity_type_manager, $event_dispatcher, $account_switcher, $messenger);
    $this->queueFactory = $queue_factory;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('account_switcher'),
      $container->get('messenger'),
      $container->get('queue'),
      $container->get('logger.factory')->get('feeds')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function createBatch(FeedInterface $feed, $stage) {
    return new FeedsQueueBatch($this, $feed, $stage, $this->queueFactory);
  }

  /**
   * {@inheritdoc}
   */
  protected function handleException(FeedInterface $feed, $stage, array $params, \Exception $exception) {
    if ($exception instanceof EmptyFeedException) {
      if (isset($params['fetcher_result']) && $params['fetcher_result'] instanceof FetcherResultInterface) {
        $params['fetcher_result']->cleanUp();
      }
      $feed->finishImport();
      return;
    }
    elseif ($exception instanceof FetchException) {
      // Fetching a resource failed. Try again at a later time.
      // @todo implement a solution to limit the amount of retries.
      // @see https://www.drupal.org/project/feeds/issues/3277999
      throw $exception;
    }
    elseif ($exception instanceof FileException) {
      $this->logException($exception, 'The feed "@label" encountered an error when processing: %type: @message in %function (line %line of %file).', [
        '@label' => $feed->label(),
      ]);
      $feed->finishImport();
      return;
    }

    // On an exception, the queue item remains on the queue so we need to keep
    // the feed locked.
    throw $exception;
  }

  /**
   * Logs an exception that works for both Drupal 9 and Drupal 10.
   *
   * @param \Exception $exception
   *   The exception to log.
   * @param string $message
   *   The message to log.
   * @param array $variables
   *   Array of variables to replace in the message.
   */
  protected function logException(\Exception $exception, string $message, array $variables): void {
    Error::logException($this->logger, $exception, $message, $variables);
  }

}
