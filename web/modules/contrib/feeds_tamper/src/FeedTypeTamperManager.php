<?php

namespace Drupal\feeds_tamper;

use Drupal\feeds\FeedTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manager for FeedTypeTamperMeta instances.
 */
class FeedTypeTamperManager implements FeedTypeTamperManagerInterface {

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * An array of FeedsTamper instances.
   *
   * @var \Drupal\feeds_tamper\FeedTypeTamperMeta[]
   */
  protected $tamperMetas = [];

  /**
   * Constructs a new FeedTypeTamperManager.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   */
  public function __construct(ContainerInterface $container) {
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public function getTamperMeta(FeedTypeInterface $feed_type, $reset = FALSE) {
    $id = $feed_type->id();

    if ($reset || !isset($this->tamperMetas[$id])) {
      $this->tamperMetas[$id] = FeedTypeTamperMeta::create($this->container, $feed_type);
    }

    return $this->tamperMetas[$id];
  }

}
