<?php

namespace Drupal\svg_image_field\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validates a SVG image.
 */
#[Constraint(
  id: 'FileIsSvgImage',
  label: new TranslatableMarkup('File Is SVG Image', [], ['context' => 'Validation']),
  type: 'file'
)]
class FileIsSvgImageConstraint extends SymfonyConstraint {

  /**
   * The message for when file mime type is not SVG.
   *
   * @var string
   */
  public string $incorrectMimeTypeMessage = 'Only files with mime type %mime-types-allowed are allowed. Mime type detected %mime-type-detected';

  /**
   * The message for when file is not a valid SVG.
   *
   * @var string
   */
  public string $invalidSvgFileMessage = 'The file is not a valid SVG image.';

  /**
   * The message for when an SVG file contains XML errors.
   *
   * @var string
   */
  public $xmlErrorMessage = 'The file contains XML errors and cannot be parsed.';

  /**
   * The message for when an SVG file does not contain a root element.
   *
   * @var string
   */
  public $emptyRootElementMessage = 'The file is missing a valid <code>&lt;svg&gt;</code> root element.';

  /**
   * The message for when an SVG file contains an invalid root element.
   *
   * @var string
   */
  public $notSvgMessage = 'The file contains an invalid root element.';

  /**
   * The details message for when an SVG file contains an invalid root element.
   *
   * @var string
   */
  public $notSvgDetailsMessage = 'Expected @expected but found @actual.';

}
