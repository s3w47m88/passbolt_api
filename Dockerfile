FROM passbolt/passbolt:latest-ce

# Environment configuration
ENV PASSBOLT_SSL_FORCE=false
ENV APP_FULL_BASE_URL=https://passbolt.theportlandcompany.com
ENV PASSBOLT_SECURITY_PROXIES=*
ENV PASSBOLT_SECURITY_SET_HEADERS=false

# Override entrypoint to run migrations first
RUN echo '#!/bin/bash\n\
echo "Starting Passbolt initialization..."\n\
cd /usr/share/php/passbolt\n\
./bin/cake passbolt migrate --no-lock 2>&1 | head -20\n\
./bin/cake passbolt install --no-admin --force 2>&1 | head -20\n\
./bin/cake cache clear_all\n\
echo "Starting web server..."\n\
exec /docker-entrypoint.sh' > /start.sh && chmod +x /start.sh

EXPOSE 80

ENTRYPOINT ["/start.sh"]