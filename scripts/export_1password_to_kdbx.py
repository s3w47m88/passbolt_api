#!/usr/bin/env python3
"""
Export all Login items from one or more 1Password accounts into a single
password-protected KDBX file suitable for import into Passbolt (via the
browser extension's KDBX import).

- Reads secrets through the `op` CLI (desktop-app session must be unlocked).
- Groups entries by account -> vault.
- Captures title, username, password, URL, notes, and TOTP (otpauth URI)
  when 1Password exposes the seed.
- The KDBX is encrypted with a generated NIST-compliant passphrase written
  alongside it (mode 600). Delete both after importing.

Usage:
  export_1password_to_kdbx.py OUT.kdbx acct1 [acct2 ...]
"""
from __future__ import annotations
import json, os, secrets, string, subprocess, sys, time
from concurrent.futures import ThreadPoolExecutor, as_completed

WORKERS = 6
GET_TIMEOUT = 30


def op(args: list[str], account: str, timeout: int = 20) -> str:
    return subprocess.run(
        ["op", *args, "--account", account, "--format", "json"],
        capture_output=True, text=True, timeout=timeout,
    ).stdout


def list_logins(account: str) -> list[dict]:
    out = op(["item", "list", "--categories", "Login"], account)
    try:
        return json.loads(out) if out.strip() else []
    except json.JSONDecodeError:
        return []


def get_item(account: str, item_id: str) -> dict | None:
    for attempt in range(2):
        try:
            out = subprocess.run(
                ["op", "item", "get", item_id, "--account", account,
                 "--format", "json"],
                capture_output=True, text=True, timeout=GET_TIMEOUT,
            ).stdout
            if out.strip():
                return json.loads(out)
        except (subprocess.TimeoutExpired, json.JSONDecodeError):
            time.sleep(0.5)
    return None


def extract(item: dict) -> dict:
    title = item.get("title", "(untitled)")
    username = password = notes = totp = ""
    for f in item.get("fields", []) or []:
        purpose = f.get("purpose"); ftype = f.get("type"); val = f.get("value", "")
        if purpose == "USERNAME" and val:
            username = val
        elif purpose == "PASSWORD" and val:
            password = val
        elif f.get("label") == "notesPlain" and val:
            notes = val
        elif ftype == "OTP" and isinstance(val, str) and val.startswith("otpauth://"):
            totp = val
    urls = item.get("urls") or []
    url = ""
    for u in urls:
        if u.get("primary"):
            url = u.get("href", ""); break
    if not url and urls:
        url = urls[0].get("href", "")
    vault = (item.get("vault") or {}).get("name", "Unknown")
    return {"title": title, "username": username, "password": password,
            "url": url, "notes": notes, "totp": totp, "vault": vault}


def gen_passphrase(n: int = 40) -> str:
    alpha = string.ascii_letters + string.digits + "!@#$%^&*()-_=+"
    return "".join(secrets.choice(alpha) for _ in range(n))


def main() -> int:
    out_path = sys.argv[1]
    accounts = sys.argv[2:]
    if not accounts:
        print("no accounts given", file=sys.stderr); return 2

    from pykeepass import create_database

    passphrase = gen_passphrase()
    kp = create_database(out_path, password=passphrase)

    total_ok = total_fail = 0
    summary = []
    for account in accounts:
        items = list_logins(account)
        acct_group = kp.add_group(kp.root_group, f"1Password: {account}")
        vault_groups: dict[str, object] = {}
        results = []
        with ThreadPoolExecutor(max_workers=WORKERS) as ex:
            futs = {ex.submit(get_item, account, it["id"]): it for it in items}
            for fut in as_completed(futs):
                item = fut.result()
                if item is None:
                    total_fail += 1
                    continue
                results.append(extract(item))
        for r in results:
            vname = r["vault"]
            if vname not in vault_groups:
                vault_groups[vname] = kp.add_group(acct_group, vname)
            entry = kp.add_entry(
                vault_groups[vname], title=r["title"] or "(untitled)",
                username=r["username"], password=r["password"],
                url=r["url"], notes=r["notes"],
                force_creation=True,  # 1Password allows duplicate titles
            )
            if r["totp"]:
                # "otp" is reserved in pykeepass; store the otpauth URI under a
                # plain custom key so the seed is preserved for later use.
                entry.set_custom_property("otpauth", r["totp"])
            total_ok += 1
        summary.append((account, len(items), len(results)))

    kp.save()

    pw_path = out_path + ".password.txt"
    with open(pw_path, "w") as fh:
        fh.write(passphrase + "\n")
    os.chmod(pw_path, 0o600)
    os.chmod(out_path, 0o600)

    print("=== export complete ===")
    for acct, listed, got in summary:
        print(f"  {acct}: listed {listed}, exported {got}")
    print(f"  entries written: {total_ok}  fetch failures: {total_fail}")
    print(f"  kdbx: {out_path}")
    print(f"  password file: {pw_path}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
