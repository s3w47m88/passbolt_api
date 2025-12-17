# Railway Environment Variables (Passbolt + MariaDB)

Copy these into your Railway Passbolt service. Values in brackets must be customized.

```env
# Application
APP_FULL_BASE_URL=https://passbolt.theportlandcompany.com
PORT=8080

# Database (Railway “MySQL” service is MariaDB-compatible)
DATASOURCES_DEFAULT_HOST=${{MySQL.HOST}}
DATASOURCES_DEFAULT_PORT=${{MySQL.PORT}}
DATASOURCES_DEFAULT_DATABASE=${{MySQL.DATABASE}}
DATASOURCES_DEFAULT_USERNAME=${{MySQL.USERNAME}}
DATASOURCES_DEFAULT_PASSWORD=${{MySQL.PASSWORD}}
DATASOURCES_DEFAULT_DRIVER=Mysql

# Email (Resend example)
EMAIL_DEFAULT_FROM_NAME=Passbolt
EMAIL_DEFAULT_FROM=passbolt@theportlandcompany.com
EMAIL_TRANSPORT_DEFAULT_HOST=smtp.resend.com
EMAIL_TRANSPORT_DEFAULT_PORT=465
EMAIL_TRANSPORT_DEFAULT_USERNAME=resend
EMAIL_TRANSPORT_DEFAULT_PASSWORD=[your-resend-api-key]
EMAIL_TRANSPORT_DEFAULT_TLS=true

# Security / HTTPS
PASSBOLT_SSL_FORCE=true
PASSBOLT_SECURITY_PROXIES=*
PASSBOLT_SECURITY_SET_HEADERS=true

# Registration (temporarily true for first admin creation, then false)
PASSBOLT_REGISTRATION_PUBLIC=false

# JWT
PASSBOLT_PLUGINS_JWT_AUTHENTICATION_ENABLED=true
PASSBOLT_PLUGINS_JWT_AUTHENTICATION_SECRET_KEY=[generate-a-secure-random-string]

# GPG (paths are defaults from the image)
PASSBOLT_GPG_SERVER_KEY_PUBLIC=/etc/passbolt/gpg/serverkey.asc
PASSBOLT_GPG_SERVER_KEY_PRIVATE=/etc/passbolt/gpg/serverkey_private.asc
```

## Steps

1. Create the Railway “MySQL” service in the same project/environment as Passbolt.
2. Set the variables above on the Passbolt service; they will resolve via `${{MySQL.*}}`.
3. Deploy and run migrations/install once via `init-database.sh` or the command in README.
4. Flip `PASSBOLT_REGISTRATION_PUBLIC` back to `false` after the first admin is created.
