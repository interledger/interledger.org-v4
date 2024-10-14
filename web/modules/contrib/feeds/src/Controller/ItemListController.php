<?php

namespace Drupal\feeds\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Utility\Error;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\Processor\EntityProcessorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Lists the feed items belonging to a feed.
 */
class ItemListController extends ControllerBase {

  /**
   * Provides a object for obtaining system time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Provides a service to handle various date related functionality.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new ItemListController object.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The object for obtaining system time.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The services of date.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(TimeInterface $time, DateFormatterInterface $date_formatter, MessengerInterface $messenger, LoggerInterface $logger) {
    $this->time = $time;
    $this->dateFormatter = $date_formatter;
    $this->messenger = $messenger;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('datetime.time'),
      $container->get('date.formatter'),
      $container->get('messenger'),
      $container->get('logger.factory')->get('feeds'),
    );
  }

  /**
   * Lists the feed items belonging to a feed.
   */
  public function listItems(FeedInterface $feeds_feed, Request $request) {
    $processor = $feeds_feed->getType()->getProcessor();

    $header = [
      'id' => $this->t('ID'),
      'title' => $this->t('Label'),
      'imported' => $this->t('Imported'),
      'guid' => [
        'data' => $this->t('GUID'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'url' => [
        'data' => $this->t('URL'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];

    $build = [];
    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => [],
      '#empty' => $this->t('There are no items yet.'),
    ];

    // @todo Allow processors to create their own entity listings.
    if (!$processor instanceof EntityProcessorInterface) {
      return $build;
    }

    $entity_ids = $this->entityTypeManager()->getStorage($processor->entityType())
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('feeds_item', [$feeds_feed->id()], 'IN')
      ->pager(50)
      ->sort('feeds_item.imported', 'DESC')
      ->execute();

    $storage = $this->entityTypeManager()->getStorage($processor->entityType());
    foreach ($storage->loadMultiple($entity_ids) as $entity) {
      $feed_item = $entity->get('feeds_item')->getItemByFeed($feeds_feed);
      $ago = $this->dateFormatter->formatInterval($this->time->getRequestTime() - $feed_item->imported);
      $row = [];

      // Entity ID.
      $row[] = $entity->id();

      // Entity link.
      try {
        $label = $entity->label();
        if (is_string($label)) {
          $label = Unicode::truncate($entity->label(), 75, TRUE, TRUE);
        }
        $row[] = [
          'data' => $entity->toLink($label),
          'title' => $entity->label(),
        ];
      }
      catch (UndefinedLinkTemplateException $e) {
        $row[] = $entity->label();
      }
      catch (RouteNotFoundException $e) {
        $row[] = $entity->label();
      }
      catch (MissingMandatoryParametersException $e) {
        $row[] = $entity->label();
      }
      catch (\Exception $e) {
        // Log this exception and display a message, if applicable.
        Error::logException($this->logger, $e);
        $message = $e->getMessage();
        if (is_string($message) && strlen($message) > 0) {
          $this->messenger->addError($message);
        }
        $row[] = $entity->label();
      }

      // Imported ago.
      $row[] = $this->t('@time ago', ['@time' => $ago]);

      // Item GUID.
      $row[] = [
        'data' => $this->getPropertyFromFeedsItem($entity, 'guid', $feeds_feed),
        'title' => $feed_item->guid,
      ];

      // Item URL.
      $row[] = [
        'data' => $this->getPropertyFromFeedsItem($entity, 'url', $feeds_feed),
        'title' => $feed_item->url,
      ];

      $build['table']['#rows'][] = $row;
    }

    $build['pager'] = ['#type' => 'pager'];
    $build['#title'] = $this->t('%title items', ['%title' => $feeds_feed->label()]);

    return $build;
  }

  /**
   * Returns a property of the feeds item.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The imported entity that is being listed.
   * @param string $property
   *   The property to get from the feeds item.
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed entity associate with the feed item.
   *
   * @return string|null|mixed
   *   If the value on the feed is a string, it will be returned truncated to 30
   *   characters. In case it is not a string, but still a scalar value, the
   *   value will be returned as is. Null is returned when it is not a scalar
   *   value.
   */
  protected function getPropertyFromFeedsItem(EntityInterface $entity, string $property, FeedInterface $feed) {
    $value = $entity->get('feeds_item')->getItemByFeed($feed)->{$property};
    if (!is_scalar($value)) {
      return NULL;
    }
    if (!is_string($value)) {
      return $value;
    }

    return Unicode::truncate($value, 30, FALSE, TRUE);
  }

}
