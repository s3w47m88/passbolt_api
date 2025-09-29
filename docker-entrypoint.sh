#!/bin/bash
set -e

echo "Starting Passbolt initialization..."

# Wait for database to be ready
until PGPASSWORD=$DATASOURCES_DEFAULT_PASSWORD psql -h "$DATASOURCES_DEFAULT_HOST" -U "$DATASOURCES_DEFAULT_USERNAME" -d postgres -c '\q' 2>/dev/null; do
  echo "Waiting for PostgreSQL to be ready..."
  sleep 2
done

# Create database if it doesn't exist
PGPASSWORD=$DATASOURCES_DEFAULT_PASSWORD psql -h "$DATASOURCES_DEFAULT_HOST" -U "$DATASOURCES_DEFAULT_USERNAME" -d postgres -tc "SELECT 1 FROM pg_database WHERE datname = '$DATASOURCES_DEFAULT_DATABASE'" | grep -q 1 || \
PGPASSWORD=$DATASOURCES_DEFAULT_PASSWORD psql -h "$DATASOURCES_DEFAULT_HOST" -U "$DATASOURCES_DEFAULT_USERNAME" -d postgres -c "CREATE DATABASE $DATASOURCES_DEFAULT_DATABASE"

echo "Database ready!"

# Generate GPG keys if they don't exist
if [ ! -f /etc/passbolt/gpg/serverkey.asc ]; then
    echo "Generating GPG keys..."
    
    # Create GPG batch file for key generation
    cat > /tmp/gpg_batch <<EOF
%echo Generating Passbolt GPG key
Key-Type: RSA
Key-Length: 4096
Subkey-Type: RSA
Subkey-Length: 4096
Name-Real: Passbolt Server
Name-Email: ${PASSBOLT_KEY_EMAIL:-passbolt@theportlandcompany.com}
Expire-Date: 0
%no-protection
%commit
%echo done
EOF

    # Generate the key
    gpg --batch --generate-key /tmp/gpg_batch
    
    # Export the keys
    gpg --armor --export ${PASSBOLT_KEY_EMAIL:-passbolt@theportlandcompany.com} > /etc/passbolt/gpg/serverkey.asc
    gpg --armor --export-secret-keys ${PASSBOLT_KEY_EMAIL:-passbolt@theportlandcompany.com} > /etc/passbolt/gpg/serverkey_private.asc
    
    # Get fingerprint
    export PASSBOLT_GPG_SERVER_KEY_FINGERPRINT=$(gpg --list-keys --fingerprint ${PASSBOLT_KEY_EMAIL:-passbolt@theportlandcompany.com} | grep -A 1 "pub" | grep -v "pub" | tr -d ' ' | tr -d '\n')
    echo "GPG keys generated with fingerprint: $PASSBOLT_GPG_SERVER_KEY_FINGERPRINT"
fi

# Run database migrations
echo "Running database migrations..."
/usr/share/php/passbolt/bin/cake passbolt migrate || true

# Install passbolt if needed
if ! /usr/share/php/passbolt/bin/cake passbolt healthcheck --datasource 2>/dev/null | grep -q "OK"; then
    echo "Installing Passbolt..."
    /usr/share/php/passbolt/bin/cake passbolt install --no-admin || true
fi

echo "Starting Passbolt application..."
# Start the original passbolt entrypoint
exec /docker-entrypoint.sh "$@"