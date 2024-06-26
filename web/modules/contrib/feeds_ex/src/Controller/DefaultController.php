<?php

namespace Drupal\feeds_ex\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default controller for the feeds_ex module.
 */
class DefaultController extends ControllerBase {

  /**
   * Autocomplete callback for encodings.
   */
  public function encodingAutocomplete(Request $request): JsonResponse {
    $matches = [];
    $input = $request->query->get('q');

    if (!strlen($input) || Unicode::getStatus() != Unicode::STATUS_MULTIBYTE) {
      return new JsonResponse($matches);
    }

    $added = array_map('trim', explode(',', $input));
    $input = array_pop($added);
    $lower_added = array_map('mb_strtolower', $added);

    // Filter out items already added. Do it case insensitively without changing
    // the suggested case.
    $prefix = '';
    $encodings = [];
    foreach (mb_list_encodings() as $suggestion) {
      if (in_array(mb_strtolower($suggestion), $lower_added)) {
        $prefix .= $suggestion . ', ';
        continue;
      }
      $encodings[] = $suggestion;
    }

    // Find starts with first.
    foreach ($encodings as $delta => $encoding) {
      if (stripos($encoding, $input) !== 0) {
        continue;
      }
      $matches[] = [
        'value' => $prefix . $encoding,
        'label' => Html::escape($encoding),
      ];
      // Remove matches so we don't search them again.
      unset($encodings[$delta]);
    }

    // Find contains next.
    foreach ($encodings as $encoding) {
      if (stripos($encoding, $input) !== FALSE) {
        $matches[] = [
          'value' => $prefix . $encoding,
          'label' => Html::escape($encoding),
        ];
      }
    }

    // Only send back 10 suggestions.
    $matches = array_slice($matches, 0, 10, TRUE);
    return new JsonResponse($matches);
  }

}
