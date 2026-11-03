# CAPTCHA

A CAPTCHA is a challenge-response test most often placed within web forms to
determine whether the user is human. The purpose of CAPTCHA is to block form
submissions by spambots, which are automated scripts that post spam content
everywhere they can. The CAPTCHA module provides this feature to virtually any
user facing web form on a Drupal site.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/captcha)

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/captcha)


## Table of contents

- Requirements
- Conflicts/Known issues
- Installation
- Configuration
- Development
- Maintainers


## Requirements

This module requires no modules outside of Drupal core.


## Conflicts/Known issues

CAPTCHA and page caching do not work together currently. However, the CAPTCHA
module does support the Drupal core page caching mechanism: it just disables the
caching of the pages where it has to put its challenges.

If you use other caching mechanisms, it is possible that CAPTCHA's won't work,
and you get error messages like 'CAPTCHA validation error: unknown CAPTCHA
session ID'.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

The settings page is at Administration > Configuration > People > CAPTCHA
module settings (`admin/config/people/captcha`).

1. On the "CAPTCHA Settings" tab, pick the "Default challenge type". This is
   used for every form that does not set its own challenge.
1. Decide which forms get a CAPTCHA. Either:
   - check "Add CAPTCHA challenges on all forms" to protect every form on
     non-administrative pages, or
   - add a CAPTCHA point for each form you want to protect, on the
     "Captcha Points" tab (`admin/config/people/captcha/captcha-points`).
1. Optionally, add a description for the CAPTCHA and choose how responses are
   validated. Modules that provide the challenges can override or ignore the
   validation setting.
1. Save configuration.

### Finding a form ID

A CAPTCHA point is matched against the form ID, the internal machine name
Drupal gives every form. It is not the page path or the form title. Some
examples:

- User login: `user_login_form`
- User registration: `user_register_form`
- Request new password: `user_pass`
- A contact form with machine name `feedback`: `contact_message_feedback_form`
- Comments of the default comment type `comment`: `comment_comment_form`
- Creating an Article node: `node_article_form`

The base form ID also works, for example `contact_message_form` covers every
contact form.

You do not have to look the form ID up. Check "Add CAPTCHA administration
information to forms" on the "CAPTCHA Settings" tab, then visit the form as a
user with the "Administer CAPTCHA settings" permission. A CAPTCHA box on the
form links to "Place a CAPTCHA here for untrusted users", which opens a new
CAPTCHA point with the form ID filled in. Forms that already have a point link
to its settings instead. This box is not shown on administrative pages unless
"Additionally add administration informations on admin pages" is checked too.

### Why don't I see the CAPTCHA?

Users with the "Skip CAPTCHA" permission are never shown a CAPTCHA. User 1 and
the administrator role have every permission, so a CAPTCHA you configured is
not shown to you while you are logged in as an administrator. Test it logged out,
or as a user whose role does not have "Skip CAPTCHA" (Administration > People >
Permissions).

The CAPTCHA is also skipped for IP addresses listed under "Whitelisted IP
Addresses" on the settings page, and when `$settings['disable_captcha']` is
set (see Development below).


## Development

You can disable captcha in your local or test environment by adding the
following line to `settings.php`:
```
$settings['disable_captcha'] = TRUE;
```


## Maintainers

- Fabiano Sant'Ana - [wundo](https://www.drupal.org/u/wundo)
- Julian Pustkuchen - [Anybody](https://www.drupal.org/u/Anybody)
- Jakob Perry - [japerry](https://www.drupal.org/u/japerry)
- Rob Loach - [RobLoach](https://www.drupal.org/u/RobLoach)
- soxofaan - [soxofaan](https://www.drupal.org/u/soxofaan)
- Joshua Sedler - [Grevil](https://www.drupal.org/u/Grevil)
- Thomas Frobieter - [thomas.frobieter](https://www.drupal.org/u/thomas.frobieter)
- Lachlan Ennis - [elachlan](https://www.drupal.org/u/elachlan)
- Naveen Valecha - [naveenvalecha](https://www.drupal.org/u/naveenvalecha)
- Andrii Podanenko - [podarok](https://www.drupal.org/u/podarok)

Supporting organizations:

- Chuva Inc. - [Chuva Inc](https://www.drupal.org/chuva-inc)
- webks GmbH - [DROWL.de](https://www.DROWL.de)
