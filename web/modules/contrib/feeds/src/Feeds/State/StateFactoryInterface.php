<?php

namespace Drupal\feeds\Feeds\State;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\StateInterface;

/**
 * Factory interface for creating State objects.
 */
interface StateFactoryInterface {

  /**
   * Creates a new State object.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to create a state for.
   * @param string $stage
   *   The stage for which to create a state.
   *
   * @return \Drupal\feeds\StateInterface
   *   The state object for the given stage.
   */
  public function createInstance(FeedInterface $feed, string $stage): StateInterface;

}
