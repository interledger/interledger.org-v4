<?php

namespace Drupal\feeds_test_entity\Routing;

use Symfony\Component\Routing\Route;

/**
 * Subscriber for Entity Test routes.
 */
class EntityTestRoutes {

  /**
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function routes() {
    $types = [
      'feeds_test_entity_test_no_url',
    ];

    $routes = [];
    foreach ($types as $entity_type_id) {
      $routes["entity.$entity_type_id.admin_form"] = new Route(
        "$entity_type_id/structure/{bundle}",
        ['_controller' => '\Drupal\entity_test\Controller\EntityTestController::testAdmin'],
        ['_permission' => 'administer entity_test content'],
        ['_admin_route' => TRUE]
      );
    }
    return $routes;
  }

}
