#!/bin/bash
# Prompts for the Passbolt account passphrase WITHOUT echoing it, injects it
# into the go-passbolt-cli config, and validates authentication.
# The passphrase is never printed and never passed on any command line.
set -euo pipefail
cd "$(dirname "$0")/.."

read -rs -p "Passbolt account passphrase: " PP; echo
export PP
.venv/bin/python scripts/_inject_passphrase.py
unset PP

echo "Validating against https://passbolt.theportlandcompany.com ..."
if passbolt list folder --mfaMode none >/dev/null 2>&1; then
  echo "AUTH_OK"
else
  # folders may 402/403 on CE; fall back to a resource list to confirm auth
  if passbolt list resource --mfaMode none >/dev/null 2>&1; then
    echo "AUTH_OK"
  else
    echo "AUTH_FAILED (wrong passphrase, or server unreachable)"
  fi
fi
