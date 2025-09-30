FROM passbolt/passbolt:latest-ce

# Environment configuration
ENV PASSBOLT_SSL_FORCE=false
ENV APP_FULL_BASE_URL=https://passbolt.theportlandcompany.com
ENV PASSBOLT_SECURITY_PROXIES=*
ENV PASSBOLT_SECURITY_SET_HEADERS=false
ENV DATASOURCES_DEFAULT_DRIVER=Mysql

# Override entrypoint to run migrations first
RUN echo '#!/bin/bash\n\
echo "Starting Passbolt initialization..."\n\
cd /usr/share/php/passbolt\n\
echo "Testing database connection..."\n\
mysql -h"$DATASOURCES_DEFAULT_HOST" -u"$DATASOURCES_DEFAULT_USERNAME" -p"$DATASOURCES_DEFAULT_PASSWORD" -e "SELECT 1" && echo "Database connected!" || echo "Database connection failed"\n\
./bin/cake passbolt migrate || true\n\
./bin/cake passbolt install --no-admin --force || true\n\
./bin/cake cache clear_all\n\
echo "Starting web server..."\n\
exec /docker-entrypoint.sh' > /start.sh && chmod +x /start.sh

EXPOSE 80

ENTRYPOINT ["/start.sh"]