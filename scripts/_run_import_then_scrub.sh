#!/bin/bash
# Runs the import (new items only), then ALWAYS scrubs the passphrase +
# private key from the go-passbolt-cli config afterward (even on failure).
# Launched detached by import_now.sh.
set -uo pipefail
cd "$(dirname "$0")/.."

export KDBX_FILE="$HOME/passbolt-secrets/1password-export-20260901-115425.kdbx"
export KDBX_PASSWORD_FILE="$KDBX_FILE.password.txt"
export EXISTING_TRIPLES_FILE="$HOME/passbolt-secrets/existing_triples.txt"

# --- dump the vault's existing (name|user|uri) triples for live dedup ---
set -a; . ./.env 2>/dev/null; set +a
MYSQL="/opt/homebrew/opt/mysql-client/bin/mysql"
PUB=$(railway variables -s MySQL --json 2>/dev/null \
  | .venv/bin/python -c 'import sys,json;print(json.load(sys.stdin).get("MYSQL_PUBLIC_URL",""))')
if [ -n "$PUB" ]; then
  eval "$(.venv/bin/python - "$PUB" <<'PY'
import sys
from urllib.parse import urlparse
u=urlparse(sys.argv[1])
print(f'DBHOST="{u.hostname}"');print(f'DBPORT="{u.port or 3306}"')
print(f'DBUSER="{u.username}"');print(f'DBPASS={repr(u.password)}'.replace("'",'"'))
print(f'DBNAME="{u.path.lstrip("/")}"')
PY
)"
  MYSQL_PWD="$DBPASS" "$MYSQL" --host="$DBHOST" --port="$DBPORT" --user="$DBUSER" "$DBNAME" -N -e \
    "SELECT CONCAT(LOWER(TRIM(name)),'|',LOWER(TRIM(COALESCE(username,''))),'|',LOWER(TRIM(COALESCE(uri,'')))) FROM resources WHERE deleted=0;" \
    > "$EXISTING_TRIPLES_FILE" 2>/dev/null
  echo "dedup source: $(wc -l < "$EXISTING_TRIPLES_FILE") existing triples"
else
  echo "WARNING: could not read DB for dedup; import will add ALL new-with-password items"
  rm -f "$EXISTING_TRIPLES_FILE"
fi

.venv/bin/python scripts/import_kdbx_to_passbolt.py --run
RC=$?

# scrub secrets from the CLI config regardless of outcome
.venv/bin/python - <<'PY'
import os
cfg = os.path.expanduser(
    "~/Library/Application Support/go-passbolt-cli/go-passbolt-cli.toml")
try:
    lines = open(cfg).read().splitlines(keepends=True)
    out = []
    for l in lines:
        if l.startswith("userpassword ="):
            out.append("userpassword = ''\n")
        elif l.startswith("userprivatekey ="):
            out.append("userprivatekey = ''\n")
        else:
            out.append(l)
    open(cfg, "w").write("".join(out))
    print("config scrubbed (passphrase + private key removed)")
except FileNotFoundError:
    pass
PY

# remove the plaintext dedup dump
rm -f "$EXISTING_TRIPLES_FILE"
echo "IMPORT_WRAPPER_DONE rc=$RC"
