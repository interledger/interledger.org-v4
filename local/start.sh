#!/bin/bash

# Docker Compose startup script for Interledger Drupal site
# This script helps initialize and start the Docker environment

set -e

echo "🚀 Starting Interledger Drupal Development Environment"

# Check if docker is available
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is required but not installed."
    exit 1
fi

# Use docker compose (modern version)
DOCKER_COMPOSE_CMD="docker compose"
if ! docker compose version &> /dev/null 2>&1; then
    # Fallback to docker-compose if available
    if command -v docker-compose &> /dev/null; then
        DOCKER_COMPOSE_CMD="docker-compose"
    else
        echo "❌ Docker Compose is not available. Please install Docker Compose."
        exit 1
    fi
fi

# Navigate to the local directory
cd "$(dirname "$0")"

echo "📦 Building and starting containers..."
$DOCKER_COMPOSE_CMD up -d --build

echo "⏳ Waiting for database to be ready..."
sleep 30

echo "🔧 Setting up file permissions..."
$DOCKER_COMPOSE_CMD exec drupal chown -R www-data:www-data /var/www/html/sites/default/files
$DOCKER_COMPOSE_CMD exec drupal chmod -R 755 /var/www/html/sites/default/files

echo "🎉 Setup complete!"
echo ""
echo "🌐 Your Drupal site is available at: http://localhost:8080"
echo "🗄️  phpMyAdmin is available at: http://localhost:8081"
echo ""
echo "Database credentials:"
echo "  Host: db"
echo "  Database: drupal"
echo "  Username: drupal"
echo "  Password: drupal123"
echo ""
echo "To stop the environment: $DOCKER_COMPOSE_CMD down"
echo "To view logs: $DOCKER_COMPOSE_CMD logs -f"
echo "To access Drupal container: $DOCKER_COMPOSE_CMD exec drupal bash"