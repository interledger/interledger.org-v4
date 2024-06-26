<?php

namespace Drupal\feeds\Feeds\Item;

/**
 * Defines an item class for use with an RSS/Atom parser.
 */
class SitemapItem extends BaseItem {

  /**
   * URL of the feed item.
   *
   * @var string
   */
  protected $url;

  /**
   * Last modified date as UNIX time GMT of the feed item.
   *
   * @var int
   */
  protected $lastmod;

  /**
   * How frequently the page is likely to change.
   *
   * For example 'monthly' or 'weekly'.
   *
   * @var string
   */
  protected $changefreq;

  /**
   * The priority of this URL relative to other URLs on the site.
   *
   * @var string
   */
  protected $priority;

}
