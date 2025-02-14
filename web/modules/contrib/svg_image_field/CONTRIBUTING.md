CONTRIBUTING
------------

You may setup your local environment with [Ddev]. This project leverages the
[Ddev Drupal Contrib] plugin.

1.  [Install Ddev] with a [Docker provider].
2.  Clone this project's repository from Drupal's GitLab.

        git clone git@git.drupal.org:project/svg_image_field.git
        cd svg_image_field

3.  Startup Ddev.

        ddev start

4.  Install composer dependencies.

        ddev poser

    Note: `ddev poser` is shorthand for `ddev composer` to add in Drupal core dependencies
    without needing to modify the root composer.json. Find out more in Ddev Drupal Contrib
    [commands].

5.  Install Drupal.

        ddev drush site:install

6.  Visit site in browser.

        ddev launch

    Or, login as user 1:

        ddev drush user:login

7.  Check code changes with phpcs and phpstan.

        ddev phpcs
        ddev phpstan

    This is useful so you don't have to push and wait for Drupal GitLabCI build.

8.  Push work to Merge Requests (MRs) opened via this project's [issue queue].


CHANGING DRUPAL CORE VERSION
----------------------------

Ddev Drupal Contrib installs a recent stable version of Drupal core via the `DRUPAL_CORE`
environment variable. Review .ddev/config.yaml to find the current default version.

Override the current default version of Drupal core by creating .ddev/config.local.yaml:

```yaml
web_environment:
    - DRUPAL_CORE=^9
```

UPDATING DEPENDENCIES
---------------------

This project depends on 3rd party PHP libraries. It also specifies suggested "dev dependencies"
for contribution on local development environments. Occasionally, Ddev and Ddev Drupal Contrib
must be updated as well.

1.  Create an issue, MR, and checkout the MR branch.
2.  Update Ddev and Ddev Drupal Contrib itself.

    Read https://ddev.readthedocs.io/en/stable/users/install/ddev-upgrade/

        ddev config --update
        ddev get ddev/ddev-drupal-contrib
        ddev restart
        ddev poser
        ddev symlink-project

3.  Review and update PHP dependencies defined in composer.json

        ddev composer outdated --direct

3.  Test clean install, commit, and push.


[Ddev]: https://www.ddev.com/
[Ddev Drupal Contrib]: https://github.com/ddev/ddev-drupal-contrib
[Install Ddev]: https://ddev.readthedocs.io/en/stable/
[Docker provider]: https://ddev.readthedocs.io/en/stable/users/install/docker-installation/
[issue queue]: https://www.drupal.org/project/issues/svg_image_field
[commands]: https://github.com/ddev/ddev-drupal-contrib#commands
