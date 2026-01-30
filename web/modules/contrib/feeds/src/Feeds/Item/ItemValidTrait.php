<?php

namespace Drupal\feeds\Feeds\Item;

/**
 * Trait for marking an item as valid or invalid.
 */
trait ItemValidTrait {

  /**
   * If the item is valid or not.
   *
   * @var bool
   */
  protected bool $valid = TRUE;

  /**
   * The message that describes why the item is invalid.
   *
   * When there is no message, this is an empty string. The message is only
   * relevant when property $valid is FALSE.
   *
   * @var string
   */
  protected string $invalidMessage = '';

  /**
   * Whether or not the item is valid.
   *
   * @return bool
   *   True if the item is valid, false otherwise.
   */
  public function isValid(): bool {
    return $this->valid;
  }

  /**
   * Returns the error message, if it set.
   *
   * An empty string is returned if there is no message.
   *
   * @return string
   *   The error message.
   */
  public function getInvalidMessage(): string {
    return $this->invalidMessage;
  }

  /**
   * Marks the item as invalid, so that it is not getting imported.
   *
   * @param string $message
   *   (optional) Message to describe why the item is invalid.
   *
   * @return $this
   */
  public function markInvalid(string $message = ''): ValidatableItemInterface {
    $this->invalidMessage = $message;
    $this->valid = FALSE;

    return $this;
  }

  /**
   * Marks the item as valid.
   *
   * @return $this
   */
  public function markValid(): ValidatableItemInterface {
    $this->invalidMessage = '';
    $this->valid = TRUE;

    return $this;
  }

}
