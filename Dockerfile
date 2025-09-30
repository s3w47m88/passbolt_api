FROM passbolt/passbolt:latest-ce-non-root

# Switch to root for setup
USER root

# Install additional packages needed for initialization
RUN apt-get update && apt-get install -y \
    postgresql-client \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Create custom entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/custom-entrypoint.sh
RUN chmod +x /usr/local/bin/custom-entrypoint.sh

# Create nginx config for Railway
RUN echo 'server {\n\
    listen 8080;\n\
    server_name _;\n\
    client_max_body_size 50M;\n\
    client_body_buffer_size 50M;\n\
    \n\
    root /usr/share/php/passbolt/webroot;\n\
    index index.php;\n\
    \n\
    location / {\n\
        try_files $uri $uri/ /index.php?$args;\n\
    }\n\
    \n\
    location ~ \.php$ {\n\
        try_files $uri =404;\n\
        include fastcgi_params;\n\
        fastcgi_pass 127.0.0.1:9000;\n\
        fastcgi_index index.php;\n\
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;\n\
        fastcgi_param PHP_VALUE "max_execution_time=300\n\
                                max_input_time=300\n\
                                post_max_size=50M\n\
                                upload_max_filesize=50M";\n\
    }\n\
}' > /etc/nginx/sites-enabled/passbolt-ssl.conf

# Switch back to www-data
USER www-data

# Expose port for Railway
EXPOSE 8080

# Set entrypoint
ENTRYPOINT ["/usr/local/bin/custom-entrypoint.sh"]