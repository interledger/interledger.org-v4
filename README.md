# Interledger Foundation Website

> **This repository is archived and is no longer actively maintained except as a temporary measure for all historic Summit pages which are still in need of full migration.**

The Interledger Foundation Website has been migrated into the Interledger Foundation V5 website repository. All ongoing development, maintenance, and updates now take place in the V5 repository.

Please do not open new issues or pull requests here.

For the latest Interledger Foundation Website source code, documentation, and contribution guidelines, please refer to the V5 repository: [Interledger Foundation V5](https://github.com/interledger/interledger.org-v5)

This is a Drupal-powered CMS that manages all the content for the Interledger Foundation website. 

## Documentation

All documentation for working with website content is available in [the wiki](https://github.com/interledger/interledger.org-v4/wiki). Please refer to the wiki for:
- Content creation and editing guidelines
- Adding blog posts and podcast episodes
- Managing multilingual content
- General site-building philosophy

## Environments

- **Production**: https://interledger.org
- **Staging**: https://staging.interledger.org

## Infrastructure

The website runs on GCP infrastructure:
- **Compute**: Single GCE (Google Compute Engine) instance running Apache and Drupal
- **Database**: Cloud SQL (MySQL)
- **File Storage**: Files are stored locally on the instance at `/var/www/[environment]/web/sites/default/files`
- **Backups**: Automated backup system using Cloud SQL exports and GCS storage
- **Project**: All resources are in the `interledger-websites` GCP project

## Local Development

Please refer to the instructions here: https://github.com/interledger/interledger.org-v4/wiki/Setting-up-on-your-local-machine

## Deployments and CI/CD

All deployment processes, backup/restore operations, and CI/CD configurations are managed from the [`ci/`](./ci) directory. See the [ci/README.md](./ci/README.md) for detailed information about:
- Deployment workflows
- Backup and restore procedures
- GitHub Actions workflows
- Makefile commands

