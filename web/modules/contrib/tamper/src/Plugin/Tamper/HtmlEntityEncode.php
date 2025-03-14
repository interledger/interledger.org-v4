<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Component\Utility\Html;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperBase;
use Drupal\tamper\TamperableItemInterface;

/**
 * Plugin implementation for html entity encode.
 *
 * @Tamper(
 *   id = "html_entity_encode",
 *   label = @Translation("HTML entity encode"),
 *   description = @Translation("This will convert all HTML special characters such as &gt; and &amp; to &amp;gt; and &amp;apm;."),
 *   category = "Text"
 * )
 */
class HtmlEntityEncode extends TamperBase {

  /**
   * {@inheritdoc}
   */
  public function tamper($data, ?TamperableItemInterface $item = NULL) {
    // Don't process empty or null values.
    if (is_null($data) || $data === '') {
      return $data;
    }

    if (!is_string($data)) {
      throw new TamperException('Input should be a string.');
    }

    return Html::escape($data);
  }

}
