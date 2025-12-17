# MariaDB / MySQL setup for Passbolt (Railway)

## Steps

1. In your Railway project, add a new **Database → MySQL** service (Railway runs a MariaDB-compatible engine).
2. Name it `MySQL` so `${{MySQL.*}}` references resolve.
3. In the Passbolt service variables, set the DB vars from `docs/railway-variables.md`.
4. Deploy, then run migrations/install once via `init-database.sh` (set `SERVICE=<passbolt-service>` if needed).
5. Create the first admin (temporarily set `PASSBOLT_REGISTRATION_PUBLIC=true`, then flip back to `false`).

## Architecture

```text
[Browser] --TLS--> [Railway edge] --> [Passbolt service :80] --> [MySQL/MariaDB service :3306]
```

## Troubleshooting

- Service name must match the `${{MySQL.*}}` references.
- Both services must live in the same Railway project/environment.
- Check Railway logs for connection errors; ensure DB user/password values are correct.
