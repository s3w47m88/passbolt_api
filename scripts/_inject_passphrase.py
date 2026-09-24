#!/usr/bin/env python3
"""Inject the passphrase from env PP into the go-passbolt-cli TOML config,
replacing only the userpassword line. The passphrase never appears on argv."""
import os
CFG = os.path.expanduser(
    "~/Library/Application Support/go-passbolt-cli/go-passbolt-cli.toml")
pp = os.environ["PP"]
def toml_basic(s: str) -> str:
    return '"' + s.replace("\\", "\\\\").replace('"', '\\"') + '"'
lines = open(CFG).read().splitlines(keepends=True)
out = []
for l in lines:
    if l.startswith("userpassword ="):
        out.append("userpassword = " + toml_basic(pp) + "\n")
    else:
        out.append(l)
open(CFG, "w").write("".join(out))
print("passphrase injected into config (mode 600)")
