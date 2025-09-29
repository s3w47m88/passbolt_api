# Railway Environment Variables

## Required Variables for Passbolt Deployment

Copy these environment variables to your Railway service:

```env
# Application Settings
APP_FULL_BASE_URL=https://passbolt.theportlandcompany.com

# Database Configuration (Supabase PostgreSQL)
# Get these from your Supabase project settings
DATASOURCES_DEFAULT_HOST=[your-supabase-host].supabase.co
DATASOURCES_DEFAULT_PORT=5432
DATASOURCES_DEFAULT_DATABASE=passbolt
DATASOURCES_DEFAULT_USERNAME=postgres
DATASOURCES_DEFAULT_PASSWORD=[your-supabase-password]
DATASOURCES_DEFAULT_DRIVER=Postgres

# Email Configuration (Resend)
EMAIL_DEFAULT_FROM_NAME=Passbolt
EMAIL_DEFAULT_FROM=passbolt@theportlandcompany.com
EMAIL_TRANSPORT_DEFAULT_HOST=smtp.resend.com
EMAIL_TRANSPORT_DEFAULT_PORT=465
EMAIL_TRANSPORT_DEFAULT_USERNAME=resend
EMAIL_TRANSPORT_DEFAULT_PASSWORD=[your-resend-api-key]
EMAIL_TRANSPORT_DEFAULT_TLS=true

# GPG Settings
PASSBOLT_KEY_EMAIL=passbolt@theportlandcompany.com
PASSBOLT_GPG_SERVER_KEY_PUBLIC=/etc/passbolt/gpg/serverkey.asc
PASSBOLT_GPG_SERVER_KEY_PRIVATE=/etc/passbolt/gpg/serverkey_private.asc

# Security
PASSBOLT_SECURITY_SMTP_SETTINGS_ENDPOINTS_DISABLED=false
PASSBOLT_PLUGINS_EXPORT_ENABLED=true

# Registration (set to true if you want public registration)
PASSBOLT_REGISTRATION_PUBLIC=false

# SSL Settings
PASSBOLT_SSL_FORCE=true

# JWT Settings
PASSBOLT_PLUGINS_JWT_AUTHENTICATION_ENABLED=true
PASSBOLT_PLUGINS_JWT_AUTHENTICATION_SECRET_KEY=[generate-a-secure-random-string]

# Railway Settings
PORT=8080
RAILWAY_ENVIRONMENT=production
```

## Steps to Configure:

1. **Supabase Database**:
   - Go to your Supabase project Settings > Database
   - Get the connection string and extract the host, password
   - The database "passbolt" will be created automatically

2. **Resend API Key**:
   - Use the API key from your .env file
   - Or create a new one at resend.com

3. **JWT Secret**:
   - Generate a secure random string (32+ characters)
   - You can use: `openssl rand -base64 32`

4. **Deploy to Railway**:
   - These variables should be set in Railway's service variables
   - Railway will automatically inject them into the container