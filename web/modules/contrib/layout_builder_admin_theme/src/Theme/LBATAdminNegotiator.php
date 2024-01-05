<?php

namespace Drupal\layout_builder_admin_theme\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\layout_builder\Form\RevertOverridesForm;
use Drupal\layout_builder\Form\DiscardLayoutChangesForm;

/**
 * Forces the admin theme when using layout builder.
 */
class LBATAdminNegotiator implements ThemeNegotiatorInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Layout Builder Admin Theme - Admin Negotiator constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    // Check if enabled in config.
    $is_enabled = $this->configFactory->get('layout_builder_admin_theme.config')->get('lbat_enable_admin_theme');
    if (!$is_enabled) {
      return FALSE;
    }

    // Get and check the route.
    $route = $route_match->getRouteObject();
    if (empty($route)) {
      return FALSE;
    }

    // Get and check the form.
    $form = $route->getDefault('_entity_form') ?? $route->getDefault('_form');
    if (empty($form)) {
      return FALSE;
    }

    $form_class = ltrim($form, '\\');
    // If this is the Layout Builder revert changes form, apply the admin theme.
    if ($form_class === RevertOverridesForm::class) {
      return TRUE;
    }
    // If this is the Layout Builder discard changes form, apply the admin
    // theme.
    if ($form_class === DiscardLayoutChangesForm::class) {
      return TRUE;
    }

    // If form ends with ".layout_builder" apply the admin theme.
    $form_types = explode('.', $form);
    $form_type = end($form_types);
    if (!empty($form_type) && $form_type === 'layout_builder') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->configFactory->get('system.theme')->get('admin');
  }

}
