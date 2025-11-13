# E2E tests (Playwright)

End-to-end tests for the Interledger.org Drupal site using Playwright.

## Prerequisites

- Node.js 18+ (20+ recommended)
- Browsers for Playwright (`npm run install:browsers`)
- The target site running locally or accessible via URL

## Quick start (local)

1) Start the local Drupal stack (optional — the test runner will also start it automatically if targeting localhost):

```bash
make -C local up
# or:
cd e2e && npm run local:up
```

This exposes the site at http://localhost:8080 by default.

2) In this `e2e/` folder, install deps and browsers:

```bash
npm install
npm run install:browsers
```

3) Run the tests:

```bash
npm test
# or explicitly against localhost (equivalent):
npm run test:local
```

Tests default to `BASE_URL=http://localhost:8080`. To target a different environment:

```bash
BASE_URL=https://your-env.example.org npm test
```

Note: When `BASE_URL` is the default `http://localhost:8080` (and not in CI), the test runner will run `make -C ../local up` automatically and wait for the port to be ready before starting tests. If the stack is already running, it will reuse it.

## Useful commands

- Run headed: `npm run test:headed`
- Debug mode: `npm run test:debug`
- Show last report: `npm run show-report`
- Codegen against current BASE_URL: `npm run codegen`

# Managing the local Docker stack from here

- Start: `npm run local:up`
- Stop: `npm run local:down`

## CI

There is a GitHub Actions workflow (`.github/workflows/e2e.yml`) that runs tests when manually dispatched and requires a `base_url` input. It does not start Drupal; point it to a running environment (e.g., staging).


