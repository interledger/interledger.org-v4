# reCAPTCHA for Drupal

The reCAPTCHA module uses the reCAPTCHA web service to improve the CAPTCHA
system and protect email addresses.
This version of the module uses the new Google No CAPTCHA reCAPTCHA API.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/recaptcha).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/recaptcha).


## Table of contents

- Requirements
- Installation
- Configuration
- Known Issues
- Thank You


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Requirements

This module requires the following modules:
- [CAPTCHA module](https://drupal.org/project/captcha)


## Configuration

1. Enable reCAPTCHA and CAPTCHA modules in: *admin/modules*
2. You'll now find a reCAPTCHA tab in the CAPTCHA administration page
   available at: *admin/config/people/captcha/recaptcha*
3. Register your web site [in the reCAPTCHA Administration](
   https://www.google.com/recaptcha/admin/create)
4. Input the site and private keys into the reCAPTCHA settings
5. Visit the Captcha administration page and set where you want the
   reCAPTCHA form to be presented: *admin/config/people/captcha*


## Known Issues

- cURL requests fail because of outdated root certificate. The reCAPTCHA module
  may not able to connect to Google servers and fails to verify the answer.

  [See Issue #2481341](https://www.drupal.org/node/2481341) for more detail.


## Thank You

- Thank you goes to the reCAPTCHA team for all their
  help, support and their amazing Captcha solution
  [recaptcha](https://www.google.com/recaptcha)
