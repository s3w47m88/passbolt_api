# Context for AI
- Claude Code and ChatGPT Codex are managing this application and deployment together.
- Developers should always check the codebase before creating plans and editing code because there are other developers, including AI, writing at the same time sometimes.
- This is a private Passbolt repository and project.
- It is hosted on GitHub.
- It must have pre-commit hooks, linting and CI/CD for security and automation. 
- Developers should always create new pre-commit hooks, linting and CI/CD when there is a sensible opportunity to do so.
- GitHub CI/CD features should be utilized.
- Deployments are automated via GitHub CI/CD and config files for Railway NixPacks.

# Passbolt CE on Railway (MariaDB)

Hardened Passbolt Community Edition deployment for Railway using a managed MySQL/MariaDB database (Railway “MySQL” service). Includes local Docker Compose for smoke tests.

## What’s inside

- Docker image pinned to a specific Passbolt CE version with the GD PHP extension installed
- Environment-driven configuration (no baked app settings in the image)
- Example env file (`.env.example`) and Railway variable list (`docs/railway-variables.md`)
- Local `docker-compose.yml` for a quick MariaDB-backed smoke test
- Lightweight CI to build the image

## Quick start (local smoke test)

1) `cp .env.example .env` and adjust values (especially passwords, JWT secret, base URL).
2) `docker compose up -d`.
3) Open the Passbolt setup wizard at http://localhost:8080 and complete initialization.
4) Stop with `docker compose down` when done.

## Deploy to Railway

1) Create a Railway project and add the “MySQL” service (MariaDB-compatible).
2) Set service variables using `docs/railway-variables.md`. Registration should stay `false` after initial setup.
3) Point your domain’s CNAME to the Railway service URL. Keep `PASSBOLT_SSL_FORCE=true` so traffic stays HTTPS-only.
4) Deploy: push to the main branch; Railway builds from `Dockerfile`.
5) Run migrations on first deploy (once): `railway run --service <passbolt-service> "cd /usr/share/php/passbolt && ./bin/cake passbolt migrate --no-lock && ./bin/cake passbolt install --no-admin --force"`.

## Operations notes

- DB engine: Use MariaDB/MySQL only (Passbolt does not support Postgres). The Railway MySQL service is compatible.
- Registration: Enable `PASSBOLT_REGISTRATION_PUBLIC=true` only during the first admin creation, then revert to `false`.
- SSL/Proxies: Railway terminates TLS; keep `PASSBOLT_SSL_FORCE=true` and `PASSBOLT_SECURITY_PROXIES=*` so Passbolt trusts the platform proxy.
- Health check: `GET /healthcheck` on the service URL.

## Updating

- Bump the pinned tag in `Dockerfile`, deploy, then run migrations as above.
- Review release notes before upgrading major versions.

## Files to know

- `Dockerfile` – builds the Passbolt container (pinned version, no baked config).
- `.env.example` – baseline env vars; copy to `.env` locally.
- `docs/railway-variables.md` – required Railway variables.
- `docker-compose.yml` – local MariaDB + Passbolt stack for smoke testing.
- `init-database.sh` – helper to run migrations/install via Railway CLI (override service name with `SERVICE` env).

## Security & quality checks

- Pre-commit (local): `pip install pre-commit && pre-commit install`; then `pre-commit run --all-files`. Hooks: ggshield secret scan, shellcheck, shfmt, hadolint, yamllint, markdownlint.
- CI (GitHub Actions): runs the same pre-commit suite plus a Docker build. Configure the `GITGUARDIAN_API_KEY` GitHub secret for ggshield.
