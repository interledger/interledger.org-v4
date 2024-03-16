<?php

namespace Drupal\Tests\rename_admin_paths\Unit\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\rename_admin_paths\Config;
use Drupal\rename_admin_paths\Form\RenameAdminPathsSettingsForm;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @group tests
 */
class RenameAdminPathsSettingsFormTest extends UnitTestCase {

  use ProphecyTrait;

  public function testValidatePathWithoutValue() {
    $element = [];
    $this->getForm()->validate($element, $this->getInvalidFormState());
  }

  /**
   * @dataProvider validValues
   *
   * @param string $value
   */
  public function testWithValidValue(string $value) {
    $element = ['#value' => $value];
    $this->getForm()->validate($element, $this->getValidFormState());
  }

  /**
   * @dataProvider invalidValues
   *
   * @param string $value
   */
  public function testWithInvalidValue(string $value) {
    $element = ['#value' => $value];
    $this->getForm()->validate($element, $this->getInvalidFormState());
  }

  /**
   * @return \Generator
   */
  public function validValues() {
    yield ['backend'];
    yield ['back-end'];
    yield ['Backend'];
    yield ['Back-End'];
    yield ['Back_End'];
    yield ['Back-End_123'];
    yield ['admin2'];
    yield ['user2'];
  }

  /**
   * @return \Generator
   */
  public function invalidValues() {
    yield ['backend!'];
    yield ['back@end'];
    yield ['(Backend)'];
    yield ['Back~End'];
    yield ['Back=End'];
    yield ['Back-End+123'];
    yield ['admin'];
    yield ['user'];
    yield ['Admin'];
  }

  /**
   * @return RenameAdminPathsSettingsForm
   */
  private function getForm() {
    $config = $this->createMock(Config::class);

    $routeBuilder = $this->createMock(RouteBuilderInterface::class);

    $translator = $this->createMock(TranslationInterface::class);
    $translator->method('translateString')->willReturn('Error');

    return new RenameAdminPathsSettingsForm(
      $config, $routeBuilder, $translator
    );
  }

  /**
   * @return FormStateInterface
   */
  private function getValidFormState() {
    $formState = $this->getProphet()->prophesize(FormStateInterface::class);
    $formState->setError()->shouldNotBeCalled();

    return $formState->reveal();
  }

  /**
   * @return FormStateInterface
   */
  private function getInvalidFormState() {
    $formState = $this->getProphet()->prophesize(FormStateInterface::class);
    $formState->setError(Argument::any(), Argument::any())->shouldBeCalled();

    return $formState->reveal();
  }
}
