<?php

declare(strict_types=1);

namespace Drupal\captcha\Render;

/**
 * Keeps the Math CAPTCHA question visible when its title is replaced.
 *
 * The Math challenge carries its question ("x + y =") in the response field's
 * #title, so that screen readers announce it with the label. Code that
 * replaces that #title, such as Webform's "Question title" setting, would
 * otherwise remove the question. In that case the question is rendered as a
 * field prefix and linked to the input with aria-describedby.
 *
 * @see captcha_captcha()
 * @see \Drupal\captcha\Element\Captcha::preRenderProcess()
 */
final class MathCaptchaQuestion {

  /**
   * Adds the question as a prefix if the response field's title was replaced.
   *
   * @param array $element
   *   The captcha_response form element.
   *
   * @return array
   *   The element, unchanged unless it is a Math response field whose #title
   *   no longer is the generated one.
   */
  public static function keepQuestionVisible(array $element): array {
    if (!isset($element['#captcha_math_title'], $element['#captcha_math_question'], $element['#id'])
      || (string) ($element['#title'] ?? '') === (string) $element['#captcha_math_title']
      || !empty($element['#field_prefix'])) {
      return $element;
    }

    $question_id = $element['#id'] . '--math-question';
    $element['#field_prefix'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => ['id' => $question_id],
      '#value' => $element['#captcha_math_question'],
    ];
    // Keep the description link that the form builder added, if any.
    $described_by = $element['#attributes']['aria-describedby'] ?? '';
    $element['#attributes']['aria-describedby'] = trim($question_id . ' ' . $described_by);

    return $element;
  }

}
