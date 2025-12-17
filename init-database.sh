#!/bin/bash
# This script should be run once to initialize Passbolt database
# Run with: railway run bash init-database.sh

echo "Initializing Passbolt database..."

SERVICE="${SERVICE:-passbolt}"

# Install Passbolt database (use SERVICE env to override target Railway service)
railway run --service "$SERVICE" "cd /usr/share/php/passbolt && ./bin/cake passbolt migrate --no-lock"
railway run --service "$SERVICE" "cd /usr/share/php/passbolt && ./bin/cake passbolt install --no-admin --force"

echo "Database initialization complete!"
