<?php

declare(strict_types=1);

namespace Drupal\Tests\image_captcha\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests image generation with font tokens that no longer resolve.
 *
 * @group image_captcha
 */
#[Group('image_captcha')]
#[RunTestsInSeparateProcesses]
final class ImageCaptchaUnknownFontTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['captcha', 'image_captcha'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['captcha', 'image_captcha']);
  }

  /**
   * Tests that only unknown font tokens fall back to the built-in font.
   */
  public function testOnlyUnknownFonts(): void {
    $this->setFonts([hash('sha256', 'no/longer/here.ttf')]);
    $this->assertImageGenerated();
  }

  /**
   * Tests that unknown font tokens are skipped next to valid ones.
   */
  public function testUnknownAndValidFonts(): void {
    $available = _image_captcha_get_available_fonts_from_directories();
    $this->assertNotEmpty($available);
    $this->setFonts([
      hash('sha256', 'no/longer/here.ttf'),
      array_key_first($available),
    ]);
    $this->assertImageGenerated();
  }

  /**
   * Sets the enabled font tokens.
   *
   * @param string[] $tokens
   *   The font tokens.
   */
  protected function setFonts(array $tokens): void {
    $this->config('image_captcha.settings')
      ->set('image_captcha_fonts', $tokens)
      ->save();
  }

  /**
   * Asserts that an image CAPTCHA can be generated.
   */
  protected function assertImageGenerated(): void {
    $image = $this->container->get('image_captcha.render_service')
      ->generateImage('Ab3dE');
    $this->assertInstanceOf(\GdImage::class, $image);
  }

}
