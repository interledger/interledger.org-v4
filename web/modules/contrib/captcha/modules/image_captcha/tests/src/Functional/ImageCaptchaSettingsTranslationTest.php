<?php

declare(strict_types=1);

namespace Drupal\Tests\image_captcha\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\locale\LocaleXss;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the translation of the Image CAPTCHA settings form.
 *
 * @group image_captcha
 */
#[Group('image_captcha')]
#[RunTestsInSeparateProcesses]
final class ImageCaptchaSettingsTranslationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['captcha', 'image_captcha', 'locale'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the label of the built-in font can be translated.
   *
   * Locale skips any translation with an <img> tag on import, so the label
   * must be a translatable string of its own, without the preview image.
   */
  public function testBuiltInFontLabel(): void {
    ConfigurableLanguage::createFromLangcode('de')->save();
    $this->config('system.site')->set('default_langcode', 'de')->save();

    $translation = 'In PHP eingebaute Schriftart';
    // @todo Call LocaleXss::stringIsSafe() directly once Drupal 11.5 is the
    //   minimum supported version.
    if (class_exists(LocaleXss::class)) {
      $is_safe = LocaleXss::stringIsSafe($translation);
    }
    else {
      // @phpstan-ignore function.deprecated
      $is_safe = locale_string_is_safe($translation);
    }
    $this->assertTrue($is_safe);
    $storage = $this->container->get('locale.storage');
    $source = $storage->createString(['source' => 'PHP built-in font'])->save();
    $storage->createTranslation([
      'lid' => $source->lid,
      'language' => 'de',
      'translation' => $translation,
    ])->save();

    $this->drupalLogin($this->drupalCreateUser(['administer CAPTCHA settings']));
    $this->drupalGet('admin/config/people/captcha/image_captcha');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($translation);
    $this->assertSession()->elementExists('css', 'img[src$="/admin/config/people/captcha/image_captcha/font_preview/BUILTIN"]');
  }

}
