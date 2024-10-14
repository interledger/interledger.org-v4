<?php

namespace Drupal\feeds\Feeds\State;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\State;
use Drupal\feeds\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Factory for creating State objects.
 */
class StateFactory implements StateFactoryInterface {

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Constructs a new StateFactory object.
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
  public function createInstance(FeedInterface $feed, string $stage): StateInterface {
    switch ($stage) {
      case StateInterface::CLEAN:
        return CleanState::create($this->container, $feed->id());

      default:
        return State::create($this->container, $feed->id());
    }
  }

}
