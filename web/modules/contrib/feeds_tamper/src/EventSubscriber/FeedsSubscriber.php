<?php

namespace Drupal\feeds_tamper\EventSubscriber;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\Feeds\Item\ValidatableItemInterface;
use Drupal\feeds\Plugin\Type\Source\SourceInterface;
use Drupal\feeds\StateInterface;
use Drupal\feeds_tamper\Adapter\TamperableFeedItemAdapter;
use Drupal\feeds_tamper\FeedTypeTamperManagerInterface;
use Drupal\tamper\Exception\SkipTamperDataException;
use Drupal\tamper\Exception\SkipTamperItemException;
use Drupal\tamper\ItemUsageInterface;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber to Feeds events.
 *
 * This is where Tamper plugins are applied to the Feeds parser result, which
 * will modify the feed items. This happens after parsing and before going
 * into processing.
 */
class FeedsSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * A feed type meta object.
   *
   * @var \Drupal\feeds_tamper\FeedTypeTamperManagerInterface
   */
  protected $tamperManager;

  /**
   * Constructs a new FeedsSubscriber object.
   *
   * @param \Drupal\feeds_tamper\FeedTypeTamperManagerInterface $tamper_manager
   *   A feed type meta object.
   */
  public function __construct(FeedTypeTamperManagerInterface $tamper_manager) {
    $this->tamperManager = $tamper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[FeedsEvents::PARSE][] = ['afterParse', FeedsEvents::AFTER];
    return $events;
  }

  /**
   * Acts on parser result.
   */
  public function afterParse(ParseEvent $event) {
    /** @var \Drupal\feeds\FeedInterface $feed */
    $feed = $event->getFeed();

    /** @var \Drupal\feeds_tamper\FeedTypeTamperMetaInterface $tamper_meta */
    $tamper_meta = $this->tamperManager->getTamperMeta($feed->getType());

    // Load the tamper plugins that need to be applied to Feeds.
    $tampers_by_source = $tamper_meta->getTampersGroupedBySource();

    // Abort if there are no tampers to apply on the current feed.
    if (empty($tampers_by_source)) {
      return;
    }

    /** @var \Drupal\feeds\Result\ParserResultInterface $result */
    $result = $event->getParserResult();

    for ($i = 0; $i < $result->count(); $i++) {
      if (!$result->offsetExists($i)) {
        break;
      }

      /** @var \Drupal\feeds\Feeds\Item\ItemInterface $item */
      $item = $result->offsetGet($i);

      try {
        $this->alterItem($item, $event, $tampers_by_source);
      }
      catch (SkipTamperItemException $e) {
        $result->offsetUnset($i);
        $i--;
      }
    }
  }

  /**
   * Alters a single item.
   *
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to make modifications on.
   * @param \Drupal\feeds\Event\ParseEvent $event
   *   The parse event.
   * @param \Drupal\tamper\TamperInterface[][] $tampers_by_source
   *   A list of tampers to apply, grouped by source.
   */
  protected function alterItem(ItemInterface $item, ParseEvent $event, array $tampers_by_source) {
    $tamperable_item = new TamperableFeedItemAdapter($item);
    foreach ($tampers_by_source as $source => $tampers) {
      try {
        // Get the value for a source.
        $item_value = $item->get($source);
        $multiple = is_array($item_value) && !empty($item_value);

        /** @var \Drupal\tamper\TamperInterface $tamper */
        foreach ($tampers as $tamper) {
          $definition = $tamper->getPluginDefinition();

          // Check if the Tamper plugin uses any source fields from the item.
          // Source fields provided by FeedsSource plugins then need to be
          // lazy loaded.
          if ($tamper instanceof ItemUsageInterface) {
            $this->lazyLoadSources($item, $tamperable_item, $tamper, $event->getFeed());
          }

          // Many plugins expect a scalar value but the current value of the
          // pipeline might be multiple scalars and in this case the current
          // value needs to be iterated and each scalar separately transformed.
          if ($multiple && !$definition['handle_multiples']) {
            $new_value = [];
            // @todo throw exception if $item_value is not an array.
            foreach ($item_value as $scalar_value) {
              $new_value[] = $tamper->tamper($scalar_value, $tamperable_item);
            }
            $item_value = $new_value;
          }
          else {
            $item_value = $tamper->tamper($item_value, $tamperable_item);
            $multiple = $tamper->multiple();
          }

          // Write the changed value to the item after each applied tamper, so
          // that Tamper plugins that work with the tamperable item have the
          // most up to date data there.
          $item->set($source, $item_value);
        }
      }
      catch (SkipTamperDataException $e) {
        // @todo We would rather unset the source, but that isn't possible yet
        // with ItemInterface.
        $item->set($source, NULL);
      }
      catch (SkipTamperItemException $e) {
        // Should be caught by ::afterParse().
        throw $e;
      }
      catch (\Exception $e) {
        // An error happened. Catch exception and set a message on the feed.
        /** @var \Drupal\feeds\StateInterface $state */
        $state = $event->getFeed()->getState(StateInterface::PARSE);
        $tamper_label = $tamper->getSetting('label');
        $tamper_label = ($tamper_label !== '' && $tamper_label !== NULL) ? $tamper_label : $tamper->getPluginId();
        $message = $this->t('Tampering failed for source %source when trying to applying the tamper %label: @exception', [
          '%label' => $tamper_label,
          '%source' => $source,
          '@exception' => $e->getMessage(),
        ]);
        $state->setMessage($message, 'warning');

        if ($item instanceof ValidatableItemInterface) {
          $item->markInvalid(sprintf('Applying tamper "%s" on source "%s" failed with the error "%s".', $tamper_label, $source, $e->getMessage()));
        }
      }
    }
  }

  /**
   * Lazy loads sources from FeedsSource plugins, if used by the Tamper plugin.
   *
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The Feeds item to set the source for.
   * @param \Drupal\tamper\TamperableItemInterface $tamperable_item
   *   The Tamper item (Feeds item adapted) to set the source for.
   * @param \Drupal\tamper\TamperInterface $tamper
   *   The Tamper plugin that is about to be applied.
   * @param \Drupal\feeds\Feeds\FeedInterface $feed
   *   The feed that is being imported.
   */
  protected function lazyLoadSources(ItemInterface $item, TamperableItemInterface $tamperable_item, TamperInterface $tamper, FeedInterface $feed) {
    if (!$tamper instanceof ItemUsageInterface) {
      // The Tamper plugin needs to implement ItemUsageInterface, else it cannot
      // tell us which properties of the item are being used.
      return;
    }

    // Get the feed type.
    $feed_type = $feed->getType();

    // Get used Tamper sources.
    $tamper_sources = $tamper->getUsedSourceProperties($tamperable_item);

    // Get available mapping sources.
    $mapping_sources = $feed_type->getMappingSources();

    // Limit mapping source list to those that are used by the Tamper plugin
    // first.
    $sources = array_intersect_key($mapping_sources, array_flip($tamper_sources));

    // Limit list of sources by those that have an ID property. Because only
    // these can belong to a FeedsSource plugin.
    $sources = array_filter($sources, function ($source) {
      return isset($source['id']);
    });

    // Loop through all sources that are possibly provided by a FeedsSource
    // plugin and load the data for those that aren't already loaded.
    foreach ($sources as $source_name => $source) {
      if ($item->get($source_name)) {
        // This source is already loaded.
        continue;
      }

      // Load the data for this source, if it is indeed provided by a
      // FeedsSource plugin.
      $source_plugin = $feed_type->getSourcePlugin($source_name);
      if ($source_plugin instanceof SourceInterface) {
        $item->set($source_name, $source_plugin->getSourceElement($feed, $item));
      }
    }
  }

}
