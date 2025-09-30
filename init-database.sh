#!/bin/bash
# This script should be run once to initialize Passbolt database
# Run with: railway run bash init-database.sh

echo "Initializing Passbolt database..."

# Install Passbolt database
railway run --service passbolt_api "cd /usr/share/php/passbolt && ./bin/cake passbolt migrate --no-lock"
railway run --service passbolt_api "cd /usr/share/php/passbolt && ./bin/cake passbolt install --no-admin --force"

echo "Database initialization complete!"