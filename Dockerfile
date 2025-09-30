FROM passbolt/passbolt:latest-ce

# Override entrypoint to skip database wait
RUN sed -i 's/wait-for.sh/echo "Skipping wait-for" #/g' /docker-entrypoint.sh || true

# Set to not wait for database
ENV DATASOURCES_DEFAULT_HOST=mysql.railway.internal
ENV DATASOURCES_DEFAULT_PORT=3306
ENV DATASOURCES_DEFAULT_USERNAME=root
ENV DATASOURCES_DEFAULT_PASSWORD=password
ENV DATASOURCES_DEFAULT_DATABASE=passbolt

EXPOSE 80

ENTRYPOINT ["/bin/bash", "-c", "echo 'Starting Passbolt...' && /docker-entrypoint.sh"]