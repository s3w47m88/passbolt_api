FROM passbolt/passbolt:latest-ce

# Environment configuration
ENV PASSBOLT_SSL_FORCE=false
ENV APP_FULL_BASE_URL=https://passbolt.theportlandcompany.com
ENV PASSBOLT_SECURITY_PROXIES=*
ENV PASSBOLT_SECURITY_SET_HEADERS=false

# Create init endpoint
RUN mkdir -p /usr/share/php/passbolt/webroot/init && \
    echo '<?php \
    echo "<h1>Initializing Passbolt...</h1><pre>"; \
    chdir("/usr/share/php/passbolt"); \
    echo "Running migrations...\n"; \
    system("./bin/cake passbolt migrate --no-lock 2>&1"); \
    echo "\nInstalling Passbolt...\n"; \
    system("./bin/cake passbolt install --no-admin --force 2>&1"); \
    echo "</pre><p>Done! <a href=\"/\">Go to Passbolt</a></p>"; \
    ?>' > /usr/share/php/passbolt/webroot/init/index.php

EXPOSE 80