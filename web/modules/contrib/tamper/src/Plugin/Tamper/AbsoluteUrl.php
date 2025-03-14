<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation for absolute url.
 *
 * @Tamper(
 *   id = "absolute_url",
 *   label = @Translation("Make URLs absolute"),
 *   description = @Translation("Make URLs in markup absolute. (i.e. href='/stuff/things' to href='http://example.com/stuff/things)."),
 *   category = "HTML"
 * )
 */
class AbsoluteUrl extends TamperBase {

  const SETTING_SOURCE = 'base_url_source';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_SOURCE] = NULL;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $sources = [];
    foreach ($this->sourceDefinition->getList() as $key => $label) {
      $sources[$key] = $label;
    }

    $form['description'] = [
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#markup' => $this->t("Make URLs in markup absolute. (i.e. href='/stuff/things' to href='http://example.com/stuff/things)."),
    ];

    $form[self::SETTING_SOURCE] = [
      '#type' => 'radios',
      '#title' => $this->t('Base URL source'),
      '#options' => $sources,
      '#default_value' => $this->getSetting(self::SETTING_SOURCE),
      '#description' => $this->t('The source name which holds the base URL. For example: https://example.com.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([
      self::SETTING_SOURCE => $form_state->getValue(self::SETTING_SOURCE),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, ?TamperableItemInterface $item = NULL) {
    $source = $this->getSetting(self::SETTING_SOURCE);
    if (empty($source)) {
      throw new TamperException('You must define a valid source from the plugin settings.');
    }
    if (!$item instanceof TamperableItemInterface) {
      throw new TamperException('The plugin "absolute_url" needs a tamperable item in order to operate.');
    }

    $site_link = $item->getSourceProperty($source);
    $site_link = $this->convertValueToString($site_link);
    if ($site_link === NULL) {
      throw new TamperException('You must define a valid domain in your base url data source (ie: http://example.com).');
    }

    $base_url_parts = parse_url($site_link);
    if ($base_url_parts === FALSE) {
      throw new TamperException('You must define a valid domain in your base url data source (ie: http://example.com).');
    }

    $data_converted = $this->convertValueToString($data);
    if ($data_converted === NULL) {
      // We couldn't use this data. Return as is.
      return $data;
    }
    if (strlen($data_converted) < 1) {
      // The data appears to be empty. Return as is.
      return $data;
    }

    // Suppress warnings for invalid HTML.
    $errors = libxml_use_internal_errors(TRUE);

    $dom = new \DOMDocument();
    $dom->loadHTML($data_converted);

    libxml_clear_errors();
    libxml_use_internal_errors($errors);

    $urls = [];
    $tags = [
      'a' => 'href',
      'img' => 'src',
      'iframe' => 'src',
      'script' => 'src',
      'object' => 'codebase',
      'link' => 'href',
      'applet' => 'code',
      'base' => 'href',
    ];
    foreach ($tags as $tag_name => $attribute) {
      foreach ($dom->getElementsByTagName($tag_name) as $tag_element) {
        $value = trim($tag_element->getAttribute($attribute));
        $this->convertAbsoluteUrl($value, $urls, $base_url_parts);
      }
    }
    $data_converted = strtr($data_converted, $urls);

    return $data_converted;
  }

  /**
   * Converts a value to a string, if possible.
   *
   * @param mixed $value
   *   The value to convert.
   *
   * @return string|null
   *   The converted value or null if it could not get converted.
   */
  protected function convertValueToString($value): ?string {
    if (is_string($value)) {
      // The value is already a string.
      return $value;
    }
    if (is_scalar($value)) {
      // Cast to a string.
      return (string) $value;
    }
    if (is_null($value)) {
      return '';
    }
    if (is_array($value)) {
      return $this->convertValueToString(reset($value));
    }
    if (is_object($value) && method_exists($value, '__toString')) {
      return (string) $value;
    }

    return NULL;
  }

  /**
   * Convert URL to absolute.
   *
   * @param string $relative_url
   *   The attribute value being converted to absolute.
   *   Expected to be a relative url.
   * @param array &$urls
   *   The list of URLs that were converted to absolute.
   * @param array $base_url_parts
   *   The base URL in parsed format, for example it can consist of the
   *   following:
   *   - scheme;
   *   - host.
   *
   * @see parse_url
   */
  protected function convertAbsoluteUrl(string $relative_url, array &$urls, array $base_url_parts) {
    if (!strlen($relative_url)) {
      return;
    }

    $relative_url_parts = parse_url($relative_url);

    if ($relative_url_parts === FALSE) {
      // Parsing the relative url failed. Abort.
      return;
    }
    if (!empty($relative_url_parts['scheme']) || !empty($relative_url_parts['host'])) {
      // This url already looks like to be absolute. Abort.
      return;
    }

    if (!isset($base_url_parts['scheme'])) {
      // If the base url did not specify scheme, assume it to be 'https'.
      $base_url_parts['scheme'] = 'https';
    }

    // Set the scheme of the url to be converted.
    $relative_url_parts['scheme'] = $base_url_parts['scheme'];

    unset($relative_url_parts['port']);
    unset($relative_url_parts['user']);
    unset($relative_url_parts['pass']);

    // Copy base authority.
    if (!isset($base_url_parts['host']) && isset($base_url_parts['path'])) {
      // If no host is set, but there is a path that looks like to start with a
      // domain name, extract the domain name from the path.
      // Make sure that if there is a remaining path, that the domain name and
      // the path are separated by a slash.
      $regex = '/^([^\/]+\.[a-z]+)\//i';
      $matches = [];
      preg_match($regex, $base_url_parts['path'] . '/', $matches);
      if (isset($matches[1]) && is_string($matches[1]) && strlen($matches[1]) > 0) {
        $base_url_parts['host'] = $matches[1];
        $base_url_parts['path'] = substr($base_url_parts['path'], strlen($matches[1]));
      }
    }
    if (!isset($base_url_parts['host'])) {
      throw new TamperException('You must define a valid domain in your base url data source (ie: http://example.com).');
    }
    $relative_url_parts['host'] = $base_url_parts['host'];
    if (isset($base_url_parts['port'])) {
      $relative_url_parts['port'] = $base_url_parts['port'];
    }
    if (isset($base_url_parts['user'])) {
      $relative_url_parts['user'] = $base_url_parts['user'];
    }
    if (isset($base_url_parts['pass'])) {
      $relative_url_parts['pass'] = $base_url_parts['pass'];
    }

    // If relative URL has no path, use base path.
    if (empty($relative_url_parts['path'])) {
      if (!empty($base_url_parts['path'])) {
        $relative_url_parts['path'] = $base_url_parts['path'];
      }
      if (!isset($relative_url_parts['query']) && isset($base_url_parts['query'])) {
        $relative_url_parts['query'] = $base_url_parts['query'];
      }
      $urls[$relative_url] = $this->joinUrl($relative_url_parts);
      return;
    }

    // If relative URL path doesn't start with /, merge with base path.
    if (strpos($relative_url_parts['path'], '/') !== 0) {
      if (empty($base_url_parts['path'])) {
        $base_url_parts['path'] = '';
      }
      elseif (strrpos($base_url_parts['path'], '/') === strlen($base_url_parts['path']) - 1) {
        // Remove ending slash from the path from the base url if it contains
        // one.
        $base_url_parts['path'] = substr($base_url_parts['path'], 0, strlen($base_url_parts['path']) - 1);
      }
      $relative_url_parts['path'] = $base_url_parts['path'] . '/' . $relative_url_parts['path'];
    }
    $urls[$relative_url] = $this->joinUrl($relative_url_parts);
  }

  /**
   * Join parts of the URL together.
   *
   * @param array $parts
   *   A parsed version of the URL.
   *
   * @return string
   *   The string version of the URL made up from each part.
   */
  protected function joinUrl(array $parts) {
    $url = '';
    if (!empty($parts['scheme'])) {
      $url .= $parts['scheme'] . ':';
    }
    if (isset($parts['host'])) {
      $url .= '//';
      if (isset($parts['user'])) {
        $url .= $parts['user'];
        if (isset($parts['pass'])) {
          $url .= ':' . $parts['pass'];
        }
        $url .= '@';
      }
      // IPv6.
      if (preg_match('/^[\da-f]*:[\da-f.:]+$/ui', $parts['host'])) {
        $url .= '[' . $parts['host'] . ']';
      }
      else {
        // IPv4 or name.
        $url .= $parts['host'];
      }
      if (isset($parts['port'])) {
        $url .= ':' . $parts['port'];
      }
      if (!empty($parts['path']) && $parts['path'][0] != '/') {
        $url .= '/';
      }
    }
    if (!empty($parts['path'])) {
      $url .= $parts['path'];
    }
    if (isset($parts['query'])) {
      $url .= '?' . $parts['query'];
    }
    if (isset($parts['fragment'])) {
      $url .= '#' . $parts['fragment'];
    }
    return $url;
  }

}
