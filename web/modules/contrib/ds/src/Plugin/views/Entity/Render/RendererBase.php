<?php

namespace Drupal\ds\Plugin\views\Entity\Render;

use Drupal\views\Entity\Render\EntityTranslationRendererBase;
use Drupal\views\ResultRow;

/**
 * Renders entities in the current language.
 */
abstract class RendererBase extends EntityTranslationRendererBase {

  /**
   * Returns the language code associated with the given row.
   *
   * @param \Drupal\views\ResultRow $row
   *   The result row.
   * @param string $relationship
   *   The relationship to be used.
   *
   * @return string
   */
  public function getLangcodeByRelationship(ResultRow $row, string $relationship): string {
    // This method needs to be overridden if the relationship is needed in the
    // implementation of getLangcode().
    return $this->getLangcode($row);
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(array $result) {
    $this->preRenderByRelationship($result, 'none');
  }

  /**
   * Runs before each entity is rendered if a relationship is needed.
   *
   * @param \Drupal\views\ResultRow[] $result
   *   The full array of results from the query.
   * @param string $relationship
   *   The relationship to be used.
   */
  public function preRenderByRelationship(array $result, string $relationship): void {
    parent::preRenderByRelationship($result, $relationship);
    $this->dsPreRender($result, $relationship);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    return $this->renderByRelationship($row, 'none');
  }

  /**
  * Renders entity data.
   *
   * @param \Drupal\views\ResultRow $row
   *   A single row of the query result.
   * @param string $relationship
   *   The relationship to be used.
   *
   * @return array
   *   A renderable array for the entity data contained in the result row.
   */
  public function renderByRelationship(ResultRow $row, string $relationship): array {
    if ($entity = $this->getEntity($row, $relationship)) {
      $entity_id = $entity->id();
      $langcode = $this->getLangcodeByRelationship($row, $relationship);
      if (isset($this->build[$entity_id][$langcode])) {
        $build = $this->build[$entity_id][$langcode];
        $this->alterBuild($build, $row);
        return $build;
      }
    }

    return [];
  }

  /**
   * Alter the build.
   *
   * @param $build
   * @param $row
   */
  protected function alterBuild(&$build, $row) {
    // Add row index. Remember that in case you want to use this, you will
    // have to remove the cache for this build.
    $build['#row_index'] = $row->index ?? NULL;

    // Delta fields.
    $delta_fields = $this->view->rowPlugin->options['delta_fieldset']['delta_fields'];
    if (!empty($delta_fields)) {
      foreach ($delta_fields as $field) {
        $field_name_delta = $field . '_delta';
        if (isset($row->{$field_name_delta})) {
          $build['#ds_delta'][$field] = $row->{$field_name_delta};
        }
      }
    }
  }

  /**
   * Pre renders all the Display Suite rows.
   *
   * @param array $result
   * @param $relationship
   * @param bool $translation
   */
  protected function dsPreRender(array $result, $relationship, bool $translation = FALSE) {
    if ($result) {

      // Get the view builder to render this entity.
      $view_builder = \Drupal::entityTypeManager()
        ->getViewBuilder($this->entityType->id());

      $i = 0;
      $grouping = [];
      $rendered = FALSE;

      foreach ($result as $row) {
        $group_value_content = '';
        $entity = $this->getEntity($row, $relationship);
        if (!$entity) {
          continue;
        }
        $entity->view = $this->view;

        $entity_id = $entity->id();
        $langcode = $this->getLangcode($row);

        // Default view mode.
        $view_mode = $this->view->rowPlugin->options['view_mode'];

        // Display settings view mode.
        if ($this->view->rowPlugin->options['switch_fieldset']['switch']) {
          $switch = $entity->get('ds_switch')->value;
          if (!empty($switch)) {
            $view_mode = $switch;
          }
        }

        // Change the view mode per row.
        if ($this->view->rowPlugin->options['alternating_fieldset']['alternating']) {
          // Check for paging to determine the view mode.
          $page = $this->view->getPager()->getCurrentPage();
          if (!empty($page) && isset($this->view->rowPlugin->options['alternating_fieldset']['allpages']) && !$this->view->rowPlugin->options['alternating_fieldset']['allpages']) {
            $view_mode = $this->view->rowPlugin->options['view_mode'];
          }
          else {
            $view_mode = $this->view->rowPlugin->options['alternating_fieldset']['item_' . $i] ?? $this->view->rowPlugin->options['view_mode'];
          }
          $i++;
        }

        // The advanced selector invokes hook_ds_views_row_render_entity.
        if ($this->view->rowPlugin->options['advanced_fieldset']['advanced']) {

          $hook = 'ds_views_row_render_entity';
          $implementors = [];
          \Drupal::moduleHandler()->invokeAllWith($hook, function (callable $hook, string $module) use (&$implementors) {
            $implementors[] = $module;
          });
          foreach ($implementors as $module) {
            if ($content = \Drupal::moduleHandler()->invoke($module, 'ds_views_row_render_entity', [$entity, $view_mode])) {
              if (!is_array($content)) {
                $content = ['#markup' => $content];
              }
              $this->build[$entity_id][$langcode] = $content;
              $rendered = TRUE;
            }
          }
        }

        // Give modules a chance to alter the $view_mode. Use $view_mode by ref.
        $view_name = $this->view->storage->id();
        $context = [
          'entity' => $entity,
          'view_name' => $view_name,
          'display' => $this->view->getDisplay(),
        ];
        \Drupal::moduleHandler()->alter('ds_views_view_mode', $view_mode, $context);

        if (!$rendered) {
          if (empty($view_mode)) {
            $view_mode = 'full';
          }
          $this->build[$entity_id][$langcode] = $view_builder->view($entity, $view_mode, $langcode);
        }

        $context = [
          'row' => $row,
          'view' => &$this->view,
          'view_mode' => $view_mode,
        ];
        \Drupal::moduleHandler()->alter('ds_views_row_render_entity', $this->build[$entity_id], $context);

        // Keep a static grouping for this view.
        if ($this->view->rowPlugin->options['grouping_fieldset']['group']) {

          $group_field = $this->view->rowPlugin->options['grouping_fieldset']['group_field'];

          // New way of creating the alias.
          if (strpos($group_field, '|') !== FALSE) {
            [, $ffield] = explode('|', $group_field);
            if (isset($this->view->sort[$ffield]->realField)) {
              $group_field = $this->view->sort[$ffield]->tableAlias . '_' . $this->view->sort[$ffield]->realField;
            }
          }

          // Note, the keys in the $row object are cut of at 60 chars.
          // see views_plugin_query_default.inc.
          if (mb_strlen($group_field) > 60) {
            $group_field = mb_substr($group_field, 0, 60);
          }

          $raw_group_value = $row->{$group_field} ?? '';
          $group_value = $raw_group_value;

          // Special function to format the heading value.
          if (!empty($this->view->rowPlugin->options['grouping_fieldset']['group_field_function'])) {
            $function = $this->view->rowPlugin->options['grouping_fieldset']['group_field_function'];
            if (function_exists($function)) {
              $group_value = $function($raw_group_value, $row->_entity);
            }
          }

          if (!isset($grouping[$group_value]) && !empty($group_value)) {
            $group_value_content = [
              '#markup' => '<h2 class="grouping-title">' . $group_value . '</h2>',
              '#weight' => -5,
            ];
            $grouping[$group_value] = $group_value;
          }
        }

        // Grouping.
        if (!empty($grouping)) {
          if (!empty($group_value_content)) {
            if (!$translation) {
              $this->build[$entity_id] = [
                'title' => $group_value_content,
                'content' => $this->build[$entity_id],
              ];
            }
            else {
              $this->build[$entity_id][$langcode] = [
                'title' => $group_value_content,
                'content' => $this->build[$entity_id][$langcode],
              ];
            }
          }
        }
      }
    }
  }

}
