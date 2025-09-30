FROM passbolt/passbolt:latest-ce-non-root

# Environment variables for Railway
ENV PORT=8080

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=60s --retries=3 \
  CMD curl -f http://localhost:8080/healthcheck/status.json || exit 1

# Expose port
EXPOSE 8080

# Use the default entrypoint
ENTRYPOINT ["/docker-entrypoint.sh"]