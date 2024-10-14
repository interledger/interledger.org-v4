<?php

namespace Drupal\feeds_test_entity\Entity;

use Drupal\entity_test\Entity\EntityTest;

/**
 * An entity test class where generating a url could lead to an exception.
 *
 * @ContentEntityType(
 *   id = "feeds_test_entity_test_no_url",
 *   label = @Translation("Test entity with url exception"),
 *   handlers = {
 *     "access" = "Drupal\entity_test\EntityTestAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "feeds_test_entity_test_no_url",
 *   admin_permission = "administer entity_test content",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "name",
 *   },
 *   field_ui_base_route = "entity.feeds_test_entity_test_no_url.admin_form",
 *   links = {
 *     "canonical" = "/feeds_test_entity_test_no_url/manage/{feeds_test_entity_test_no_url}",
 *     "add-form" = "/feeds_test_entity_test_no_url/add",
 *     "edit-form" = "/feeds_test_entity_test_no_url/manage/{feeds_test_entity_test_no_url}",
 *   },
 * )
 */
class EntityTestNoUrl extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    $exception = \Drupal::state()->get('feeds_test_entity_test_no_url.exception');
    if (isset($exception['class'])) {
      $class = $exception['class'];
      if (count($exception['args']) > 0) {
        $instance = (new \ReflectionClass($class))->newInstanceArgs($exception['args']);
      }
      else {
        $instance = new $class();
      }
      throw $instance;
    }
    return parent::toUrl($rel, $options);
  }

}
