<?php

namespace Drupal\securitytxt\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure the security.txt file.
 */
class SecuritytxtConfigureForm extends ConfigFormBase {

  /**
   * A 'securitytxt.settings' config instance.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * Constructs a SecuritytxtConfigureForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typedConfigManager, LanguageManagerInterface $languageManager) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->settings = $config_factory->getEditable('securitytxt.settings');
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'securitytxt_configure';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['securitytxt.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the security.txt file for your site'),
      '#default_value' => $this->settings->get('enabled'),
      '#description' => $this->t('When enabled the security.txt file will be accessible to all users with the "view securitytxt" permission, you will almost certinaly want to give this permission to everyone i.e. authenticated and anonymous users.'),
    ];

    $form['enabled_signature'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Digitally sign the file'),
      '#default_value' => $this->settings->get('enabled_signature'),
      '#description' => $this->t('When enabled, the security.txt file will be signed with the value you set on the Sign-tab.'),
    ];

    $form['contact'] = [
      '#type' => 'details',
      '#title' => $this->t('Contact'),
      '#open' => TRUE,
      '#description' => $this->t('You must provide at least one means of contact: email, phone or contact page URL.'),
    ];

    $form['contact']['contact_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#default_value' => $this->settings->get('contact_email'),
      '#description' => $this->t('Typically this would be of the form <kbd>security@example.com</kbd>. Leave it blank if you do not want to provide an email address.'),
    ];
    $form['contact']['contact_phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone'),
      '#default_value' => $this->settings->get('contact_phone'),
      '#description' => $this->t('Use full international format e.g. <kbd>+1-201-555-0123</kbd>. Leave it blank if you do not want to provide a phone number.'),
    ];
    $form['contact']['contact_page_url'] = [
      '#type' => 'url',
      '#title' => $this->t('URL'),
      '#default_value' => $this->settings->get('contact_page_url'),
      '#description' => $this->t('The URL of a contact page which should be loaded over HTTPS. Leave it blank if you do not want to provide a contact page.'),
    ];

    $form['encryption'] = [
      '#type' => 'details',
      '#title' => $this->t('Encryption'),
      '#open' => TRUE,
      '#description' => $this->t('Allow people to send you encrypted messages by providing your public key.'),
    ];
    $form['encryption']['encryption_key_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Public key URL'),
      '#default_value' => $this->settings->get('encryption_key_url'),
      '#description' => $this->t('The URL of your public key file, or a page which contains your public key. This URL should use the HTTPS protocol.'),
    ];

    $form['expiry'] = [
      '#type' => 'details',
      '#title' => $this->t('Expires'),
      '#open' => TRUE,
      '#description' => $this->t('An expiry date can help security researchers know if your security.txt file is still valid.'),
    ];
    $timestamp = $this->settings->get('expiry_date') ?? strtotime('+1 year');
    $form['expiry']['expiry_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Expiry date'),
      '#default_value' => DrupalDateTime::createFromTimestamp($timestamp),
      '#required' => TRUE,
      '#description' => $this->t('Set an expiry date to show in your security.txt file.'),
    ];

    $form['policy'] = [
      '#type' => 'details',
      '#title' => $this->t('Policy'),
      '#open' => TRUE,
      '#description' => $this->t('A security and/or disclosure policy can help security researchers understand  how to work with you when reporting security vulnerabilities.'),
    ];
    $form['policy']['policy_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Security policy URL'),
      '#default_value' => $this->settings->get('policy_url'),
      '#description' => $this->t('The URL of a page which provides details of your security and/or disclosure policy. Leave it blank if you do not have such a page.'),
    ];

    $form['acknowledgments'] = [
      '#type' => 'details',
      '#title' => $this->t('Acknowledgments'),
      '#open' => TRUE,
      '#description' => $this->t('A security acknowledgments page should list the individuals or companies that have disclosed security vulnerabilities and worked with you to fix them.'),
    ];
    $form['acknowledgments']['acknowledgments_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Acknowledgments page URL'),
      '#default_value' => $this->settings->get('acknowledgments_url'),
      '#description' => $this->t('The URL of your security acknowledgments page. Leave it blank if you do not have such a page.'),
    ];

    $form['hiring'] = [
      '#type' => 'details',
      '#title' => $this->t('Hiring'),
      '#open' => TRUE,
      '#description' => $this->t('Hiring information can provide details about how to apply for security-related jobs or list security-related job positions.'),
    ];
    $form['hiring']['hiring_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Hiring page URL'),
      '#default_value' => $this->settings->get('hiring_url'),
      '#description' => $this->t('A reference to where security-related job positions or information about applying for a security-related job position can be found. Leave blank if you do not have such information.'),
    ];

    $defaultSiteLanguage = $this->languageManager->getDefaultLanguage()->getId() ?? 'en';
    $form['languages'] = [
      '#type' => 'details',
      '#title' => $this->t('Preferred languages'),
      '#open' => TRUE,
      '#description' => $this->t('The "Preferred-Languages" field can be used to indicate a set of natural languages that are preferred when submitting security reports.'),
    ];
    $form['languages']['preferred_languages'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Preferred languages'),
      '#default_value' => $this->settings->get('preferred_languages') ?? $defaultSiteLanguage,
      '#description' => $this->t('The values within this set are language tags, and this set may list multiple values, separated by commas. For example "en, es, fr" which would be English, Spanish and French.'),
    ];

    $form['canonical'] = [
      '#type' => 'details',
      '#title' => $this->t('Canonical'),
      '#open' => TRUE,
      '#description' => $this->t('The "Canonical" field indicates the canonical URIs where the "security.txt" file is located.'),
    ];
    $form['canonical']['canonical_urls'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Canonical urls'),
      '#default_value' => $this->settings->get('canonical_urls'),
      '#description' => $this->t('If this field indicates a web URI, then it must begin with "https://". Each canonical url should be placed on a new line.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $enabled = $form_state->getValue('enabled');
    $contact_email = $form_state->getValue('contact_email');
    $contact_phone = $form_state->getValue('contact_phone');
    $contact_page_url = $form_state->getValue('contact_page_url');

    /* When enabled, check that at least one contact field is specified. */
    if ($enabled && $contact_email == '' && $contact_phone == '' && $contact_page_url == '') {
      $form_state->setErrorByName('contact', $this->t('You must specify at least one method of contact.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $enabled = $form_state->getValue('enabled');
    $enabled_signature = $form_state->getValue('enabled_signature');
    $contact_email = $form_state->getValue('contact_email');
    $contact_phone = $form_state->getValue('contact_phone');
    $contact_page_url = $form_state->getValue('contact_page_url');
    $encryption_key_url = $form_state->getValue('encryption_key_url');
    $expiry_date = $form_state->getValue('expiry_date');
    $policy_url = $form_state->getValue('policy_url');
    $acknowledgments_url = $form_state->getValue('acknowledgments_url');
    $hiring_url = $form_state->getValue('hiring_url');
    $preferred_languages = $form_state->getValue('preferred_languages');
    $canonical_urls = $form_state->getValue('canonical_urls');

    /* Warn if contact URL is not loaded over HTTPS */
    if ($contact_page_url != '' && substr($contact_page_url, 0, 8) !== 'https://') {
      $this->messenger()->addWarning($this->t('Your contact URL should really be loaded over HTTPS.'));
    }

    /* Warn if encryption URL is not loaded over HTTPS */
    if ($encryption_key_url != '' && substr($encryption_key_url, 0, 8) !== 'https://') {
      $this->messenger()->addWarning($this->t('Your public key URL should really be loaded over HTTPS.'));
    }

    /* Warn if hiring URL is not loaded over HTTPS */
    if ($hiring_url != '' && substr($hiring_url, 0, 8) !== 'https://') {
      $this->messenger()->addWarning($this->t('Your hiring URL should really be loaded over HTTPS.'));
    }

    /* Warn if any canonical_urls URL is not loaded over HTTPS */
    $canonical_urls_extracted = preg_split('/\r\n|\r|\n/', $canonical_urls);
    foreach ($canonical_urls_extracted as $canonical_url) {
      if ($canonical_url != '' && substr($canonical_url, 0, 8) !== 'https://') {
        $this->messenger()->addWarning($this->t('All your canonical URL should really be loaded over HTTPS.'));
      }
    }

    /* Message the user to proceed to the sign page if they have enabled security.txt */
    if ($enabled && $enabled_signature) {
      $this->messenger()->addStatus($this->t(
        'You should now <a href=":sign">sign your security.txt file</a>.',
        [':sign' => Url::fromRoute('securitytxt.sign')->toString()]
      ));
    }

    /* Save the configuration */
    $this->settings
      ->set('enabled', $enabled)
      ->set('enabled_signature', $enabled_signature)
      ->set('contact_email', $contact_email)
      ->set('contact_phone', $contact_phone)
      ->set('contact_page_url', $contact_page_url)
      ->set('encryption_key_url', $encryption_key_url)
      ->set('expiry_date', $expiry_date->getTimestamp())
      ->set('policy_url', $policy_url)
      ->set('acknowledgments_url', $acknowledgments_url)
      ->set('hiring_url', $hiring_url)
      ->set('preferred_languages', $preferred_languages)
      ->set('canonical_urls', $canonical_urls)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
