FROM passbolt/passbolt:latest-ce

# Disable SSL forcing since Railway handles SSL termination
ENV PASSBOLT_SSL_FORCE=false
ENV APP_FULL_BASE_URL=https://passbolt.theportlandcompany.com

# Set dummy database config to prevent startup failures
ENV DATASOURCES_DEFAULT_HOST=mysql.railway.internal
ENV DATASOURCES_DEFAULT_PORT=3306
ENV DATASOURCES_DEFAULT_USERNAME=root
ENV DATASOURCES_DEFAULT_PASSWORD=password
ENV DATASOURCES_DEFAULT_DATABASE=passbolt

# Trust Railway's proxy
ENV PASSBOLT_SECURITY_PROXIES=*

EXPOSE 80

CMD ["/docker-entrypoint.sh"]