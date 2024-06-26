<?php

namespace Drupal\feeds\Feeds\Target;

use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a language field mapper.
 *
 * @FeedsTarget(
 *   id = "langcode",
 *   field_types = {
 *     "language"
 *   }
 * )
 */
class Language extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  public function setTarget(FeedInterface $feed, EntityInterface $entity, $field_name, array $values) {
    if ($values = $this->prepareValues($values)) {
      $langcode = $values[0]['value'] ?? NULL;
      if (!empty($langcode)) {
        $entity->set($field_name, $langcode);
      }
    }
  }

}
