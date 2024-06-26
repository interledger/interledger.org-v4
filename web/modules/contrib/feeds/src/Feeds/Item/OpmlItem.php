<?php

namespace Drupal\feeds\Feeds\Item;

/**
 * Defines an item class for use with an OPML document.
 */
class OpmlItem extends BaseItem {

  /**
   * Title of the feed.
   *
   * @var string
   */
  protected $title;

  /**
   * URL of the feed.
   *
   * @var string
   */
  protected $xmlurl;

  /**
   * The categories of the feed.
   *
   * @var array
   */
  protected $categories;

  /**
   * The URL of the site that provides the feed.
   *
   * @var string
   */
  protected $htmlurl;

}
