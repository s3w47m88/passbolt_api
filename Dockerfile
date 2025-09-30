FROM passbolt/passbolt:latest-ce

# Create a startup script to debug
RUN echo '#!/bin/bash\n\
echo "=== Environment Variables ==="\n\
env | grep -E "(DATASOURCES|MYSQL|PORT|APP_FULL)"\n\
echo "=== Testing MySQL Connection ==="\n\
mysql -h"$DATASOURCES_DEFAULT_HOST" -u"$DATASOURCES_DEFAULT_USERNAME" -p"$DATASOURCES_DEFAULT_PASSWORD" -e "SELECT 1" || echo "MySQL connection failed"\n\
echo "=== Starting Passbolt ==="\n\
exec /docker-entrypoint.sh' > /start-debug.sh && chmod +x /start-debug.sh

EXPOSE 80

CMD ["/start-debug.sh"]