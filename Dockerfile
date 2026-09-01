# Pinned to an exact Passbolt CE release (do not use latest-ce in production).
# Upgraded from 4.6.2 (Apr 2024) to 5.15.0 to pick up 2+ years of security fixes.
FROM passbolt/passbolt:5.15.0-1-ce

# Secure-by-default hardening. Read by Passbolt at runtime; Railway service
# variables override these if ever needed.
#   SET_HEADERS  -> X-Frame-Options, X-Content-Type-Options, Referrer-Policy, etc.
#   SECURITY_PROXIES=* -> trust Railway's edge X-Forwarded-Proto
# NOTE: PASSBOLT_SSL_FORCE is intentionally NOT set here. On Railway's edge
# (TLS terminated upstream) SSL_FORCE=true causes an infinite http<->https
# redirect loop even with PROXIES=*, taking the login page down. HTTPS is
# already enforced at the Railway edge (http 301 -> https), so we omit it.
ENV PASSBOLT_SECURITY_SET_HEADERS=true \
    PASSBOLT_SECURITY_PROXIES=*

# Install PHP extensions required by Passbolt that are not bundled in the base image
RUN set -eux; \
  if command -v install-php-extensions >/dev/null 2>&1; then \
    install-php-extensions gd; \
  elif command -v docker-php-ext-install >/dev/null 2>&1; then \
    apt-get update; \
    apt-get install -y --no-install-recommends \
      libfreetype6-dev \
      libjpeg-dev \
      libpng-dev; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" gd; \
    rm -rf /var/lib/apt/lists/*; \
  elif command -v apt-get >/dev/null 2>&1; then \
    apt-get update; \
    apt-get install -y --no-install-recommends php-gd; \
    rm -rf /var/lib/apt/lists/*; \
  else \
    echo "No supported PHP extension installer found for GD." >&2; \
    exit 1; \
  fi

EXPOSE 80

CMD ["/docker-entrypoint.sh"]
