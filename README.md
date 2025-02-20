# Interledger Foundation website

This is a Drupal powered CMS that manages all the content for the Interledger Foundation website. All the documentation for this project is in [the wiki](https://github.com/interledger/interledger.org-v4/wiki).

## Local development

Please refer to the instructions here: https://github.com/interledger/interledger.org-v4/wiki/Setting-up-on-your-local-machine

**NOTE**: When style changes are made:
1. Bump the style version in the `web/themes/interledger/interledger.libraries.yml` file.
2. Run `npm run build:css` so that the style changes are appropriately minified.
3. Run `./vendor/bin/drush cr` to clear the cache.
4. Test the style changes locally before making a PR.