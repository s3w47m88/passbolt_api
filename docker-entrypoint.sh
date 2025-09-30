#!/bin/bash
set -e

echo "Starting Passbolt initialization..."

# Wait for database to be ready
until PGPASSWORD=$DATASOURCES_DEFAULT_PASSWORD psql -h "$DATASOURCES_DEFAULT_HOST" -U "$DATASOURCES_DEFAULT_USERNAME" -d postgres -c '\q' 2>/dev/null; do
  echo "Waiting for PostgreSQL to be ready..."
  sleep 2
done

# Create database if it doesn't exist
echo "Checking database..."
PGPASSWORD=$DATASOURCES_DEFAULT_PASSWORD psql -h "$DATASOURCES_DEFAULT_HOST" -U "$DATASOURCES_DEFAULT_USERNAME" -d postgres -tc "SELECT 1 FROM pg_database WHERE datname = '$DATASOURCES_DEFAULT_DATABASE'" | grep -q 1 || \
PGPASSWORD=$DATASOURCES_DEFAULT_PASSWORD psql -h "$DATASOURCES_DEFAULT_HOST" -U "$DATASOURCES_DEFAULT_USERNAME" -d postgres -c "CREATE DATABASE $DATASOURCES_DEFAULT_DATABASE"

echo "Database ready!"

# Start the original passbolt entrypoint
echo "Starting Passbolt application..."
exec /usr/bin/wait-for.sh "$DATASOURCES_DEFAULT_HOST:$DATASOURCES_DEFAULT_PORT" -- /docker-entrypoint.sh