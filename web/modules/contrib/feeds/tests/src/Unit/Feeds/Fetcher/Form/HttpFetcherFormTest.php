<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Fetcher\Form;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;
use Drupal\feeds\Feeds\Fetcher\Form\HttpFetcherForm;
use Drupal\feeds\Plugin\Type\FeedsPluginInterface;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Fetcher\Form\HttpFetcherForm
 * @group feeds
 */
class HttpFetcherFormTest extends FeedsUnitTestCase {

  /**
   * Tests the configuration form.
   *
   * @covers ::buildConfigurationForm
   */
  public function testConfigurationForm() {
    // Urls are created using a global service.
    $link_generator = $this->prophesize(LinkGeneratorInterface::class);
    $link_generator->generate(Argument::type('string'), Argument::type(Url::class))
      ->willReturn('<a href="https://www.drupal.org/project/feeds/issues/3341361" target="_blank">https://www.drupal.org/project/feeds/issues/3341361</a>');

    $container = new ContainerBuilder();
    $container->set('link_generator', $link_generator->reveal());
    \Drupal::setContainer($container);

    $form_object = new HttpFetcherForm();

    $form_object->setPlugin($this->createMock(FeedsPluginInterface::class));

    $form_object->setStringTranslation($this->getStringTranslationStub());

    $form = $form_object->buildConfigurationForm([], new FormState());
    $this->assertIsArray($form);
    $this->assertNotEmpty($form);
  }

}
