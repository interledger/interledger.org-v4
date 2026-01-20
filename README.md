# Interledger Foundation Website

This is a Drupal-powered CMS that manages all the content for the Interledger Foundation website. 

**⚠️ Note: We are currently in the process of migrating from AWS to Google Cloud Platform (GCP).**

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

