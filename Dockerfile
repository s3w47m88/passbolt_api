FROM passbolt/passbolt:latest-ce

# Health check for Railway
HEALTHCHECK --interval=30s --timeout=3s --start-period=90s --retries=3 \
  CMD curl -f http://localhost/healthcheck || exit 1

EXPOSE 80

# Use Railway's internal MySQL service variables