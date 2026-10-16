<?php

declare(strict_types=1);

namespace Drupal\Tests\svg_image_field\Kernel;

use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\svg_image_field\Plugin\Validation\Constraint\FileIsSvgImageConstraint;

/**
 * Tests error messages from the FileIsSvgImage constraint.
 *
 * @group svg_image_field
 */
final class FileIsSvgImageConstraintMessagesTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'svg_image_field',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
  }

  /**
   * Tests that XML parsing errors are split into multiple list items.
   */
  public function testXmlErrorsAreSplitIntoMultipleViolations(): void {
    $module_path = $this->container->get('extension.list.module')->getPath('svg_image_field');
    $resource = \Drupal::root() . '/' . $module_path . '/tests/resources/invalid_svg--svg-with-script-tag.svg';

    $uri = 'temporary://invalid_svg--svg-with-script-tag.svg';
    file_put_contents($uri, file_get_contents($resource));

    $file = File::create([
      'uri' => $uri,
      'filename' => 'invalid_svg--svg-with-script-tag.svg',
      'filemime' => 'image/svg+xml',
      'status' => 1,
    ]);

    $violations = $this->container
      ->get('typed_data_manager')
      ->getValidator()
      ->validate($file->getTypedData(), new FileIsSvgImageConstraint());
    $messages = [];
    foreach ($violations as $violation) {
      $messages[] = (string) $violation->getMessage();
    }

    $this->assertGreaterThanOrEqual(2, count($messages));
    $this->assertSame('The file contains XML errors and cannot be parsed.', $messages[0]);
    $this->assertStringContainsString('Line', $messages[1]);
  }

  /**
   * Tests that invalid root element errors are split into two violations.
   */
  public function testInvalidRootElementIsSplitIntoTwoViolations(): void {
    $module_path = $this->container->get('extension.list.module')->getPath('svg_image_field');
    $resource = \Drupal::root() . '/' . $module_path . '/tests/resources/invalid_svg--script-tag-only.svg';

    $uri = 'temporary://invalid_svg--script-tag-only.svg';
    file_put_contents($uri, file_get_contents($resource));

    $file = File::create([
      'uri' => $uri,
      'filename' => 'invalid_svg--script-tag-only.svg',
      'filemime' => 'image/svg+xml',
      'status' => 1,
    ]);

    $violations = $this->container
      ->get('typed_data_manager')
      ->getValidator()
      ->validate($file->getTypedData(), new FileIsSvgImageConstraint());
    $messages = [];
    foreach ($violations as $violation) {
      $messages[] = (string) $violation->getMessage();
    }

    $this->assertCount(2, $messages);
    $this->assertSame('The file contains an invalid root element.', $messages[0]);
    $this->assertStringContainsString('Expected', $messages[1]);
    $this->assertStringContainsString('&lt;svg&gt;', $messages[1]);
    $this->assertStringContainsString('&lt;script&gt;', $messages[1]);
  }

}
