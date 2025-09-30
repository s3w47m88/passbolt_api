FROM passbolt/passbolt:latest-ce

# Environment configuration
ENV PASSBOLT_SSL_FORCE=false
ENV APP_FULL_BASE_URL=https://passbolt.theportlandcompany.com
ENV PASSBOLT_SECURITY_PROXIES=*
ENV PASSBOLT_SECURITY_SET_HEADERS=false

# Database driver must be set
ENV DATASOURCES_DEFAULT_DRIVER=Mysql

# GPG Configuration
ENV PASSBOLT_GPG_SERVER_KEY_FINGERPRINT=""
ENV PASSBOLT_KEY_EMAIL=passbolt@theportlandcompany.com

# Copy init script to webroot
COPY init.php /usr/share/php/passbolt/webroot/init.php

# Create startup script that generates GPG keys if needed
RUN echo '#!/bin/bash\n\
if [ ! -f /etc/passbolt/gpg/serverkey.asc ]; then\n\
  echo "Generating GPG keys..."\n\
  gpg --batch --gen-key <<EOF\n\
%echo Generating GPG key\n\
Key-Type: RSA\n\
Key-Length: 4096\n\
Subkey-Type: RSA\n\
Subkey-Length: 4096\n\
Name-Real: Passbolt Server\n\
Name-Email: passbolt@theportlandcompany.com\n\
Expire-Date: 0\n\
%no-protection\n\
%commit\n\
%echo done\n\
EOF\n\
  gpg --armor --export passbolt@theportlandcompany.com > /etc/passbolt/gpg/serverkey.asc\n\
  gpg --armor --export-secret-keys passbolt@theportlandcompany.com > /etc/passbolt/gpg/serverkey_private.asc\n\
fi\n\
exec /docker-entrypoint.sh' > /startup.sh && chmod +x /startup.sh

EXPOSE 80

CMD ["/startup.sh"]