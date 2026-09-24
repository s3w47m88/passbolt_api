#!/bin/bash
# One-shot: prompt for the Passbolt passphrase (hidden), inject it, validate
# login, and — only if auth succeeds — launch the full import in the
# background. The passphrase is never echoed and never passed on a command line.
set -uo pipefail
cd "$(dirname "$0")/.."
LOG="$HOME/passbolt-secrets/import.log"

read -rs -p "Passbolt account passphrase: " PP; echo
export PP
.venv/bin/python scripts/_inject_passphrase.py
unset PP

echo "Validating against https://passbolt.theportlandcompany.com ..."
if ! passbolt list resource --mfaMode none >/dev/null 2>&1; then
  echo "AUTH_FAILED — wrong passphrase or server unreachable. Nothing imported."
  # scrub the bad passphrase back out
  .venv/bin/python - <<'PY'
import os
cfg=os.path.expanduser("~/Library/Application Support/go-passbolt-cli/go-passbolt-cli.toml")
l=open(cfg).read().splitlines(keepends=True)
open(cfg,"w").write("".join("userpassword = ''\n" if x.startswith("userpassword =") else x for x in l))
PY
  exit 1
fi

echo "AUTH_OK — launching import in background."
: > "$LOG"
nohup bash scripts/_run_import_then_scrub.sh >> "$LOG" 2>&1 &
echo "IMPORT_STARTED pid=$! log=$LOG"
