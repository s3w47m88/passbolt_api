# Passbolt Private Installation

Private Passbolt password manager deployment configured for Railway and Supabase.

## Architecture

- **Application**: Passbolt Community Edition (CE)
- **Deployment**: Railway (Docker-based)
- **Database**: Supabase PostgreSQL
- **Email**: Resend SMTP
- **Security**: GitGuardian pre-commit hooks

## Setup Instructions

### 1. Configure Supabase Database

The application will automatically create a `passbolt` database in your Supabase instance. You need to provide:
- Supabase host (from your project settings)
- Database password

### 2. Set Railway Environment Variables

Copy the variables from `docs/railway-variables.md` to your Railway service settings.

### 3. Configure DNS

After deployment, point your domain to Railway:
- Add a CNAME record for `passbolt.theportlandcompany.com`
- Point it to your Railway service URL

### 4. Access Passbolt

Once deployed and DNS is configured:
1. Navigate to https://passbolt.theportlandcompany.com
2. Complete the setup wizard
3. Install the browser extension
4. Create your admin account

## Security

- All credentials are stored as environment variables
- GPG keys are generated automatically on first run
- GitGuardian pre-commit hook prevents secret leaks
- HTTPS is enforced via Railway's built-in SSL

## Maintenance

### Backup
- Database is backed up through Supabase
- GPG keys are stored in the container (persist through Railway volumes)

### Updates
- Update the Docker image tag in `Dockerfile`
- Push to GitHub to trigger Railway redeploy

## Support

For issues specific to this deployment, check:
- Railway logs for application errors
- Supabase logs for database issues
- Passbolt healthcheck at `/healthcheck`