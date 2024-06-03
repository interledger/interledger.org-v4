<?php

namespace Drupal\securitytxt;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Securitytxt serializer class.
 *
 * Formats the security.txt and security.txt.sig output files.
 */
class SecuritytxtSerializer {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * Constructs our SecuritytxtSerializer.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(DateFormatterInterface $date_formatter) {
    $this->dateFormatter = $date_formatter;
  }

  /**
   * Gets the body of a security.txt file.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $settings
   *   A 'securitytxt.settings' config instance.
   *
   * @return string
   *   The body of a security.txt file.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   When the security.txt file is disabled.
   */
  public function getSecuritytxtFile(ImmutableConfig $settings) {
    $enabled = $settings->get('enabled');
    $enabled_signature = $settings->get('enabled_signature');
    $contact_email = $settings->get('contact_email');
    $contact_phone = $settings->get('contact_phone');
    $contact_page_url = $settings->get('contact_page_url');
    $encryption_key_url = $settings->get('encryption_key_url');
    $expiry_date = $settings->get('expiry_date');
    $policy_url = $settings->get('policy_url');
    $acknowledgments_url = $settings->get('acknowledgments_url');
    $hiring_url = $settings->get('hiring_url');
    $preferred_languages = $settings->get('preferred_languages');
    $canonical_urls = $settings->get('canonical_urls');

    if ($enabled) {
      $content = '';

      if ($contact_email != '') {
        $content .= 'Contact: mailto:' . $contact_email . "\n";
      }

      if ($contact_phone) {
        $content .= 'Contact: tel:' . $contact_phone . "\n";
      }

      if ($contact_page_url != '') {
        $content .= 'Contact: ' . $contact_page_url . "\n";
      }

      if ($encryption_key_url != '') {
        $content .= 'Encryption: ' . $encryption_key_url . "\n";
      }

      if ($expiry_date != '') {
        $content .= 'Expires: ' . $this->dateFormatter->format($expiry_date, 'custom', \DateTime::ATOM) . "\n";
      }

      if ($policy_url != '') {
        $content .= 'Policy: ' . $policy_url . "\n";
      }

      if ($acknowledgments_url != '') {
        $content .= 'Acknowledgments: ' . $acknowledgments_url . "\n";
      }

      if ($hiring_url != '') {
        $content .= 'Hiring: ' . $hiring_url . "\n";
      }

      if ($enabled_signature) {
        $signature_text = $settings->get('signature_text');
        $content = "-----BEGIN PGP SIGNED MESSAGE-----\n\n" . $content;
        $content .= "\n-----BEGIN PGP SIGNATURE-----\n" . $signature_text . "\n-----END PGP SIGNATURE-----\n";
      }

      if ($preferred_languages != '') {
        $content .= 'Preferred-Languages: ' . $preferred_languages . "\n";
      }

      if ($canonical_urls != '') {
        $content .= $this->formatCanonical($canonical_urls);
      }

      return $content;
    }
    else {
      throw new NotFoundHttpException();
    }
  }

  /**
   * Gets the body of a security.txt.sig file.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $settings
   *   A 'securitytxt.settings' config instance.
   *
   * @return string
   *   The body of a security.txt.sig file.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   When the security.txt file is disabled.
   */
  public function getSecuritytxtSignature(ImmutableConfig $settings) {
    $enabled = $settings->get('enabled');
    $signature_text = $settings->get('signature_text');

    if ($enabled) {
      return $signature_text;
    }
    else {
      throw new NotFoundHttpException();
    }
  }

  /**
   * Formats the canonical urls for the security.txt.
   *
   * @param string $canonical
   *   The canonical urls that need to be formatted.
   *
   * @return string
   *   The canonical urls for the security.txt
   */
  protected function formatCanonical(string $canonical = ''): string {
    if ($canonical === '') {
      return '';
    }

    $formatted_canonical = '';
    $canonical_urls = preg_split('/\r\n|\r|\n/', $canonical);

    foreach ($canonical_urls as $canonical_url) {

      // URL must start with https:// as per Section 2.7.2 of [RFC7230].
      if (!str_starts_with($canonical_url, 'https://')) {
        continue;
      }

      $formatted_canonical .= 'Canonical: ' . $canonical_url . "\n";
    }

    return $formatted_canonical;
  }

}
