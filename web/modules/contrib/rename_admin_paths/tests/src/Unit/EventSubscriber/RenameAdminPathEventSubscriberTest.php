<?php

declare(strict_types=1);

namespace Drupal\Tests\rename_admin_paths\Unit\EventSubscriber;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\rename_admin_paths\Config;
use Drupal\rename_admin_paths\EventSubscriber\RenameAdminPathsEventSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests to confirm the event subscriber renames paths properly.
 *
 * @group rename_admin_paths
 */
class RenameAdminPathEventSubscriberTest extends UnitTestCase {

  /**
   * Test that the event subscriber gets the correct events.
   */
  public function testGetSubscribedEvents(): void {
    $events = RenameAdminPathsEventSubscriber::getSubscribedEvents();

    $this->assertCount(1, $events);
  }

  /**
   * Test that paths are not renamed just by enabling the module.
   */
  public function testDoNotRenamePaths(): void {
    $this->assertRoutePaths(
      [],
      [
        'home' => '/',
        'about' => '/about',
        'admin' => '/admin',
        'admin_slashed' => '/admin/',
        'admin_sub' => '/admin/sub',
        'admin_sub_sub' => '/admin/sub/sub',
        'admin_admin' => '/admin/admin',
        'admin_sub_admin' => '/admin/sub/admin',
        'admins' => '/admins',
        'admins_sub' => '/admins/sub',
        'user' => '/user',
        'user_slashed' => '/user/',
        'user_sub' => '/user/sub',
        'user_sub_sub' => '/user/sub/sub',
        'user_admin' => '/user/user',
        'user_sub_admin' => '/user/sub/user',
        'users' => '/users',
        'users_sub' => '/users/sub',
      ]
    );
  }

  /**
   * Test that admin paths can be renamed.
   */
  public function testRenameAdminPath(): void {
    $this->assertRoutePaths(
      [
        'admin_path' => TRUE,
        'admin_path_value' => 'backend',
      ],
      [
        'home' => '/',
        'about' => '/about',
        'admin' => '/backend',
        'admin_slashed' => '/backend/',
        'admin_sub' => '/backend/sub',
        'admin_sub_sub' => '/backend/sub/sub',
        'admin_admin' => '/backend/admin',
        'admin_sub_admin' => '/backend/sub/admin',
        'admins' => '/admins',
        'admins_sub' => '/admins/sub',
        'user' => '/user',
        'user_slashed' => '/user/',
        'user_sub' => '/user/sub',
        'user_sub_sub' => '/user/sub/sub',
        'user_admin' => '/user/user',
        'user_sub_admin' => '/user/sub/user',
        'users' => '/users',
        'users_sub' => '/users/sub',
      ]
    );
  }

  /**
   * Test that user paths can be renamed.
   */
  public function testRenameUserPath(): void {
    $this->assertRoutePaths(
      [
        'user_path' => TRUE,
        'user_path_value' => 'member',
      ],
      [
        'home' => '/',
        'about' => '/about',
        'admin' => '/admin',
        'admin_slashed' => '/admin/',
        'admin_sub' => '/admin/sub',
        'admin_sub_sub' => '/admin/sub/sub',
        'admin_admin' => '/admin/admin',
        'admin_sub_admin' => '/admin/sub/admin',
        'admins' => '/admins',
        'admins_sub' => '/admins/sub',
        'user' => '/member',
        'user_slashed' => '/member/',
        'user_sub' => '/member/sub',
        'user_sub_sub' => '/member/sub/sub',
        'user_admin' => '/member/user',
        'user_sub_admin' => '/member/sub/user',
        'users' => '/users',
      ]
    );
  }

  /**
   * Test that admin and user paths can be renamed.
   */
  public function testRenameAdminPaths(): void {
    $this->assertRoutePaths(
      [
        'admin_path' => TRUE,
        'admin_path_value' => 'backend',
        'user_path' => TRUE,
        'user_path_value' => 'member',
      ],
      [
        'home' => '/',
        'about' => '/about',
        'admin' => '/backend',
        'admin_slashed' => '/backend/',
        'admin_sub' => '/backend/sub',
        'admin_sub_sub' => '/backend/sub/sub',
        'admin_admin' => '/backend/admin',
        'admin_sub_admin' => '/backend/sub/admin',
        'admins' => '/admins',
        'admins_sub' => '/admins/sub',
        'user' => '/member',
        'user_slashed' => '/member/',
        'user_sub' => '/member/sub',
        'user_sub_sub' => '/member/sub/sub',
        'user_admin' => '/member/user',
        'user_sub_admin' => '/member/sub/user',
        'users' => '/users',
        'users_sub' => '/users/sub',
      ]
    );
  }

  /**
   * Asserts routes according to the module config.
   *
   * @param array $config
   *   The Rename Admin Paths module config.
   * @param array $routes
   *   The routes to assert.
   */
  private function assertRoutePaths(array $config, array $routes): void {
    $routeCollection = $this->getRouteCollection();

    $config = new Config($this->getConfigFactoryStub(
      [
        'rename_admin_paths.settings' => $config,
      ]
    ));

    $eventSubscriber = new RenameAdminPathsEventSubscriber($config);
    $eventSubscriber->onRoutesAlter(new RouteBuildEvent($routeCollection));

    foreach ($routes as $name => $path) {
      $this->assertEquals($path, $routeCollection->get($name)->getPath());
    }
  }

  /**
   * Returns a route collection.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The route collection.
   */
  private function getRouteCollection(): RouteCollection {
    $routeCollection = new RouteCollection();
    foreach ([
      'home' => '/',
      'about' => '/about',
      'admin' => '/admin',
      'admin_slashed' => '/admin/',
      'admin_sub' => '/admin/sub',
      'admin_sub_sub' => '/admin/sub/sub',
      'admin_admin' => '/admin/admin',
      'admin_sub_admin' => '/admin/sub/admin',
      'admins' => '/admins',
      'admins_sub' => '/admins/sub',
      'user' => '/user',
      'user_slashed' => '/user/',
      'user_sub' => '/user/sub',
      'user_sub_sub' => '/user/sub/sub',
      'user_admin' => '/user/user',
      'user_sub_admin' => '/user/sub/user',
      'users' => '/users',
      'users_sub' => '/users/sub',
    ] as $name => $path) {
      $routeCollection->add($name, new Route($path));
    }

    return $routeCollection;
  }

}
