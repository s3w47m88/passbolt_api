FROM passbolt/passbolt:4.6.2-1-ce

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
