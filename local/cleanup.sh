#!/bin/bash

# Docker cleanup script for Interledger Drupal site
# This script helps clean up Docker resources

set -e

echo "🧹 Cleaning up Interledger Drupal Development Environment"

# Navigate to the local directory
cd "$(dirname "$0")"

# Use docker compose (modern version)
DOCKER_COMPOSE_CMD="docker compose"
if ! docker compose version &> /dev/null 2>&1; then
    # Fallback to docker-compose if available
    if command -v docker-compose &> /dev/null; then
        DOCKER_COMPOSE_CMD="docker-compose"
    else
        echo "❌ Docker Compose is not available."
        exit 1
    fi
fi

echo "🛑 Stopping containers..."
$DOCKER_COMPOSE_CMD down

echo "🗑️  Removing volumes (this will delete the database)..."
read -p "Are you sure you want to remove all data? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    $DOCKER_COMPOSE_CMD down -v
    
    echo "🧽 Cleaning up Docker images..."
    docker image prune -f
    
    echo "🗄️  Removing unused volumes..."
    docker volume prune -f
    
    echo "✅ Cleanup complete!"
    echo "To restart with fresh data, run: ./start.sh"
else
    echo "ℹ️  Cleanup cancelled. Containers stopped but data preserved."
    echo "To restart, run: $DOCKER_COMPOSE_CMD up -d"
fi