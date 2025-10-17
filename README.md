# Interledger Foundation website

This is a Drupal powered CMS that manages all the content for the Interledger Foundation website. All the documentation for this project is in [the wiki](https://github.com/interledger/interledger.org-v4/wiki).

## Local development

Please refer to the instructions here: https://github.com/interledger/interledger.org-v4/wiki/Setting-up-on-your-local-machine

## Staging environment

The staging environment runs in a Google Cloud Run based container.

The `files` folder is mounted in from a GCS bucket called `interledger-org-staging-bucket` which lives under the GCP project called `interledger-websites`.

If you have the appropriate access you can manipulate the files folder directly on there, or you can use the `gsutil` utility to upload or download files
from and to the bucket.

The Database lives in a MySQL Cloud SQL instance and should be managed directly through Google Cloud SQL Studio. Databases can be imported and exported from there.

Pipelines for Github Actions have been configured to automatically create a new container containing the drupal modifications on any merge to main.

### Example commands:

#### Upload files to staging bucket
Uploading new files to the staging bucket. For this to work you should be authenticated into Google Cloud.
```sh
gsutil -m cp -r files/* gs://interledger-org-staging-bucket/files
```

#### Downloading files from staging bucket to local
```sh
gsutil -m cp -r gs://interledger-org-staging-bucket/files files
```

