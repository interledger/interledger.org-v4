<?php

namespace Drupal\feeds_ex\File;

/**
 * Text lines from file iterator.
 */
class LineIterator extends \SplFileObject {

  /**
   * The position to start in the file.
   *
   * @var int
   */
  protected $startPosition = 0;

  /**
   * The number of lines to read.
   *
   * @var int
   */
  protected $lineLimit;

  /**
   * The number of lines that have been read.
   *
   * @var int
   */
  protected $linesRead = 0;

  /**
   * Implements Iterator::rewind().
   */
  public function rewind(): void {
    parent::rewind();
    if ($this->startPosition) {
      $this->fseek($this->startPosition);
    }
    $this->linesRead = 0;
  }

  /**
   * Implements Iterator::next().
   */
  public function next(): void {
    $this->linesRead++;
    parent::next();
  }

  /**
   * Implements Iterator::valid().
   */
  public function valid(): bool {
    return (!$this->lineLimit || $this->linesRead < $this->lineLimit) && parent::valid() && parent::current();
  }

  /**
   * Sets the number of lines to read.
   *
   * @param int $limit
   *   The number of lines to read.
   */
  public function setLineLimit($limit) {
    $this->lineLimit = (int) $limit;
  }

  /**
   * Sets the starting position.
   *
   * @param int $position
   *   The position to start in the file.
   */
  public function setStartPosition($position) {
    $this->startPosition = (int) $position;
  }

}
