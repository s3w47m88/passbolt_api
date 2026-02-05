# Self Registration Allowlist (Email Domains)

Passbolt CE stores self registration settings in the database (table `organization_settings`, property `selfRegistration`).

This repo includes a helper script that updates the setting via Railway's MySQL `MYSQL_PUBLIC_URL`:

```bash
.venv/bin/pip install pymysql
.venv/bin/python scripts/set_self_registration_allowlist.py theportlandcompany.com
```

Notes:

- This allowlist is domain-based. To add a specific person on another domain, use Passbolt admin invites instead of broadening the allowlist.
- The script is idempotent: it updates the existing setting row if present, otherwise inserts it.
