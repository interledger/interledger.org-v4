<?php

namespace Drupal\feeds\Feeds\Item;

/**
 * Defines a base item class.
 */
abstract class BaseItem implements ItemInterface, ValidatableItemInterface {

  use ItemValidTrait;

  /**
   * Additional item data.
   *
   * This contains data for fields that are not defined as class properties.
   *
   * Subclasses MUST NOT override this property with a different type.
   *
   * @var array
   */
  protected array $data = [];

  /**
   * {@inheritdoc}
   */
  public function get($field) {
    // Special case: allow "data" as a user field. When a field called 'data'
    // is requested, instead of returning the contents of the $data property,
    // check if the $data property contains a field called 'data'. If so, return
    // that. If not, return null.
    if ($field === 'data') {
      return $this->data['data'] ?? NULL;
    }

    // Check if the requested field is defined as a property on our object. If
    // this is the case, return the value of the object property.
    if (property_exists($this, $field)) {
      return $this->$field;
    }

    // If the field is *not* defined as an object property, check if the field
    // exists on the data property.
    if (isset($this->data[$field])) {
      return $this->data[$field];
    }

    // The requested field was not found directly on the object, nor on the data
    // property. In that case, return null.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function set($field, $value) {
    // Special case: allow "data" as a user field.
    // Store it inside the internal $data array instead of overwriting the $data
    // property.
    if ($field === 'data') {
      $this->data['data'] = $value;
      return $this;
    }

    // Check if the requested field is defined as a property on our object. If
    // this is the case, set the value directly on that object property.
    if (property_exists($this, $field)) {
      $this->$field = $value;
      return $this;
    }

    // If the field is *not* defined as an object property, set it on the data
    // property.
    $this->data[$field] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $vars = [];

    // Create an array of all object properties except "data", "valid" and
    // "invalidMessage".
    foreach (get_object_vars($this) as $key => $value) {
      switch ($key) {
        case 'data':
        case 'valid':
        case 'invalidMessage':
          break;

        default:
          $vars[$key] = $value;
      }
    }

    // Merge with the internal $data array (which may also include a field
    // called 'data').
    return $vars + $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function fromArray(array $data) {
    foreach ($data as $field => $value) {
      $this->set($field, $value);
    }
    return $this;
  }

}
