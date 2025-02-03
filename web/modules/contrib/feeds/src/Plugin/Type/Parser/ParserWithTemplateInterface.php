<?php

namespace Drupal\feeds\Plugin\Type\Parser;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedTypeInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface for parsers that provide an import template.
 */
interface ParserWithTemplateInterface extends ParserInterface {

  /**
   * Returns the import template, along with headers.
   *
   * @param \Drupal\feeds\FeedTypeInterface $feed_type
   *   The feed type to generate a template for.
   * @param \Drupal\feeds\FeedInterface $feed
   *   (optional) The feed to generate a template for.
   *
   * @return Symfony\Component\HttpFoundation\Response
   *   The response, with the correct headers.
   */
  public function getTemplate(FeedTypeInterface $feed_type, ?FeedInterface $feed = NULL): Response;

  /**
   * Returns the content for the import template.
   *
   * @param \Drupal\feeds\FeedTypeInterface $feed_type
   *   The feed type to generate a template for.
   * @param \Drupal\feeds\FeedInterface $feed
   *   (optional) The feed to generate a template for.
   *
   * @return string
   *   The template contents.
   */
  public function getTemplateContents(FeedTypeInterface $feed_type, ?FeedInterface $feed = NULL): string;

}
