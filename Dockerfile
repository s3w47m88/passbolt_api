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

# --- Passbolt/Attachments plugin (end-to-end encrypted file attachments) ------
# Additive and backward-compatible: new /attachments routes + table + an
# optional `attachments` key in the v5 secret schema that stock clients ignore.
# Server-side only for now; the forked web UI / extension ship after a browser
# E2E test. Validated live against this exact base image before deploy.
USER root
COPY plugins/Passbolt/Attachments /usr/share/php/passbolt/plugins/Passbolt/Attachments
COPY attachments-plugin-register.php /tmp/attachments-plugin-register.php
RUN php /tmp/attachments-plugin-register.php && rm -f /tmp/attachments-plugin-register.php

# Apply the plugin's migrations on container start (core entrypoint only runs
# core migrations). One-shot supervisord program; see the conf for rationale.
COPY deploy/attachments-migrate.conf /etc/supervisor/conf.d/attachments-migrate.conf

# Raise body-size limits so encrypted blobs (up to ~25 MB) can be uploaded.
# nginx: a conf.d snippet included inside the http{} block (valid context); do
# NOT sed nginx.conf (its mail examples contain commented `server {` lines).
RUN set -eux; \
  mkdir -p /etc/nginx/conf.d; \
  { \
    printf 'client_max_body_size 40m;\n'; \
    printf 'client_body_buffer_size 1m;\n'; \
    printf 'client_body_timeout 300s;\n'; \
    printf 'proxy_read_timeout 300s;\n'; \
    printf 'fastcgi_read_timeout 300s;\n'; \
    printf 'fastcgi_send_timeout 300s;\n'; \
  } > /etc/nginx/conf.d/attachments.conf; \
  for PHPDIR in /etc/php/*/fpm/conf.d /usr/local/etc/php/conf.d; do \
    if [ -d "$PHPDIR" ]; then \
      printf 'upload_max_filesize=50M\npost_max_size=50M\n' > "$PHPDIR/zz-attachments.ini"; \
    fi; \
  done; \
  # Authoritative: php_admin_value in the fpm pool cannot be overridden by a
  # later ini file, an env-driven entrypoint rewrite, or ini_set(). The base
  # image was effectively capping uploads (~5M) at runtime despite conf.d; this
  # guarantees the limit regardless of what else sets it.
  for POOL in /etc/php/*/fpm/pool.d/www.conf /usr/local/etc/php-fpm.d/www.conf; do \
    if [ -f "$POOL" ]; then \
      printf '\nphp_admin_value[upload_max_filesize] = 50M\nphp_admin_value[post_max_size] = 50M\n' >> "$POOL"; \
    fi; \
  done; \
  # ROOT CAUSE of the ~5M cap: the Passbolt nginx site config passes
  # `fastcgi_param PHP_VALUE "upload_max_filesize=5M ..."` on every request,
  # which overrides php.ini/conf.d per-request. Raise it here so encrypted
  # blobs up to the nginx client_max_body_size can be uploaded.
  for SITE in /etc/nginx/sites-available/nginx-passbolt.conf /etc/nginx/sites-enabled/nginx-passbolt.conf; do \
    if [ -f "$SITE" ]; then \
      sed -i -E 's/upload_max_filesize=5M/upload_max_filesize=40M/g; s/post_max_size=32M/post_max_size=40M/g; s/client_max_body_size[[:space:]]+32M/client_max_body_size 40M/g' "$SITE"; \
    fi; \
  done

EXPOSE 80

CMD ["/docker-entrypoint.sh"]
