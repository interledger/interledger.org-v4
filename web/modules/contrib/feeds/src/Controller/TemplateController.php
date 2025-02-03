<?php

namespace Drupal\feeds\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Plugin\Type\Parser\ParserWithTemplateInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for downloading a parser template.
 */
class TemplateController extends ControllerBase {

  /**
   * Returns a template to be downloaded for a feed type.
   *
   * @param \Drupal\feeds\FeedTypeInterface $feeds_feed_type
   *   The feed type to generate a template for.
   * @param \Drupal\feeds\FeedInterface $feeds_feed
   *   (optional) The feed to generate a template for.
   */
  public function page(FeedTypeInterface $feeds_feed_type, ?FeedInterface $feeds_feed = NULL) {
    $parser = $feeds_feed_type->getParser();
    if (!$parser instanceof ParserWithTemplateInterface) {
      // Parser doesn't support providing a template. Abort.
      throw new NotFoundHttpException();
    }

    return $parser->getTemplate($feeds_feed_type, $feeds_feed);
  }

  /**
   * Returns a template to be downloaded for a feed.
   *
   * @param \Drupal\feeds\FeedInterface $feeds_feed
   *   The feed to generate a template for.
   */
  public function feedPage(FeedInterface $feeds_feed) {
    return $this->page($feeds_feed->getType(), $feeds_feed);
  }

}
