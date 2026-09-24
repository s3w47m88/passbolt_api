#!/usr/bin/env python3
"""
Import a KDBX (built by export_1password_to_kdbx.py) into Passbolt via
go-passbolt-cli, preserving the account -> vault structure as Passbolt folders.

Auth is read from environment variables (never the command line, so the
passphrase does not appear in the process list):
  PB_SERVER            e.g. https://passbolt.theportlandcompany.com
  PB_PRIVATE_KEY_FILE  path to the user's Passbolt private key (.asc)
  PB_PASSPHRASE        the passphrase that unlocks that private key
  KDBX_FILE            path to the .kdbx
  KDBX_PASSWORD_FILE   path to the .kdbx.password.txt

Modes:
  --plan   parse the KDBX and print the folder/resource plan (no network)
  --run    actually create folders + resources on the server

Entries with no password are skipped and reported (Passbolt resources
require a secret); their titles are written to a .skipped.txt file.
"""
from __future__ import annotations
import os, subprocess, sys, json, time

def sh(args: list[str], env: dict, timeout: int = 60) -> tuple[int, str, str]:
    p = subprocess.run(args, env=env, capture_output=True, text=True, timeout=timeout)
    return p.returncode, p.stdout.strip(), p.stderr.strip()

def cli_env() -> dict:
    # go-passbolt-cli reads auth (server, private key, passphrase, mfamode)
    # from its config file, populated by inject_passphrase / import_now.
    # Nothing extra needed here.
    return dict(os.environ)

def create_folder(name: str, parent_id: str | None, env: dict) -> str:
    args = ["passbolt", "create", "folder", "--name", name, "--json"]
    if parent_id:
        args += ["--folderParentID", parent_id]
    rc, out, err = sh(args, env)
    if rc != 0:
        raise RuntimeError(f"folder '{name}' failed: {err or out}")
    try:
        j = json.loads(out)
        return j.get("id") or j.get("ID") or out.strip()
    except json.JSONDecodeError:
        return out.strip()  # some builds print the bare id

def create_resource(r: dict, folder_id: str | None, env: dict) -> None:
    args = [
        "passbolt", "create", "resource",
        "--type", "password-and-description",
        "--name", r["title"] or "(untitled)",
        "--username", r["username"],
        "--uri", r["url"],
        "--password", r["password"],
    ]
    if folder_id:
        args += ["--folderParentID", folder_id]
    desc = r["notes"]
    if not folder_id:
        # CE has no folders: preserve origin in the description so it stays
        # searchable/filterable (e.g. "Source: ... / Dept. of Finance").
        src = f"Source: {r['account']} / {r['vault']}"
        desc = src + ("\n\n" + desc if desc else "")
    if r.get("totp"):
        desc = (desc + "\n\n" if desc else "") + "TOTP: " + r["totp"]
    if desc:
        args += ["--description", desc]
    rc, out, err = sh(args, env)
    if rc != 0:
        raise RuntimeError(f"resource '{r['title']}' failed: {err or out}")

def load_kdbx() -> list[dict]:
    from pykeepass import PyKeePass
    pw = open(os.environ["KDBX_PASSWORD_FILE"]).read().strip()
    kp = PyKeePass(os.environ["KDBX_FILE"], password=pw)
    rows = []
    for g in kp.root_group.subgroups:            # account groups
        for sv in g.subgroups:                   # vault groups
            for e in sv.entries:
                rows.append({
                    "account": g.name, "vault": sv.name,
                    "title": e.title or "", "username": e.username or "",
                    "url": e.url or "", "password": e.password or "",
                    "notes": e.notes or "",
                    "totp": e.get_custom_property("otpauth") or "",
                })
    return rows

def _triple(name: str, user: str, uri: str) -> str:
    return (name.strip().lower() + "|" + user.strip().lower()
            + "|" + uri.strip().lower())

def load_existing_triples() -> set[str] | None:
    """Existing (name|user|uri) triples already in the vault, so we skip
    re-importing them. Written by the wrapper to EXISTING_TRIPLES_FILE."""
    path = os.environ.get("EXISTING_TRIPLES_FILE")
    if not path or not os.path.exists(path):
        return None
    return {l.strip() for l in open(path) if l.strip()}

def main() -> int:
    mode = sys.argv[1] if len(sys.argv) > 1 else "--plan"
    rows = load_kdbx()
    no_pw = [r for r in rows if not r["password"]]

    # Skip entries already present in the vault (exact name|user|uri match),
    # so we import only genuinely-new items and stay idempotent on re-run.
    existing = load_existing_triples()
    already = 0
    have_pw = []
    for r in rows:
        if not r["password"]:
            continue
        if existing is not None and _triple(r["title"], r["username"], r["url"]) in existing:
            already += 1
            continue
        have_pw.append(r)
    if existing is None:
        print("  WARNING: no EXISTING_TRIPLES_FILE -> dedup disabled, importing all with-password items")
    else:
        print(f"  dedup: {already} already in vault (skipped), {len(have_pw)} new to import")

    # structure
    accounts: dict[str, set] = {}
    for r in rows:
        accounts.setdefault(r["account"], set()).add(r["vault"])

    print(f"=== plan ===")
    print(f"  total entries: {len(rows)}  importable(with password): {len(have_pw)}  skipped(no password): {len(no_pw)}")
    for a, vs in accounts.items():
        print(f"  folder: {a}")
        for v in sorted(vs):
            cnt = sum(1 for r in have_pw if r['account']==a and r['vault']==v)
            print(f"      folder: {v}  ({cnt} resources)")

    if no_pw:
        skip_path = os.environ["KDBX_FILE"] + ".skipped.txt"
        with open(skip_path, "w") as fh:
            for r in no_pw:
                fh.write(f"{r['account']} / {r['vault']} / {r['title']}\n")
        print(f"  skipped titles written to: {skip_path}")

    if mode != "--run":
        return 0

    env = cli_env()
    # verify auth once with a cheap call (list resource works on CE)
    rc, out, err = sh(["passbolt", "list", "resource", "--json"], env, timeout=60)
    if rc != 0:
        print(f"AUTH/CONNECT FAILED: {err or out}", file=sys.stderr)
        return 3
    print("  auth OK")

    # Folders are a Passbolt Pro feature; on CE they fail. Probe once, then
    # either build the folder tree or fall back to a flat import.
    vault_folder_ids: dict[tuple, str] = {}
    folders_ok = True
    try:
        probe = create_folder("__import_probe__", None, env)
        # clean up the probe folder if the CLI supports delete
        sh(["passbolt", "delete", "folder", "--id", probe], env, timeout=30)
    except Exception as ex:
        folders_ok = False
        print(f"  folders unavailable (CE) -> flat import. ({str(ex)[:80]})")

    if folders_ok:
        acct_folder_ids: dict[str, str] = {}
        for a, vs in accounts.items():
            acct_folder_ids[a] = create_folder(a, None, env)
            for v in sorted(vs):
                vault_folder_ids[(a, v)] = create_folder(v, acct_folder_ids[a], env)
        print(f"  created {len(acct_folder_ids)} account folders, {len(vault_folder_ids)} vault folders")

    ok = fail = 0
    fails = []
    t0 = time.time()
    for i, r in enumerate(have_pw, 1):
        try:
            fid = vault_folder_ids.get((r["account"], r["vault"])) if folders_ok else None
            create_resource(r, fid, env)
            ok += 1
        except Exception as ex:
            fail += 1; fails.append((r["title"], str(ex)[:120]))
        if i % 50 == 0:
            print(f"  {i}/{len(have_pw)}  ok={ok} fail={fail}  ({time.time()-t0:.0f}s)")
    print(f"=== import done: ok={ok} fail={fail} skipped_no_pw={len(no_pw)} ===")
    if fails:
        fp = os.environ["KDBX_FILE"] + ".failed.txt"
        with open(fp, "w") as fh:
            for t, e in fails: fh.write(f"{t}\t{e}\n")
        print(f"  failures written to: {fp}")
    return 0

if __name__ == "__main__":
    sys.exit(main())
