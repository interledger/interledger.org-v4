<?php

declare(strict_types=1);

namespace Drupal\admin_audit_trail\Plugin\views\filter;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\InOperator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Exposes audit trail operations to Views.
 */
#[ViewsFilter("audit_trail_operations")]
class AuditTrailOperations extends InOperator {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->database = $container->get('database');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions(): ?array {
    if (!isset($this->valueOptions)) {
      $this->valueOptions = [];

      // Query distinct operations from the database.
      $query = $this->database->select('admin_audit_trail', 'aat')
        ->fields('aat', ['operation'])
        ->distinct()
        ->orderBy('operation', 'ASC');

      $result = $query->execute();
      foreach ($result as $row) {
        if (!empty($row->operation)) {
          $this->valueOptions[$row->operation] = ucfirst($row->operation);
        }
      }
    }
    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state): void {
    parent::valueForm($form, $form_state);
    $form['value']['#access'] = !empty($form['value']['#options']);
  }

}
