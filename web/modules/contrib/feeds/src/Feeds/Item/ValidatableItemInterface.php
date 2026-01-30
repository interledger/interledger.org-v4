<?php

namespace Drupal\feeds\Feeds\Item;

/**
 * The interface for a feed item with validation support.
 */
interface ValidatableItemInterface extends ItemInterface {

  /**
   * Whether or not the item is valid.
   *
   * @return bool
   *   True if the item is valid, false otherwise.
   */
  public function isValid(): bool;

  /**
   * Returns the error message, if it set.
   *
   * An empty string is returned if there is no message.
   *
   * @return string
   *   The error message.
   */
  public function getInvalidMessage(): string;

  /**
   * Marks the item as invalid, so that it is not getting imported.
   *
   * @param string $message
   *   (optional) Message to describe why the item is invalid.
   *
   * @return $this
   */
  public function markInvalid(string $message = ''): ValidatableItemInterface;

  /**
   * Marks the item as valid.
   *
   * @return $this
   */
  public function markValid(): ValidatableItemInterface;

}
