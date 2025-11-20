# Interledger Foundation website

This is a Drupal powered CMS that manages all the content for the Interledger Foundation website. All the documentation for this project is in [the wiki](https://github.com/interledger/interledger.org-v4/wiki).

## Local development

Please refer to the instructions here: <https://github.com/interledger/interledger.org-v4/wiki/Setting-up-on-your-local-machine>

### Code Formatting

This project uses [Biome](https://biomejs.dev/), [Prettier](https://prettier.io/), and [ESLint](https://eslint.org/) for code formatting and linting. Before submitting a pull request, please ensure your code passes linting:

1. **Fix issues**: Run `pnpm run format` to automatically fix linting and formatting issues
2. **Check before pushing**: Run `pnpm run lint` to verify everything passes (CI will also run this)

The project uses Biome for JavaScript/TypeScript formatting and linting, Prettier for Markdown files, and ESLint for additional JavaScript linting rules.

## Staging environment

We now have a [staging environment avilable](https://interledger-org-staging-395917053417.us-east1.run.app/). It is not fully functional yet, but we hope that this will form the backbone of how we will deploy the website in the future.

The staging environment runs as a [Google Cloud Run based container](https://console.cloud.google.com/run/detail/us-east1/interledger-org-staging/observability/metrics?hl=en&project=interledger-websites).

- The `files` folder is mounted in from a GCS bucket called `interledger-org-staging-bucket`
- The GCP project called `interledger-websites` and access is required if developers want to make changes here.
- If you have the appropriate access you can manipulate the files folder directly on there, or you can use the [gsutil utility](https://cloud.google.com/storage/docs/gsutil_install) to upload or download files
  from and to the bucket.
- The Database lives in a MySQL Cloud SQL instance and should be managed directly through Google Cloud SQL Studio. Databases can be imported and exported from there.

Pipelines for Github Actions have been configured to automatically create a new container containing the drupal modifications on any merge to main.

Known issues

- At this point in time, the developer portal is not correctly being patched in for the staging environment.

Uploading new files to the staging bucket. For this to work you should be authenticated into Google Cloud.

```sh
# Upload files to the staging environment
gsutil -m cp -r files/* gs://interledger-org-staging-bucket/files

# Download to local
gsutil -m cp -r gs://interledger-org-staging-bucket/files files
```
