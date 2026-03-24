#!/bin/bash
# Production Optimization Script for Library Fokara
# Run this after deploying to production to cache all Laravel configs
# Usage: bash scripts/optimize-production.sh

set -e

echo "=== Library Fokara - Production Optimization ==="
echo ""

# Clear old caches first
echo "[1/6] Clearing old caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
echo "    Done."

# Rebuild caches
echo "[2/6] Caching configuration..."
php artisan config:cache
echo "    Done."

echo "[3/6] Caching routes..."
php artisan route:cache
echo "    Done."

echo "[4/6] Caching views..."
php artisan view:cache
echo "    Done."

echo "[5/6] Caching events..."
php artisan event:cache
echo "    Done."

# Run pending migrations
echo "[6/6] Running pending migrations..."
php artisan migrate --force
echo "    Done."

echo ""
echo "=== Optimization Complete ==="
echo "All Laravel caches have been rebuilt."
