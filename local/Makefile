# Development Makefile for local Docker Compose
# Default target is `help`.

COMPOSE=docker compose
COMPOSE_PROJECT_DIR := $(shell pwd)

DB_SERVICE := db
DB_CONTAINER_NAME := interledger_mysql
DB_NAME := drupal
DB_USER := drupal
DB_PASSWORD := drupal123
DB_ROOT_PASSWORD := rootpass123
SQL_DUMP := local-backupdb.sql

.PHONY: help up down rebuild restore-database logs clean

help:
	@echo "Usage: make <target>"
	@echo ""
	@echo "Targets:"
	@echo "  up                 Start the docker compose environment (detached)."
	@echo "  down               Stop and remove containers, networks."
	@echo "  rebuild            Rebuild images and start fresh (down + up --build)."
	@echo "  restore-database   Restore the DB from local/$(SQL_DUMP) into $(DB_CONTAINER_NAME)."
	@echo "  logs               Show last 1000 log entries from all services."
	@echo "  clean              Remove all containers, volumes, and images (complete cleanup)."
	@echo "  help               Show this help (default)."

up:
	@echo "Starting docker compose..."
	$(COMPOSE) up -d
	@echo "Started. Use '$(COMPOSE) ps' to view containers."

down:
	@echo "Stopping docker compose..."
	$(COMPOSE) down
	@echo "Stopped."

rebuild:
	@echo "Rebuilding the docker compose setup (this will stop and remove containers)..."
	$(COMPOSE) down --volumes --remove-orphans || true
	$(COMPOSE) up -d --build
	@echo "Rebuild complete."

restore-database:
	@echo "Restoring database from local/$(SQL_DUMP)..."
	@if [ ! -f "$(SQL_DUMP)" ]; then \
		echo "Error: $(SQL_DUMP) not found in local/ directory. Place the SQL dump file at local/$(SQL_DUMP)."; exit 1; \
	fi
	@echo "Checking if containers are running..."
	@if ! $(COMPOSE) ps | grep -q "Up"; then \
		echo "Containers are not running. Starting them first..."; \
		echo "Running: $(COMPOSE) up -d"; \
		$(COMPOSE) up -d; \
		echo "Waiting for database to be ready..."; \
		sleep 10; \
	fi
	@echo "WARNING: This will overwrite the existing database '$(DB_NAME)' in container '$(DB_CONTAINER_NAME)'."
	@echo "All current data will be lost!"
	@echo -n "Are you sure you want to continue? [y/N]: "; \
	read answer; \
	if [ "$$answer" != "y" ] && [ "$$answer" != "Y" ]; then \
		echo "Database restore cancelled."; \
		exit 0; \
	fi
	@echo "Copying dump into $(DB_CONTAINER_NAME) (service: $(DB_SERVICE))..."
	@echo "Running: $(COMPOSE) cp ./$(SQL_DUMP) $(DB_SERVICE):/tmp/$(SQL_DUMP)"
	$(COMPOSE) cp ./$(SQL_DUMP) $(DB_SERVICE):/tmp/$(SQL_DUMP)
	@echo "Importing database, this can take a while..."
	@echo "Running: $(COMPOSE) exec -T $(DB_SERVICE) sh -c 'mysql -u\"$(DB_USER)\" -p\"$(DB_PASSWORD)\" \"$(DB_NAME)\" < /tmp/$(SQL_DUMP)'"
	@echo "You can monitor progress in another terminal with: make logs"
	$(COMPOSE) exec -T $(DB_SERVICE) sh -c 'mysql -u"$(DB_USER)" -p"$(DB_PASSWORD)" "$(DB_NAME)" < /tmp/$(SQL_DUMP)'
	@echo "Database restore finished."

logs:
	@echo "Showing last 1000 log entries from all services..."
	$(COMPOSE) logs --tail=1000 -f

clean:
	@echo "WARNING: This will remove all containers, volumes, and images for this project."
	@echo "All data including the database will be permanently lost!"
	@echo -n "Are you sure you want to continue? [y/N]: "; \
	read answer; \
	if [ "$$answer" != "y" ] && [ "$$answer" != "Y" ]; then \
		echo "Clean operation cancelled."; \
		exit 0; \
	fi
	@echo "Stopping and removing containers, networks, and volumes..."
	$(COMPOSE) down --volumes --remove-orphans
	@echo "Removing images..."
	docker image rm local-drupal 2>/dev/null || echo "Image local-drupal not found or already removed"
	docker image rm mysql:8.0 2>/dev/null || echo "Image mysql:8.0 not found or already removed"
	@echo "Clean complete. All project containers, volumes, and images have been removed."

# Default target
.DEFAULT_GOAL := help
