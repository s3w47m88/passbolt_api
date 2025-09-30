FROM passbolt/passbolt:latest-ce

# Disable SSL forcing since Railway handles SSL termination
ENV PASSBOLT_SSL_FORCE=false
ENV APP_FULL_BASE_URL=https://passbolt.theportlandcompany.com

# Trust Railway's proxy
ENV PASSBOLT_SECURITY_PROXIES=*

# Fix CSP headers for the domain
ENV PASSBOLT_SECURITY_SET_HEADERS=false

EXPOSE 80

CMD ["/docker-entrypoint.sh"]