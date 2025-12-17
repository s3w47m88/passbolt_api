FROM passbolt/passbolt:4.6.2-1-ce

# Install PHP extensions required by Passbolt that are not bundled in the base image
RUN docker-php-ext-install gd

EXPOSE 80

CMD ["/docker-entrypoint.sh"]
