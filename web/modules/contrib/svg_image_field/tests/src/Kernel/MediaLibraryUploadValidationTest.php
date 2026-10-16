<?php

declare(strict_types=1);

namespace Drupal\Tests\svg_image_field\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media_library\Form\FileUploadForm;
use Drupal\media_library\MediaLibraryState;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests Media Library modal upload validation for the SVG media source.
 *
 * @group svg_image_field
 */
final class MediaLibraryUploadValidationTest extends KernelTestBase {

  use MediaTypeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'file',
    'filter',
    'image',
    'media',
    'media_library',
    'svg_image_field',
    'system',
    'user',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installSchema('file', 'file_usage');
    $this->installEntitySchema('media');
    $this->installConfig([
      'field',
      'system',
      'file',
      'image',
      'media',
      'media_library',
    ]);

    // Create the special UID 1 account expected by parts of the media stack.
    $this->createUser([]);

    $this->createMediaType('image', ['id' => 'image']);
    $this->createMediaType('svg', [
      'id' => 'vector_image',
      'label' => 'Vector image',
    ]);
  }

  /**
   * Ensures the SVG media source uses the core Media Library upload form.
   */
  public function testSvgMediaSourceUsesCoreUploadForm(): void {
    $definition = $this->container
      ->get('plugin.manager.media.source')
      ->getDefinition('svg');

    $this->assertSame(
      FileUploadForm::class,
      $definition['forms']['media_library_add']
    );
  }

  /**
   * Ensures SVG validation is only applied to SVG-backed media types.
   */
  public function testSvgUploadValidatorsAreScopedToSvgMediaType(): void {
    $image_form = $this->buildUploadForm('image');
    $svg_form = $this->buildUploadForm('vector_image');

    $image_validators = $image_form['container']['upload']['#upload_validators'];
    $svg_validators = $svg_form['container']['upload']['#upload_validators'];

    if (version_compare(\Drupal::VERSION, '10.2.0', '<')) {
      $this->assertArrayNotHasKey('svg_image_field_validate_mime_type', $image_validators);
      $this->assertArrayHasKey('svg_image_field_validate_mime_type', $svg_validators);
    }
    else {
      $this->assertArrayNotHasKey('FileIsSvgImage', $image_validators);
      $this->assertArrayHasKey('FileIsSvgImage', $svg_validators);
    }
  }

  /**
   * Builds the Media Library upload form for the selected media type.
   *
   * @param string $selected_type_id
   *   The media type selected in the Media Library modal.
   *
   * @return array
   *   The built upload form.
   */
  private function buildUploadForm(string $selected_type_id): array {
    $state = MediaLibraryState::create('test', ['image', 'vector_image'], $selected_type_id, -1);
    $form_state = new FormState();
    $form_state->set('media_library_state', $state);

    return \Drupal::formBuilder()->buildForm(FileUploadForm::class, $form_state);
  }

}
