<?php

namespace Drupal\feeds\Feeds\Item;

/**
 * The interface for a feed item with validation support.
 */
interface ValidatableItemInterface extends ItemInterface {

  /**
   * Marks the item as invalid, so that it is not getting imported.
   *
   * @param string $message
   *   Optional parameter to describe why the item is invalid.
   *
   * @return $this
   */
  public function markInvalid(string $message = ''): ValidatableItemInterface;

}
