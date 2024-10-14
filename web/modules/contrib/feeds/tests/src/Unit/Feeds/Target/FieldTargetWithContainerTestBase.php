<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Base class for testing feeds field targets with container.
 */
abstract class FieldTargetWithContainerTestBase extends FieldTargetTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    $language_manager = $this->createMock(LanguageManagerInterface::class);
    $language = $this->createMock(LanguageInterface::class);
    $language->expects($this->any())
      ->method('getId')
      ->willReturn('en');
    $language_manager->expects($this->any())
      ->method('getCurrentLanguage')
      ->willReturn($language);
    $container->set('language_manager', $language_manager);

    \Drupal::setContainer($container);
  }

}
