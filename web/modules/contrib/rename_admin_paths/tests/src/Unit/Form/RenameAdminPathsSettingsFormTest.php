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
 * Tests the behavior of the module settings form.
 *
 * @group tests
 */
class RenameAdminPathsSettingsFormTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * Test when an invalid value is provided.
   */
  public function testValidatePathWithoutValue() {
    $element = [];
    $this->getForm()->validate($element, $this->getInvalidFormState());
  }

  /**
   * Checks if validation succeeds with a valid value.
   *
   * @param string $value
   *   The form value.
   *
   * @dataProvider validValues
   */
  public function testWithValidValue(string $value) {
    $element = ['#value' => $value];
    $this->getForm()->validate($element, $this->getValidFormState());
  }

  /**
   * Checks if validation succeeds with an invalid value.
   *
   * @param string $value
   *   The form value.
   *
   * @dataProvider invalidValues
   */
  public function testWithInvalidValue(string $value) {
    $element = ['#value' => $value];
    $this->getForm()->validate($element, $this->getInvalidFormState());
  }

  /**
   * Returns a set of valid values.
   *
   * @return \Generator
   *   The set of valid values.
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
   * Returns a set of invalid values.
   *
   * @return \Generator
   *   The set of invalid values.
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
   * Returns the Rename Admin Paths admin settings form.
   *
   * @return \Drupal\rename_admin_paths\Form\RenameAdminPathsSettingsForm
   *   The admin settings form.
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
   * Returns a valid form state.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   A valid form state.
   */
  private function getValidFormState() {
    $formState = $this->getProphet()->prophesize(FormStateInterface::class);
    $formState->setError()->shouldNotBeCalled();

    return $formState->reveal();
  }

  /**
   * Returns an invalid form state.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   An invalid form state.
   */
  private function getInvalidFormState() {
    $formState = $this->getProphet()->prophesize(FormStateInterface::class);
    $formState->setError(Argument::any(), Argument::any())->shouldBeCalled();

    return $formState->reveal();
  }

}
