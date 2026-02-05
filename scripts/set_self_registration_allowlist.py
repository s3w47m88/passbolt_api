#!/usr/bin/env python3
"""
Set Passbolt Self Registration allowlist (email domains) in the Railway MySQL DB.

This writes the `organization_settings` row for property `selfRegistration` to:
  {"provider":"email_domains","data":{"allowed_domains":[...]}}

It uses `railway variables -s MySQL --json` to discover the public MySQL URL.
"""

from __future__ import annotations

import json
import os
import subprocess
import sys
import uuid
from datetime import datetime, timezone
from urllib.parse import urlparse

import pymysql


PASSBOLT_UUID_V5_NAMESPACE = uuid.UUID("d5447ca1-950f-459d-8b20-86ddfdd0f922")
ORG_SETTING_PROPERTY = "selfRegistration"
ORG_SETTING_PROPERTY_ID = uuid.uuid5(
    PASSBOLT_UUID_V5_NAMESPACE, f"organization.settings.property.id.{ORG_SETTING_PROPERTY}"
)


def _utc_now_mysql() -> str:
    # MySQL DATETIME, UTC
    return datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M:%S")


def _railway_mysql_public_url() -> str:
    try:
        out = subprocess.check_output(
            ["railway", "variables", "-s", "MySQL", "--json"],
            cwd=os.getcwd(),
            stderr=subprocess.STDOUT,
            text=True,
        )
    except subprocess.CalledProcessError as e:
        raise RuntimeError(f"Failed to read Railway variables for MySQL: exit={e.returncode}") from e

    data = json.loads(out)
    url = data.get("MYSQL_PUBLIC_URL")
    if not url:
        raise RuntimeError("MYSQL_PUBLIC_URL was not present in `railway variables -s MySQL --json` output.")
    return url


def _connect(url: str):
    parsed = urlparse(url)
    if parsed.scheme != "mysql":
        raise RuntimeError(f"Unexpected MYSQL_PUBLIC_URL scheme: {parsed.scheme!r}")

    host = parsed.hostname
    port = parsed.port or 3306
    user = parsed.username or ""
    password = parsed.password or ""
    database = (parsed.path or "").lstrip("/")

    if not host or not user or not database:
        raise RuntimeError("MYSQL_PUBLIC_URL was missing host/user/database parts.")

    return pymysql.connect(
        host=host,
        port=port,
        user=user,
        password=password,
        database=database,
        charset="utf8mb4",
        cursorclass=pymysql.cursors.DictCursor,
        autocommit=False,
    )


def _get_admin_user_id(cur) -> str:
    # Prefer an admin user for created_by/modified_by FK integrity.
    cur.execute(
        """
        SELECT u.id
        FROM users u
        JOIN roles r ON r.id = u.role_id
        WHERE r.name = 'admin'
        ORDER BY u.created ASC
        LIMIT 1
        """
    )
    row = cur.fetchone()
    if row and row.get("id"):
        return row["id"]

    # Some instances may not have any users yet (e.g. pre-setup). The
    # organization_settings schema doesn't enforce FK constraints, but requires
    # non-null UUID-like strings.
    return str(uuid.uuid4())


def _read_current_setting(cur) -> str | None:
    cur.execute(
        """
        SELECT value
        FROM organization_settings
        WHERE property_id = %s
        ORDER BY modified DESC
        LIMIT 1
        """,
        (str(ORG_SETTING_PROPERTY_ID),),
    )
    row = cur.fetchone()
    return row["value"] if row else None


def _upsert_setting(cur, allowed_domains: list[str]) -> None:
    now = _utc_now_mysql()
    admin_id = _get_admin_user_id(cur)

    payload = {"provider": "email_domains", "data": {"allowed_domains": allowed_domains}}
    value = json.dumps(payload, separators=(",", ":"), sort_keys=True)

    cur.execute(
        """
        SELECT id
        FROM organization_settings
        WHERE property_id = %s
        ORDER BY created ASC
        """,
        (str(ORG_SETTING_PROPERTY_ID),),
    )
    rows = cur.fetchall()

    if not rows:
        cur.execute(
            """
            INSERT INTO organization_settings
              (id, property_id, property, value, created, modified, created_by, modified_by)
            VALUES
              (%s, %s, %s, %s, %s, %s, %s, %s)
            """,
            (
                str(uuid.uuid4()),
                str(ORG_SETTING_PROPERTY_ID),
                ORG_SETTING_PROPERTY,
                value,
                now,
                now,
                admin_id,
                admin_id,
            ),
        )
        return

    keep_id = rows[0]["id"]
    cur.execute(
        """
        UPDATE organization_settings
        SET value = %s,
            property = %s,
            modified = %s,
            modified_by = %s
        WHERE id = %s
        """,
        (value, ORG_SETTING_PROPERTY, now, admin_id, keep_id),
    )

    extra_ids = [r["id"] for r in rows[1:] if r.get("id")]
    if extra_ids:
        cur.execute(
            f"DELETE FROM organization_settings WHERE id IN ({','.join(['%s'] * len(extra_ids))})",
            tuple(extra_ids),
        )


def main(argv: list[str]) -> int:
    if len(argv) < 2 or not argv[1].strip():
        print("Usage: scripts/set_self_registration_allowlist.py <domain> [<domain> ...]", file=sys.stderr)
        return 2

    allowed_domains = [d.strip().lower() for d in argv[1:] if d.strip()]
    if not allowed_domains:
        print("No domains provided.", file=sys.stderr)
        return 2

    url = _railway_mysql_public_url()
    conn = _connect(url)
    try:
        with conn.cursor() as cur:
            before = _read_current_setting(cur)
            _upsert_setting(cur, allowed_domains)
        conn.commit()
    except Exception:
        conn.rollback()
        raise
    finally:
        conn.close()

    # Print only non-sensitive confirmation.
    if before:
        try:
            before_obj = json.loads(before)
            before_domains = before_obj.get("data", {}).get("allowed_domains")
        except Exception:
            before_domains = None
    else:
        before_domains = None

    print(f"Updated self-registration allowed domains: {allowed_domains}")
    if before_domains is not None:
        print(f"Previous allowed domains: {before_domains}")

    return 0


if __name__ == "__main__":
    raise SystemExit(main(sys.argv))
