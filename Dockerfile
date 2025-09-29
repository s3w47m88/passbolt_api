FROM passbolt/passbolt:latest-ce-non-root

# Switch to root for setup
USER root

# Install additional packages needed for initialization
RUN apt-get update && apt-get install -y \
    postgresql-client \
    curl \
    gnupg \
    && rm -rf /var/lib/apt/lists/*

# Create directories for GPG keys and ensure proper permissions
RUN mkdir -p /etc/passbolt/gpg \
    && chown -R www-data:www-data /etc/passbolt

# Copy startup script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Switch back to www-data user
USER www-data

# Expose ports for non-root container
EXPOSE 8080 4433

# Set entrypoint
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]