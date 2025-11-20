# Security.txt

The Security.txt module provides an implementation of the security.txt draft RFC
standard. Its purpose is to provide a standardized way to document your
website’s security contact details and policy. This allows users and security
researchers to securely disclose security vulnerabilities to you.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/securitytxt)

To submit bug reports and feature suggestions, or to track changes
[issue queue](https://www.drupal.org/project/issues/securitytxt)

## Table of Contents

- Installation
- Configuration
  1. Permissions
  1. Security.txt configuration
  1. Security.txt signing
- Use
- Further reading
- Maintainers

## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

## Configuration

Once you have installed this module, you will want to perform the following
configuration.

### 1. Permissions

You control the permissions granted to each role at '/admin/people/permissions':

- You will almost certainly want to give everyone the 'View security.txt'
  permission, i.e. give it to both the 'Anonymous User' and `Authenticated User'
  roles.
- You will only want to give the `Administer security.txt' permission to very
  trusted roles.

### 2 Security.txt configuration

The Security.txt configuration page can be found under 'System' on the Drupal
configuration page. Fill in all the details you want to add to your
'security.txt' file, then press the 'Save configuration' button. You should then
proceed to the 'Sign' tab of the configuration form.

### 3 Security.txt signing

You can provide a digital signature for your 'security.txt' file by following
the instructions on the 'Sign' tab of the module’s configuration page.

## Use

Once you have completed the configuration of the Security.txt module, your
security.txt and security.txt.sig files will be available at the following
standard URLs:

- /.well-known/security.txt
- /.well-known/security.txt.sig

## Further reading

- Learn more about the [security.txt standard](https://securitytxt.org/)
- Read the [draft RFC](https://tools.ietf.org/html/draft-foudil-securitytxt-02)

## Maintainers

- Daniel May - [danieljrmay](https://www.drupal.org/u/danieljrmay)
- Norman Kämper-Leymann - [leymannx](https://www.drupal.org/u/leymannx)
