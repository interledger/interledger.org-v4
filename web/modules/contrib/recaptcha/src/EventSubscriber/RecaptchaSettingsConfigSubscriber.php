<?php

namespace Drupal\recaptcha\EventSubscriber;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The recaptcha settings config subscriber.
 *
 * A subscriber rebuilding the library info when the "use_globally" setting is
 * updated.
 */
class RecaptchaSettingsConfigSubscriber implements EventSubscriberInterface {

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Constructs a RecaptchaSettingsConfigSubscriber object.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   */
  public function __construct(CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * Invalidates the library_info tag.
   *
   * Invalidates the library_info tag when the value of recaptcha.settings
   * use_globally is changed.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The Event to process.
   */
  public function onSave(ConfigCrudEvent $event) {
    if ($event->getConfig()->getName() === 'recaptcha.settings') {
      if ($event->isChanged('use_globally')) {
        $this->cacheTagsInvalidator->invalidateTags(['library_info']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onSave'];
    return $events;
  }

}
