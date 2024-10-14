<?php

namespace Drupal\feeds\Feeds\State;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an interface for dependency container injection for State objects.
 */
interface ContainerStateInterface {

  /**
   * Instantiates a new instance of this class.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param int $feed_id
   *   The ID of the feed that this state belongs to.
   */
  public static function create(ContainerInterface $container, int $feed_id);

}
