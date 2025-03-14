<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\StripTags;

/**
 * Tests the strip tags plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\StripTags
 * @group tamper
 */
class StripTagsTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new StripTags([], 'strip_tags', [], $this->getMockSourceDefinition());
  }

  /**
   * Test the plugin with no tags allowed.
   */
  public function testNoAllowedTags() {
    $config = [
      StripTags::SETTING_ALLOWED_TAGS => NULL,
    ];
    $this->plugin->setConfiguration($config);
    $this->assertEquals('supercalifragilisticexpialidocious', $this->plugin->tamper('super<b>cali</b>fragil<b>istic</b>expi<b>ali</b>docious'));
    $this->assertEquals('HelloWorld', $this->plugin->tamper('Hello<b>World'));
  }

  /**
   * Test the plugin with tags allowed.
   */
  public function testAllowedTags() {
    $config = [
      StripTags::SETTING_ALLOWED_TAGS => '<i>',
    ];
    $this->plugin->setConfiguration($config);
    $this->assertEquals('Chitty<i>Chitty</i>BangBang', $this->plugin->tamper('Chitty<i>Chitty</i><b>Bang</b>Bang'));
  }

  /**
   * Test the plugin behavior without string data.
   */
  public function testNoStringTamper() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be a string.');
    $this->plugin->tamper(['this is an array']);
  }

}
