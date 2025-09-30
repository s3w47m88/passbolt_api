FROM passbolt/passbolt:latest-ce

# Environment configuration
ENV PASSBOLT_SSL_FORCE=false
ENV APP_FULL_BASE_URL=https://passbolt.theportlandcompany.com
ENV PASSBOLT_SECURITY_PROXIES=*
ENV PASSBOLT_SECURITY_SET_HEADERS=false
ENV DATASOURCES_DEFAULT_DRIVER=Mysql
ENV DATASOURCES_DEFAULT_ENCODING=utf8mb4
ENV DATASOURCES_DEFAULT_TIMEZONE=UTC
ENV DATASOURCES_DEFAULT_PERSISTENT=false
ENV DATASOURCES_DEFAULT_INIT_COMMANDS='["SET sql_mode = \\'TRADITIONAL\\'"]'

# Copy scripts to webroot
COPY init.php /usr/share/php/passbolt/webroot/init.php
COPY setup.php /usr/share/php/passbolt/webroot/setup.php
COPY setup-database.php /usr/share/php/passbolt/webroot/setup-database.php
COPY create-admin.php /usr/share/php/passbolt/webroot/create-admin.php
COPY force-admin-setup.php /usr/share/php/passbolt/webroot/force-admin-setup.php
COPY setup-first-admin.php /usr/share/php/passbolt/webroot/setup-first-admin.php
COPY bypass-setup.php /usr/share/php/passbolt/webroot/bypass-setup.php
COPY recover-admin.php /usr/share/php/passbolt/webroot/recover-admin.php
COPY create-spencer-admin.php /usr/share/php/passbolt/webroot/create-spencer-admin.php
COPY update-admin-email.php /usr/share/php/passbolt/webroot/update-admin-email.php
COPY final-spencer-setup.php /usr/share/php/passbolt/webroot/final-spencer-setup.php
COPY test-email.php /usr/share/php/passbolt/webroot/test-email.php
COPY debug-email.php /usr/share/php/passbolt/webroot/debug-email.php
COPY fix-email-config.php /usr/share/php/passbolt/webroot/fix-email-config.php
COPY check-gpg-keys.php /usr/share/php/passbolt/webroot/check-gpg-keys.php
COPY fix-gpg-keys.php /usr/share/php/passbolt/webroot/fix-gpg-keys.php
COPY reset-admin-user.php /usr/share/php/passbolt/webroot/reset-admin-user.php
COPY delete-all-users.php /usr/share/php/passbolt/webroot/delete-all-users.php
COPY debug-auth.php /usr/share/php/passbolt/webroot/debug-auth.php

EXPOSE 80

CMD ["/docker-entrypoint.sh"]