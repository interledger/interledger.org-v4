<?php

namespace Drupal\feeds\Feeds\Target;

use Drupal\Component\Uuid\Uuid as UuidValidator;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\TargetValidationException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a UUID field mapper.
 *
 * @FeedsTarget(
 *   id = "uuid",
 *   field_types = {
 *     "uuid"
 *   }
 * )
 */
class Uuid extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    $definition = FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('value')
      ->markPropertyUnique('value');

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty(FeedInterface $feed, EntityInterface $entity, $field_name) {
    // We only allow the UUID to be set when creating the entity.
    // Base UUID fields are marked as read only and EntityProcessorBase::map()
    // will only allow the value to be set if the field is empty.
    // However, ContentEntityStorageBase makes multiple attempts to set the UUID
    // value during the entity creation process so by now, event new entity has
    // the UUID field set. So, we lie here to allow the incoming value to
    // override what is already there, but only during initial entity import.
    return $entity->isNew();
  }

  /**
   * {@inheritdoc}
   *
   * Adds UUID validation and confirms that the value is not empty.
   */
  protected function prepareValue($delta, array &$values) {
    parent::prepareValue($delta, $values);

    $values['value'] = trim($values['value']);
    if (empty($values['value'])) {
      throw new EmptyFeedException($this->t('UUID value cannot be empty'));
    }

    if (!UuidValidator::isValid($values['value'])) {
      $value = $values['value'];
      throw new TargetValidationException($this->t('Supplied value "%value" is not a valid UUID.', ['%value' => $value]));
    }

    if ($delta > 0) {
      throw new TargetValidationException($this->t('UUID field cannot hold more than 1 value'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setTarget(FeedInterface $feed, EntityInterface $entity, $field_name, array $values) {
    if ($values = $this->prepareValues($values)) {
      $entity_target = $this->getEntityTarget($feed, $entity);
      if (!empty($entity_target)) {
        $entity_target->get($field_name)->setValue($values);
      }
    }
  }

}
