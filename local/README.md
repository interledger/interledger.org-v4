# Interledger.org Docker Development Environment

This Docker setup provides a complete local development environment for the Interledger.org Drupal website.

## Prerequisites

- Docker and Docker Compose installed on your system
- At least 4GB of available RAM
- The database dump file `local-backupdb.sql` in the `local/` directory
- The Drupal files in the `local/files/` directory

## Quick Start

1. Navigate to the `local/` directory:
   ```bash
   cd local/
   ```

2. Run the startup script:
   ```bash
   ./start.sh
   ```

   Or manually start with Docker Compose:
   ```bash
   docker-compose up -d --build
   ```

3. Wait for the containers to start (usually 1-2 minutes)

4. Access your site at: http://localhost:8080

## Services

The Docker Compose setup includes:

- **Drupal** (http://localhost:8080): The main Drupal website
- **MySQL 8.0** (localhost:3306): Database server with automatic restoration from `local-backupdb.sql`
- **phpMyAdmin** (http://localhost:8081): Web-based MySQL administration

## Database Configuration

- **Host**: `db` (from within containers) or `localhost` (from host machine)
- **Database**: `drupal`
- **Username**: `drupal`
- **Password**: `drupal123`
- **Root Password**: `rootpass123`

## File Structure

The setup mounts the following directories:
- `../web/` → `/var/www/html/` (Drupal web root)
- `../vendor/` → `/var/www/vendor/` (Composer dependencies, read-only)
- `./files/` → `/var/www/html/sites/default/files/` (Drupal files directory)

## Development Features

The development environment includes:
- Automatic database restoration from backup
- CSS/JS aggregation disabled for easier debugging
- Error messages displayed for development
- Caching disabled for immediate changes
- File permission management
- Security headers configured

## Common Commands

```bash
# Start the environment
docker-compose up -d

# Stop the environment
docker-compose down

# View logs
docker-compose logs -f

# Access the Drupal container
docker-compose exec drupal bash

# Run Drush commands
docker-compose exec drupal ./vendor/bin/drush status

# Rebuild containers
docker-compose down
docker-compose up -d --build

# Import fresh database (if needed)
docker-compose exec db mysql -u drupal -pdrupal123 drupal < /docker-entrypoint-initdb.d/01-database.sql
```

## Troubleshooting

### Database Issues
If the database doesn't import correctly:
1. Stop the containers: `docker-compose down`
2. Remove the MySQL volume: `docker volume rm local_mysql_data`
3. Start again: `docker-compose up -d`

### File Permission Issues
If you encounter permission issues:
```bash
docker-compose exec drupal chown -R www-data:www-data /var/www/html/sites/default/files
docker-compose exec drupal chmod -R 755 /var/www/html/sites/default/files
```

### Clear Drupal Cache
```bash
docker-compose exec drupal ./vendor/bin/drush cache:rebuild
```

### Reset to Clean State
```bash
docker-compose down -v
docker-compose up -d --build
```

## Security Note

This setup is for development only. The database credentials and configuration are not suitable for production use.