<?php

declare(strict_types=1);

namespace Drupal\Tests\image_captcha\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that the allowed characters are split correctly per character.
 *
 * @group image_captcha
 *
 * @see https://www.drupal.org/project/captcha/issues/3495425
 */
#[Group('image_captcha')]
#[RunTestsInSeparateProcesses]
final class ImageCaptchaUtf8SplitTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['captcha', 'image_captcha'];

  /**
   * Tests _image_captcha_utf8_split() with single and multibyte characters.
   *
   * @dataProvider splitProvider
   */
  #[DataProvider('splitProvider')]
  public function testUtf8Split(string $string, array $expected): void {
    $this->assertSame($expected, _image_captcha_utf8_split($string));
  }

  /**
   * Provides strings and their expected per-character split.
   *
   * @return array
   *   Test cases.
   */
  public static function splitProvider(): array {
    return [
      'ascii' => [
        'abcABC123',
        ['a', 'b', 'c', 'A', 'B', 'C', '1', '2', '3'],
      ],
      'two-byte latin' => [
        'àáâã',
        ['à', 'á', 'â', 'ã'],
      ],
      'three-byte hiragana' => [
        'あかさたな',
        ['あ', 'か', 'さ', 'た', 'な'],
      ],
      'four-byte emoji' => [
        '😀😁',
        ['😀', '😁'],
      ],
      'mixed widths' => [
        'aàあ',
        ['a', 'à', 'あ'],
      ],
    ];
  }

}
