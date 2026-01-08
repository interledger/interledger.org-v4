<?php

namespace Drupal\tamper_test;

use Drupal\tamper\TamperableItemInterface;

/**
 * A spy object to detect calls to tamperable item methods during testing.
 */
class TamperableItemSpy implements TamperableItemInterface {

  /**
   * Whether any method has been called.
   *
   * @var bool
   */
  private int $methodCallCount = 0;

  /**
   * Records a method call and returns a default value.
   */
  public function __call(string $name, array $arguments) {
    $this->methodCallCount++;
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    $this->methodCallCount++;
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceProperty($property, $data) {
    $this->methodCallCount++;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceProperty($property) {
    $this->methodCallCount++;
    return NULL;
  }

  /**
   * Returns if any methods were called or not.
   */
  public function wasUsed(): bool {
    return $this->methodCallCount > 0;
  }

}
