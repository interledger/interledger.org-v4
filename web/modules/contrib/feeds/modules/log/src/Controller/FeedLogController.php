<?php

namespace Drupal\feeds_log\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds_log\ImportLogInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for Feeds log routes.
 */
class FeedLogController extends ControllerBase {

  /**
   * A service to handle various date related functionality.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new FeedLogController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(DateFormatterInterface $date_formatter, EntityTypeManagerInterface $entity_type_manager) {
    $this->dateFormatter = $date_formatter;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Returns page title for logged items listing.
   *
   * @param \Drupal\feeds\FeedInterface $feeds_feed
   *   The feed for which to show logs.
   * @param \Drupal\feeds_log\ImportLogInterface $feeds_import_log
   *   The import log entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   A translatable string.
   */
  public function title(FeedInterface $feeds_feed, ImportLogInterface $feeds_import_log) {
    return $this->t('%feed import log @time', [
      '%feed' => $feeds_feed->label(),
      '@time' => $this->dateFormatter->format($feeds_import_log->getImportStartTime(), 'short'),
    ]);
  }

  /**
   * Displays logged items for a single import.
   *
   * @param \Drupal\feeds\FeedInterface $feeds_feed
   *   The feed for which to show logs.
   * @param \Drupal\feeds_log\ImportLogInterface $feeds_import_log
   *   The import log entity.
   *
   * @return array
   *   A render array.
   */
  public function view(FeedInterface $feeds_feed, ImportLogInterface $feeds_import_log) {
    $view_builder = $this->entityTypeManager->getViewBuilder('feeds_import_log');
    return $view_builder->view($feeds_import_log);
  }

}
