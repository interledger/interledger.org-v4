<?php

declare(strict_types=1);

namespace Drupal\rename_admin_paths\EventSubscriber;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\rename_admin_paths\Config;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alters the routes to rename the admin paths.
 */
class RenameAdminPathsEventSubscriber implements EventSubscriberInterface {

  /**
   * List of admin paths.
   *
   * @var array
   */
  const ADMIN_PATHS = ['admin', 'user'];

  /**
   * The module config.
   *
   * @var \Drupal\rename_admin_paths\Config
   */
  private Config $config;

  /**
   * Constructs an event subscriber.
   *
   * @param \Drupal\rename_admin_paths\Config $config
   *   The module config.
   */
  public function __construct(Config $config) {
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   *
   * Use a very low priority so we are sure all routes are correctly marked as
   * admin route which is mostly done in other event subscribers like the
   * AdminRouteSubscriber.
   */
  public static function getSubscribedEvents(): array {
    return [
      RoutingEvents::ALTER => [
        ['onRoutesAlter', -2048],
      ],
    ];
  }

  /**
   * Alters routes if at least one admin path is enabled.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route generation event.
   */
  public function onRoutesAlter(RouteBuildEvent $event): void {
    foreach (self::ADMIN_PATHS as $path) {
      if ($this->config->isPathEnabled($path)) {
        $this->alterRouteCollection(
          $event->getRouteCollection(),
          $path,
          $this->config->getPathValue($path)
        );
      }
    }
  }

  /**
   * Replaces the paths for a collection of routes.
   *
   * @param \Symfony\Component\Routing\RouteCollection $routeCollection
   *   The route collection to replace paths for.
   * @param string $from
   *   The old path before the change.
   * @param string $to
   *   The new path after the change.
   */
  private function alterRouteCollection(
    RouteCollection $routeCollection,
    string $from,
    string $to,
  ): void {
    foreach ($routeCollection as $route) {
      $this->replaceRoutePath($route, $from, $to);
    }
  }

  /**
   * Replaces the path of a route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to change.
   * @param string $from
   *   The old path before the change.
   * @param string $to
   *   The new path after the change.
   */
  private function replaceRoutePath(
    Route $route,
    string $from,
    string $to,
  ): void {
    if ($this->matchRouteByPath($route, $from)) {
      $route->setPath(
        preg_replace(
          sprintf('~^/%s~', $from),
          sprintf('/%s', $to),
          $route->getPath(),
          1
        )
      );
    }
  }

  /**
   * Checks whether a route matches a path.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check.
   * @param string $path
   *   The path to match against.
   *
   * @return bool
   *   TRUE if the route was matched.
   *
   *   match /path, /path/ and /path/* but not /path*
   */
  private function matchRouteByPath(Route $route, string $path): bool {
    return (bool) preg_match(
      sprintf('~^/%s(?:/(.*))?$~', $path),
      $route->getPath()
    );
  }

}
