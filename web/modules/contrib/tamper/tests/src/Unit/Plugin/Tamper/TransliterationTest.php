<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\Component\Transliteration\PhpTransliteration;
use Drupal\tamper\Plugin\Tamper\Transliteration;

/**
 * Tests the transliteration plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Transliteration
 * @group tamper
 */
class TransliterationTest extends TamperPluginTestBase {

  /**
   * A transliteration instance.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    $config = [
      'source_definition' => $this->getMockSourceDefinition(),
    ];
    return new Transliteration($config, 'transliteration', [], $this->transliteration);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->transliteration = new PhpTransliteration();
    parent::setUp();
  }

  /**
   * Tests transliteration transformation of non-alphanumeric characters.
   */
  public function testTransliterationTransform() {
    $original = '90000012345678_Jäätelöä_Åbo_Spøgelsesjægerne_Günther_áé';
    $expected = '90000012345678_Jaateloa_Abo_Spogelsesjaegerne_Gunther_ae';

    $plugin = $this->instantiatePlugin();
    $this->assertEquals($expected, $plugin->tamper($original));
  }

}
