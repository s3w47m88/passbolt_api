#!/usr/bin/env python3
"""
Directly insert new Passbolt resources (password-and-description type) into the
database for spencerhill@theportlandcompany.com, encrypting each secret to the
user's PUBLIC key (no account passphrase needed).

Replicates the exact shape of existing working rows across 4 tables:
  resources, secrets, secret_revisions, permissions

Dedups against the live vault (name|user|uri) so only genuinely-new items are
inserted, and records every inserted resource id to a rollback file.

Env:
  MYSQL_URL           mysql://user:pass@host:port/db  (Railway public URL)
  GNUPGHOME           a keyring containing the user's public key
  KDBX_FILE, KDBX_PASSWORD_FILE
Modes:
  --one   insert a single test resource and print its ids (for verification)
  --run   insert all new items
"""
from __future__ import annotations
import json, os, subprocess, sys, uuid
from datetime import datetime, timezone
from urllib.parse import urlparse
import pymysql
from pykeepass import PyKeePass

USER_ID = "d81b2089-0971-4731-8521-e9a1043cc1d5"        # spencerhill (admin)
FPR = "1FF5239AFB74C543FDAC988FA2A7B1E7CF68AC7A"          # his key
RT_PWD_DESC = "a28a04cd-6f53-518a-967c-9963bf9cec51"      # password-and-description
PERM_OWNER = 15
ROLLBACK = os.path.expanduser("~/passbolt-secrets/db_insert_rollback.txt")


def now_mysql() -> str:
    return datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M:%S")


def connect():
    u = urlparse(os.environ["MYSQL_URL"])
    return pymysql.connect(
        host=u.hostname, port=u.port or 3306, user=u.username,
        password=u.password, database=u.path.lstrip("/"),
        charset="utf8mb4", autocommit=False)


def existing_triples(cur) -> set[str]:
    cur.execute("SELECT LOWER(TRIM(name)),LOWER(TRIM(COALESCE(username,''))),"
                "LOWER(TRIM(COALESCE(uri,''))) FROM resources WHERE deleted=0")
    return {f"{n}|{u}|{r}" for (n, u, r) in cur.fetchall()}


def encrypt(plaintext: str) -> str:
    p = subprocess.run(
        ["gpg", "--batch", "--yes", "--armor", "--trust-model", "always",
         "--compress-algo", "none", "--encrypt", "--recipient", FPR],
        input=plaintext.encode(), capture_output=True)
    if p.returncode != 0 or b"BEGIN PGP MESSAGE" not in p.stdout:
        raise RuntimeError("encrypt failed: " + p.stderr.decode()[:200])
    return p.stdout.decode()


def load_new_items(cur) -> list[dict]:
    kp = PyKeePass(os.environ["KDBX_FILE"],
                   password=open(os.environ["KDBX_PASSWORD_FILE"]).read().strip())
    have = existing_triples(cur)
    items = []
    for g in kp.root_group.subgroups:
        for sv in g.subgroups:
            for e in sv.entries:
                if not e.password:
                    continue
                name = (e.title or "").strip()
                user = (e.username or "").strip()
                uri = (e.url or "").strip()
                trip = f"{name.lower()}|{user.lower()}|{uri.lower()}"
                if trip in have:
                    continue
                desc = (e.notes or "").strip()
                src = f"Source: {g.name} / {sv.name}"
                desc = src + ("\n\n" + desc if desc else "")
                totp = e.get_custom_property("otpauth")
                if totp:
                    desc += "\n\nTOTP: " + totp
                items.append({
                    "name": name[:255], "username": user[:255], "uri": uri[:1024],
                    "password": e.password, "description": desc[:9000],
                })
    return items


def insert_item(cur, it: dict, ts: str) -> str:
    rid = str(uuid.uuid4()); sid = str(uuid.uuid4())
    revid = str(uuid.uuid4()); pid = str(uuid.uuid4())
    secret = encrypt(json.dumps({"password": it["password"],
                                 "description": it["description"]},
                                ensure_ascii=False))
    cur.execute(
        "INSERT INTO resources (id,name,username,uri,description,deleted,"
        "created,modified,created_by,modified_by,resource_type_id) "
        "VALUES (%s,%s,%s,%s,NULL,0,%s,%s,%s,%s,%s)",
        (rid, it["name"], it["username"], it["uri"], ts, ts,
         USER_ID, USER_ID, RT_PWD_DESC))
    cur.execute(
        "INSERT INTO secret_revisions (id,resource_id,resource_type_id,"
        "deleted,created,modified,created_by,modified_by) "
        "VALUES (%s,%s,%s,NULL,%s,%s,%s,%s)",
        (revid, rid, RT_PWD_DESC, ts, ts, USER_ID, USER_ID))
    cur.execute(
        "INSERT INTO secrets (id,user_id,resource_id,secret_revision_id,"
        "deleted,created_by,modified_by,data,created,modified) "
        "VALUES (%s,%s,%s,%s,NULL,%s,%s,%s,%s,%s)",
        (sid, USER_ID, rid, revid, USER_ID, USER_ID, secret, ts, ts))
    cur.execute(
        "INSERT INTO permissions (id,aco,aco_foreign_key,aro,aro_foreign_key,"
        "type,created,modified) VALUES (%s,'Resource',%s,'User',%s,%s,%s,%s)",
        (pid, rid, USER_ID, PERM_OWNER, ts, ts))
    return rid


def main() -> int:
    mode = sys.argv[1] if len(sys.argv) > 1 else "--one"
    conn = connect(); cur = conn.cursor()
    items = load_new_items(cur)
    print(f"  new items to insert: {len(items)}")
    if not items:
        print("  nothing new — done."); return 0

    ts = now_mysql()
    inserted = []
    try:
        if mode == "--one":
            rid = insert_item(cur, items[0], ts)
            conn.commit()
            inserted.append(rid)
            print(f"  TEST insert OK: resource {rid} ({items[0]['name']!r})")
        else:
            for i, it in enumerate(items, 1):
                rid = insert_item(cur, it, ts)
                inserted.append(rid)
                if i % 100 == 0:
                    conn.commit()
                    print(f"  {i}/{len(items)} inserted")
            conn.commit()
            print(f"  ALL inserted: {len(inserted)} resources")
    except Exception as ex:
        conn.rollback()
        print(f"  ERROR (rolled back uncommitted batch): {ex}", file=sys.stderr)
        # still record what was committed
    finally:
        with open(ROLLBACK, "a") as fh:
            for rid in inserted:
                fh.write(rid + "\n")
        print(f"  rollback ids appended to: {ROLLBACK}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
