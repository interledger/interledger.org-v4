<?php

declare(strict_types=1);

namespace Drupal\Tests\captcha\Kernel;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the global CAPTCHA on the site installation configuration form.
 *
 * @group captcha
 */
#[Group('captcha')]
#[RunTestsInSeparateProcesses]
final class CaptchaInstallConfigureFormTest extends KernelTestBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['captcha', 'system', 'user'];

  /**
   * The form ID.
   */
  protected string $formId;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return $this->formId;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['submit'] = ['#type' => 'submit', '#value' => 'Save'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
  }

  /**
   * Tests that a global CAPTCHA does not block an unattended installation.
   */
  public function testInstallConfigureForm(): void {
    $this->installSchema('captcha', ['captcha_sessions']);
    $this->installConfig('captcha');
    $this->config('captcha.settings')->set('enable_globally', TRUE)->save();

    $this->formId = 'captcha_test_form';
    $this->assertArrayHasKey('captcha', $this->container->get('form_builder')->getForm($this));

    // Simulate the installer, which submits install_configure_form without a
    // CAPTCHA response.
    $GLOBALS['install_state'] = ['installation_finished' => FALSE];
    try {
      $this->formId = 'install_configure_form';
      $form_state = new FormState();
      $this->container->get('form_builder')->submitForm($this, $form_state);
      $this->assertSame([], $form_state->getErrors());
    }
    finally {
      unset($GLOBALS['install_state']);
    }
  }

}
