<?php

namespace Drupal\rename_admin_paths\EventSubscriber;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\rename_admin_paths\Config;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RenameAdminPathsEventSubscriber implements EventSubscriberInterface {

  /**
   * list of admin paths.
   *
   * @var array
   */
  const ADMIN_PATHS = ['admin', 'user'];

  /**
   * @var Config
   */
  private $config;

  /**
   * @param Config $config
   */
  public function __construct(Config $config) {
    $this->config = $config;
  }

  /**
   * Use a very low priority so we are sure all routes are correctly marked as
   * admin route which is mostly done in other event subscribers like the
   * AdminRouteSubscriber
   *
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      RoutingEvents::ALTER => [
        ['onRoutesAlter', -2048],
      ],
    ];
  }

  /**
   * @param RouteBuildEvent $event
   */
  public function onRoutesAlter(RouteBuildEvent $event) {
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
   * @param RouteCollection $routeCollection
   * @param string $from
   * @param string $to
   */
  private function alterRouteCollection(
    RouteCollection $routeCollection,
    string $from,
    string $to
  ): void {
    foreach ($routeCollection as $route) {
      $this->replaceRoutePath($route, $from, $to);
    }
  }

  /**
   * @param Route $route
   * @param string $from
   * @param string $to
   */
  private function replaceRoutePath(
    Route $route,
    string $from,
    string $to
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
   * @param Route $route
   * @param string $path
   *
   * @return boolean
   *
   * match /path, /path/ and /path/* but not /path*
   */
  private function matchRouteByPath(Route $route, string $path): bool {
    return (bool) preg_match(
      sprintf('~^/%s(?:/(.*))?$~', $path),
      $route->getPath()
    );
  }
}
