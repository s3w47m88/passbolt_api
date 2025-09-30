FROM passbolt/passbolt:latest-ce

# Environment configuration
ENV PASSBOLT_SSL_FORCE=false
ENV APP_FULL_BASE_URL=https://passbolt.theportlandcompany.com
ENV PASSBOLT_SECURITY_PROXIES=*
ENV PASSBOLT_SECURITY_SET_HEADERS=false
ENV DATASOURCES_DEFAULT_DRIVER=Mysql

EXPOSE 80

# Use default entrypoint
CMD ["/docker-entrypoint.sh"]