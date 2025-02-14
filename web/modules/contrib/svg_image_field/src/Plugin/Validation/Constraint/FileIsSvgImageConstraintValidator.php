<?php

namespace Drupal\svg_image_field\Plugin\Validation\Constraint;

use Drupal\file\Plugin\Validation\Constraint\BaseFileConstraintValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validator for FileIsSvgImageConstraint.
 */
class FileIsSvgImageConstraintValidator extends BaseFileConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    $file = $this->assertValueIsFile($value);
    if (!$constraint instanceof FileIsSvgImageConstraint) {
      throw new UnexpectedTypeException($constraint, FileIsSvgImageConstraint::class);
    }

    $mime_type = $file->getMimeType();
    $allowed_mime_types = ['image/svg+xml', 'image/svg'];
    if (in_array($mime_type, $allowed_mime_types) === FALSE) {
      $this->context->addViolation($constraint->incorrectMimeTypeMessage, [
        '%mime-types-allowed' => implode(',', $allowed_mime_types),
        '%mime-type-detected' => $mime_type,
      ]);
    }
    else {
      $svg_file = @file_get_contents($file->getFileUri());
      if (!$svg_file) {
        $this->context->addViolation($constraint->invalidSvgFileMessage, [
          '%file-name' => $file->getFilename(),
        ]);
      }

      $doc = new \DOMDocument();
      libxml_use_internal_errors(TRUE);
      $doc->loadXML($svg_file, LIBXML_COMPACT);
      $errors = libxml_get_errors();
      libxml_clear_errors();

      // Check if there were any XML parsing errors.
      if (!empty($errors)) {
        $error_messages = array_map(function (\LibXMLError $error) {
          return sprintf(
            'Line %d: %s',
            $error->line,
            trim($error->message)
          );
        }, $errors);

        $this->context->addViolation($constraint->xmlErrorMessage, [
          '@errors' => implode(',', $error_messages),
        ]);
        return;
      }

      // Find the first element node (skipping XML declaration, DOCTYPE,
      // comments, etc).
      $root = NULL;
      /** @var \DOMElement $node */
      foreach ($doc->childNodes as $node) {
        if ($node->nodeType === XML_ELEMENT_NODE) {
          $root = $node;
          break;
        }
      }

      // Verify that a root element exists.
      if (!$root) {
        $this->context->addViolation($constraint->emptyRootElementMessage);
        return;
      }

      // Verify that the root element is <svg>.
      if ($root->tagName !== 'svg') {
        $this->context->addViolation($constraint->notSvgMessage, [
          '@expected' => '<svg>',
          '@actual' => '<' . $root->tagName . '>',
        ]);
        return;
      }
    }

  }

}
