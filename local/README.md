# Interledger.org Local Development

Docker-based local development environment for the Interledger.org Drupal website.

## Prerequisites

- Docker and Docker Compose
- Make
- Minimum 4GB available RAM

## Quick Start

1. Place database dump as `local/local-backupdb.sql`
2. Start environment: `make up`
3. Restore database: `make restore-database`

For all available commands:
```bash
make help
```

## Architecture

- `web/` folder mounted read-only for immediate code changes
- Files stored in `local/files/` and `local/private/`
- Composer dependencies built into container image
- No rebuilds needed for PHP/theme changes

## Access Points

- **Website**: http://localhost:8080
- **Database**: localhost:3306
  - User: `drupal`
  - Password: `drupal123`
  - Database: `drupal`

## Common Tasks

### Clear Drupal cache
```bash
make drush cr
```

### Run any Drush command
```bash
make drush status
make drush user:login
```

### View logs
```bash
make logs
```

### Rebuild after Composer changes
```bash
make rebuild
```

## Database Access

The database is accessible on `127.0.0.1` using the port specified in the `.env` file (default: 3306). The `.env` file is created automatically when running `make up`.
