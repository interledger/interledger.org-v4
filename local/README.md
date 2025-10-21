# Interledger.org Docker Development Environment

This Docker setup provides a complete local development environment for the Interledger.org Drupal website.

## Prerequisites

- Docker and Docker Compose installed
- At least 4GB of available RAM
- Make installed on your system

## Setup

1. Place your database dump file as `local/local-backupdb.sql`
2. Ensure Drupal files are in `local/files/`
3. Place private files in `local/private/` if needed

## Usage

Start the environment:
```bash
make up
```

Restore database from backup:
```bash
make restore-database
```

View logs:
```bash
make logs
```

Stop the environment:
```bash
make down
```

Rebuild everything:
```bash
make rebuild
```

Clean up all containers, volumes, and images:
```bash
make clean
```

Show all available commands:
```bash
make help
```

## Architecture

The setup uses two main principles:

- The `web` folder is mounted read-only to preserve file permissions for development
- Files and private directories are mounted to `/var/drupal/` to avoid folder conflicts
- Composer dependencies are built into the container image
- Code changes in `web/` reflect immediately without rebuilds

## Access

- Website: http://localhost:8080
- Database: localhost:3306 (user: drupal, password: drupal123, database: drupal)


