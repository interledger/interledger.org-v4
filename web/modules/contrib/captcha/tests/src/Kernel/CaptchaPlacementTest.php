<?php

declare(strict_types=1);

namespace Drupal\Tests\captcha\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests CAPTCHA placement logic in _captcha_get_captcha_placement().
 *
 * @group captcha
 */
#[Group('captcha')]
#[RunTestsInSeparateProcesses]
final class CaptchaPlacementTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['captcha'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    \Drupal::moduleHandler()->loadInclude('captcha', 'inc');
  }

  /**
   * Tests placement when the form has webform_actions nested under elements.
   *
   * This simulates the Webform module's form structure when the
   * submit button(s) are customized, where actions are placed in
   * $form['elements']['actions'] with #type 'webform_actions'.
   */
  public function testWebformActionsPlacement(): void {
    $form = [
      'element' => ['#markup' => 'Some element'],
      'elements' => [
        'actions' => [
          '#type' => 'webform_actions',
          'submit' => ['#type' => 'submit', '#value' => 'Submit'],
        ],
      ],
    ];

    $placement = _captcha_get_captcha_placement('test_webform_actions', $form);

    $this->assertSame(['elements'], $placement['path']);
    $this->assertSame('actions', $placement['key']);
    $this->assertNull($placement['weight']);
  }

  /**
   * Tests placement when the webform_actions element has a custom key.
   *
   * A webform can hold several "Submit button(s)" elements under any key; the
   * CAPTCHA goes before the last one, keeping the given weight.
   */
  public function testWebformActionsCustomKeyPlacement(): void {
    $form = [
      'elements' => [
        'name' => ['#type' => 'textfield'],
        'top_buttons' => ['#type' => 'webform_actions'],
        'message' => ['#type' => 'textarea'],
        'bottom_buttons' => [
          '#type' => 'webform_actions',
          '#weight' => 5,
        ],
      ],
      'actions' => [
        '#type' => 'actions',
        'submit' => ['#type' => 'submit', '#value' => 'Submit'],
      ],
    ];

    $placement = _captcha_get_captcha_placement('test_webform_custom_key', $form);

    $this->assertSame(['elements'], $placement['path']);
    $this->assertSame('bottom_buttons', $placement['key']);
    $this->assertSame(5, $placement['weight']);
  }

  /**
   * Tests that a CAPTCHA is inserted right before webform_actions.
   */
  public function testWebformActionsInsertion(): void {
    $form = [
      'elements' => [
        'name' => ['#type' => 'textfield'],
        'actions' => ['#type' => 'webform_actions'],
      ],
      'actions' => [
        '#type' => 'actions',
        'submit' => ['#type' => 'submit', '#value' => 'Submit'],
      ],
    ];

    $placement = _captcha_get_captcha_placement('test_webform_insertion', $form);
    $this->container->get('captcha.helper')
      ->insertCaptchaElement($form, $placement, ['#type' => 'captcha']);

    $this->assertSame(['name', 'captcha', 'actions'], array_keys($form['elements']));
    $this->assertArrayNotHasKey('captcha', $form);
  }

  /**
   * Tests placement with a standard top-level actions element.
   */
  public function testStandardActionsPlacement(): void {
    $form = [
      'element' => ['#markup' => 'Some element'],
      'actions' => [
        '#type' => 'actions',
        '#weight' => 100,
        'submit' => ['#type' => 'submit', '#value' => 'Submit'],
      ],
    ];

    $placement = _captcha_get_captcha_placement('test_standard_actions', $form);

    $this->assertSame([], $placement['path']);
    $this->assertSame('actions', $placement['key']);
    $this->assertSame(99, $placement['weight']);
  }

  /**
   * Tests fallback placement when no actions group exists.
   */
  public function testFallbackButtonPlacement(): void {
    $form = [
      'element' => ['#markup' => 'Some element'],
      'submit' => [
        '#type' => 'submit',
        '#value' => 'Submit',
        '#weight' => 50,
      ],
    ];

    $placement = _captcha_get_captcha_placement('test_fallback', $form);

    $this->assertSame([], $placement['path']);
    $this->assertSame('submit', $placement['key']);
  }

  /**
   * Tests placement without any buttons (should return NULL).
   */
  public function testNoButtonsPlacement(): void {
    $form = [
      'element' => ['#markup' => 'Some element'],
    ];

    $placement = _captcha_get_captcha_placement('test_no_buttons', $form);

    $this->assertNull($placement);
  }

  /**
   * Tests that webform_actions placement takes priority over top-level actions.
   */
  public function testWebformActionsPriority(): void {
    $form = [
      'element' => ['#markup' => 'Some element'],
      'elements' => [
        'actions' => [
          '#type' => 'webform_actions',
          'submit' => ['#type' => 'submit', '#value' => 'Submit'],
        ],
      ],
      'actions' => [
        '#type' => 'actions',
        '#weight' => 200,
        'submit' => ['#type' => 'submit', '#value' => 'Submit'],
      ],
    ];

    $placement = _captcha_get_captcha_placement('test_priority', $form);

    $this->assertSame(['elements'], $placement['path']);
    $this->assertSame('actions', $placement['key']);
    $this->assertNull($placement['weight']);
  }

}
