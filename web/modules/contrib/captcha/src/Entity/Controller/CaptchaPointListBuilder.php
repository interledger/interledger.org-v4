<?php

namespace Drupal\captcha\Entity\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Builds the list of capture points for the captcha point form.
 */
class CaptchaPointListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * @return array
   *   A render array structure of header strings.
   */
  public function buildHeader() {
    $header['form_id'] = $this->t('CAPTCHA Point form ID');
    $header['captcha_type'] = $this->t('CAPTCHA Point challenge type');
    $header['captcha_status'] = $this->t('Status');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   A render array structure of fields for this entity.
   */
  public function buildRow(EntityInterface $entity) {
    $row['form_id'] = $entity->id();
    $row['captcha_type'] = $entity->getCaptchaType();
    $row['captcha_status'] = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');

    return $row + parent::buildRow($entity);
  }

}
