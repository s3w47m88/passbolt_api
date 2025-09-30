FROM passbolt/passbolt:latest-ce

# Environment configuration
ENV PASSBOLT_SSL_FORCE=false
ENV APP_FULL_BASE_URL=https://passbolt.theportlandcompany.com
ENV PASSBOLT_SECURITY_PROXIES=*
ENV PASSBOLT_SECURITY_SET_HEADERS=false
ENV DATASOURCES_DEFAULT_DRIVER=Mysql

# Copy scripts to webroot
COPY init.php /usr/share/php/passbolt/webroot/init.php
COPY setup.php /usr/share/php/passbolt/webroot/setup.php

EXPOSE 80

CMD ["/docker-entrypoint.sh"]