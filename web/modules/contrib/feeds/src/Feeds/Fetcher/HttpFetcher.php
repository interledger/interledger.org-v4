<?php

namespace Drupal\feeds\Feeds\Fetcher;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\FetchException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\File\FeedsFileSystemInterface;
use Drupal\feeds\Plugin\Type\ClearableInterface;
use Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\Result\HttpFetcherResult;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Utility\Feed;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines an HTTP fetcher.
 *
 * @FeedsFetcher(
 *   id = "http",
 *   title = @Translation("Download from url"),
 *   description = @Translation("Downloads data from a URL using Drupal's HTTP request handler."),
 *   form = {
 *     "configuration" = "Drupal\feeds\Feeds\Fetcher\Form\HttpFetcherForm",
 *     "feed" = "Drupal\feeds\Feeds\Fetcher\Form\HttpFetcherFeedForm",
 *   }
 * )
 */
class HttpFetcher extends PluginBase implements ClearableInterface, FetcherInterface, ContainerFactoryPluginInterface {

  /**
   * The Guzzle client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Drupal file system helper.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Drupal file system helper for Feeds.
   *
   * @var \Drupal\feeds\File\FeedsFileSystemInterface
   */
  protected $feedsFileSystem;

  /**
   * Constructs an UploadFetcher object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \GuzzleHttp\ClientInterface $client
   *   The Guzzle client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The Drupal file system helper.
   * @param \Drupal\feeds\File\FeedsFileSystemInterface $feeds_file_system
   *   The Drupal file system helper for Feeds.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ClientInterface $client, CacheBackendInterface $cache, FileSystemInterface $file_system, FeedsFileSystemInterface $feeds_file_system) {
    $this->client = $client;
    $this->cache = $cache;
    $this->fileSystem = $file_system;
    $this->feedsFileSystem = $feeds_file_system;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('cache.feeds_download'),
      $container->get('file_system'),
      $container->get('feeds.file_system.in_progress'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(FeedInterface $feed, StateInterface $state) {
    $sink = $this->feedsFileSystem->tempnam($feed, 'http_fetcher_');

    // Get cache key if caching is enabled.
    $cache_key = $this->useCache() ? $this->getCacheKey($feed) : FALSE;

    $response = $this->get($feed->getSource(), $sink, $cache_key);
    // @todo Handle redirects.
    // @codingStandardsIgnoreStart
    // $feed->setSource($response->getEffectiveUrl());
    // @codingStandardsIgnoreEnd

    // 304, nothing to see here.
    if ($response->getStatusCode() == Response::HTTP_NOT_MODIFIED) {
      // Since the fetch is getting aborted, delete the downloaded file.
      $this->fileSystem->unlink($sink);

      $state->setMessage($this->t('The feed has not been updated.'));
      throw new EmptyFeedException();
    }
    else {
      // Check if the fetched data has any content by checking the
      // "Content-Length" header.
      $lengths = $response->getHeader('Content-Length');
      if (count($lengths) > 0) {
        $length = reset($lengths);
        if ($length < 1) {
          // The fetched data is empty. Delete the temporary file and abort the
          // fetch process.
          $this->fileSystem->unlink($sink);

          $state->setMessage($this->t('The feed is empty.'));
          throw new EmptyFeedException();
        }
      }
    }

    return new HttpFetcherResult($sink, $response->getHeaders(), $this->fileSystem);
  }

  /**
   * Performs a GET request.
   *
   * @param string $url
   *   The URL to GET.
   * @param string $sink
   *   The location where the downloaded content will be saved. This can be a
   *   resource, path or a StreamInterface object.
   * @param string $cache_key
   *   (optional) The cache key to find cached headers. Defaults to false.
   * @param array $options
   *   (optional) Additional options to pass to the request.
   *   See https://docs.guzzlephp.org/en/stable/request-options.html.
   *
   * @return \Guzzle\Http\Message\Response
   *   A Guzzle response.
   *
   * @throws \Drupal\feeds\Exception\FetchException
   *   Thrown if the GET request failed.
   *
   * @see \GuzzleHttp\RequestOptions
   */
  protected function get($url, $sink, $cache_key = FALSE, array $options = []) {
    $url = Feed::translateSchemes($url);

    $options += [
      RequestOptions::SINK => $sink,
      RequestOptions::TIMEOUT => $this->configuration['request_timeout'],
      RequestOptions::HEADERS => [],
    ];

    $headers = [];

    // Add cached headers if requested.
    if ($cache_key && ($cache = $this->cache->get($cache_key))) {
      if (isset($cache->data['etag'])) {
        $headers['If-None-Match'] = $cache->data['etag'];
      }
      if (isset($cache->data['last-modified'])) {
        $headers['If-Modified-Since'] = $cache->data['last-modified'];
      }
    }

    // Add header options.
    $options[RequestOptions::HEADERS] += $headers;

    try {
      $response = $this->client->getAsync($url, $options)->wait();
    }
    catch (RequestException $e) {
      $args = ['%site' => $url, '%error' => $e->getMessage()];

      // Since the fetch is getting aborted, delete the downloaded file.
      $this->fileSystem->unlink($sink);

      throw new FetchException(strtr('The feed from %site seems to be broken because of error "%error".', $args));
    }

    if ($cache_key) {
      $this->cache->set($cache_key, array_change_key_case($response->getHeaders()));
    }

    return $response;
  }

  /**
   * Returns if the cache should be used.
   *
   * @return bool
   *   True if results should be cached. False otherwise.
   */
  protected function useCache() {
    return !$this->configuration['always_download'];
  }

  /**
   * Returns the download cache key for a given feed.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to find the cache key for.
   *
   * @return string
   *   The cache key for the feed.
   */
  protected function getCacheKey(FeedInterface $feed) {
    return $feed->id() . ':' . hash('sha256', $feed->getSource());
  }

  /**
   * {@inheritdoc}
   */
  public function clear(FeedInterface $feed, StateInterface $state) {
    $this->onFeedDeleteMultiple([$feed]);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // @todo auto_detect_feeds causes issues with downloading files that are
      // not a RSS feed. Set the default to TRUE as soon as that issue is
      // resolved.
      'auto_detect_feeds' => FALSE,
      'use_pubsubhubbub' => FALSE,
      'always_download' => FALSE,
      'fallback_hub' => '',
      'request_timeout' => 30,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function onFeedDeleteMultiple(array $feeds) {
    foreach ($feeds as $feed) {
      $this->cache->delete($this->getCacheKey($feed));
    }
  }

}
