<?php

declare(strict_types=1);

namespace Drupal\captcha\Plugin\CrowdsecScenario;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\crowdsec\Attribute\Scenario;
use Drupal\crowdsec\ScenarioPluginBase;

/**
 * Plugin implementation of the failed captcha scenario.
 */
#[Scenario(
  id: 'captcha:failed_captcha',
  scenario: 'drupal/failed_captcha',
  label: new TranslatableMarkup('Failed CAPTCHAs'),
  description: new TranslatableMarkup('Failed attempts to solve a CAPTCHA.'),
)]
final class FailedCaptchaScenario extends ScenarioPluginBase {}
