<?php

declare(strict_types=1);

namespace Drupal\captcha_point_entity_test\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an entity type whose ID collides with captcha_point_load().
 *
 * @ConfigEntityType(
 *   id = "point",
 *   label = @Translation("Point"),
 *   config_prefix = "point",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *   },
 * )
 */
#[ConfigEntityType(
  id: 'point',
  label: new TranslatableMarkup('Point'),
  config_prefix: 'point',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
  ],
  config_export: [
    'id',
    'label',
  ],
)]
final class Point extends ConfigEntityBase {

  /**
   * The point ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The point label.
   *
   * @var string
   */
  protected $label;

}
