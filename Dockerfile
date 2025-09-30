FROM passbolt/passbolt:latest-ce

# Disable SSL forcing since Railway handles SSL termination
ENV PASSBOLT_SSL_FORCE=false
ENV APP_FULL_BASE_URL=https://passbolt.theportlandcompany.com

# Trust Railway's proxy
ENV PASSBOLT_SECURITY_PROXIES=*

# Fix CSP headers for the domain
ENV PASSBOLT_SECURITY_SET_HEADERS=false

# Create initialization script
RUN echo '#!/bin/bash\n\
echo "Initializing Passbolt database..."\n\
cd /usr/share/php/passbolt\n\
./bin/cake passbolt migrate --no-lock || true\n\
./bin/cake passbolt install --no-admin --force || true\n\
echo "Starting web server..."\n\
exec /docker-entrypoint.sh' > /init.sh && chmod +x /init.sh

EXPOSE 80

CMD ["/init.sh"]