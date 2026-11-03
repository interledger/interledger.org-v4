<?php

declare(strict_types=1);

namespace Drupal\captcha\Hook;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Requirements hook implementations for the CAPTCHA module.
 *
 * Registered in captcha.services.yml so that captcha_requirements() can use
 * it on core older than 11.1, which does not register src/Hook classes.
 */
final class CaptchaRequirementsHooks {

  use StringTranslationTrait;

  /**
   * Constructs a CaptchaRequirementsHooks object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The string translation service.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected StateInterface $state,
    TranslationInterface $stringTranslation,
  ) {
    $this->stringTranslation = $stringTranslation;
  }

  /**
   * Implements hook_runtime_requirements().
   *
   * @return array
   *   The requirements, keyed by name.
   */
  #[Hook('runtime_requirements')]
  public function runtimeRequirements(): array {
    $requirements = [];

    if ($this->configFactory->get('captcha.settings')->get('enable_stats')) {
      // Show the wrong response counter in the status report.
      $requirements['captcha_wrong_response_counter'] = [
        'title' => $this->t('CAPTCHA'),
        'value' => $this->formatPlural(
          (int) $this->state->get('captcha.wrong_response_counter', 0),
          'Already 1 blocked form submission',
          'Already @count blocked form submissions'
        ),
        'severity' => DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '11.2.0', fn() => RequirementSeverity::Info, fn() => REQUIREMENT_INFO),
      ];
    }

    return $requirements;
  }

}
