# Passbolt Hosting — Cost Analysis & Migration Options

_Last updated: 2026-07-10_

## 1. Cost diagnosis (Railway)

The Railway **Passbolt** service is ~**$7.05/mo**, and it is **~94% memory**:

| Line item | Usage | Rate | Cost |
|---|---|---|---|
| Memory | 28,550.88 minutely-GB | $0.000231 / GB / min | **$6.61** |
| CPU | 524.60 minutely-vCPU | $0.000463 / vCPU / min | $0.24 |
| Volume | 57,826.68 minutely-GB | $0.00000347 / GB / min | $0.20 |
| Egress | 0.02 GB | $0.05 / GB | $0.001 |

**Root cause:** this is **not a leak or misconfiguration**. CPU is idle; the cost is the
arithmetic floor of keeping a ~0.65 GB container's RAM alive 24/7 (0.65 GB × ~43,200 min/mo ×
$0.000231 ≈ $6.60). An always-on container is billed continuously for the memory it holds.

**The real total is higher:** MariaDB runs as a **separate always-on Railway service**
(~300–450 MB), billed the same way. Click **"View Cost by Service"** — the true monthly total
is likely **~$12–15/mo**, not $7.

**Why "pennies" isn't achievable:** $0.05-class pricing only exists for scale-to-zero,
per-request serverless. Passbolt cannot run in that model (see §2).

## 2. Cloudflare Pages/Workers — infeasible

Passbolt is a **CakePHP (PHP) monolith**: Apache + PHP-FPM + cron + server-side GPG/OpenPGP
crypto + a persistent **MariaDB** + a stateful volume, all long-running.

| Requirement | Passbolt needs | Cloudflare Workers/Pages |
|---|---|---|
| Runtime | PHP 8 | ❌ JS/WASM V8 isolates only |
| Process model | Persistent server + cron | ❌ Stateless, per-request, CPU-time capped |
| Database | MySQL/MariaDB | ❌ Only D1 (SQLite) / external DB proxy |
| Server-side crypto | php-gnupg / GPG | ❌ Not available |
| Persistent filesystem/volume | ✅ | ❌ |

There is **no port of Passbolt to Workers/Pages**. Cloudflare's only useful role here is a
**free DNS proxy / CDN in front of** wherever Passbolt actually runs (orange-cloud) — it hosts
nothing and saves no compute.

## 3. Hosting options — single ~2 GB VM running the existing `docker-compose.yml`

| Host | US-based? | Plan (~2 GB) | Monthly | Notes |
|---|---|---|---|---|
| **Oracle Cloud Always Free** | ✅ (US regions) | Ampere ARM ≤4 vCPU / 24 GB | **$0 forever** | ✅ Truly free; most setup; CC required; capacity varies by region |
| **RackNerd** | ✅ Los Angeles | 2 GB / ~40 GB | **~$2–4** (annual prepay) | ✅ Cheapest US; spartan panel |
| **Hetzner** | ❌ Germany (has US DCs) | CAX11 ARM 4 GB | **~$4.10** (€3.79) | ✅ Cheapest reliable overall |
| **Vultr** | ✅ FL/NJ | 2 GB / 1 vCPU | **$12** | Most Hetzner-like reputable US host |
| **Linode / Akamai** | ✅ Philadelphia | 2 GB | **$12** | Mature, hourly-capped billing |
| **DigitalOcean** | ✅ NYC | 2 GB | **$12** | Best docs/UX |
| **AWS Lightsail** | ✅ | 2 GB / 2 vCPU | **$12** | Simplest AWS path |
| **AWS EC2** | ✅ | t4g.small 2 GB | **~$14** on-demand | ❌ Most complex; metered egress; ~$7–8 only with 1-yr commit |

Only ~0.7 GB RAM is actually in use, so every option above is over-provisioned. **Bare metal
(a whole dedicated physical server, ~$40+/mo) is unnecessary** for a single-user Passbolt.

## 4. Recommendation

- **If the goal is $0:** **Oracle Cloud Always Free** (Ampere ARM) — runs the existing
  `docker-compose.yml` unchanged. Tradeoff: more setup, credit card required at signup,
  occasional "out of capacity" on Always Free ARM (retry / try another home region).
- **Best paid balance:** **Hetzner CAX11 (~$4)**, or **Vultr / Linode ($12)** if a US-based
  provider matters.
- **Cheapest US:** **RackNerd (~$2–4, annual prepay)**.
- Put **Cloudflare (free) DNS/proxy** in front of whichever host is chosen.

## 5. Migration approach (host-agnostic — for when a host is chosen)

1. Provision VM (Ubuntu 22.04/24.04 ARM), harden SSH, install Docker + compose plugin.
2. Copy repo; set a production `.env` (strong DB passwords, JWT secret, `APP_FULL_BASE_URL`,
   `PASSBOLT_SSL_FORCE=true`).
3. Export current data from Railway MariaDB (`mysqldump`) **and** the GPG server keyring volume
   (`/etc/passbolt/gpg`); import into the new stack.
   ⚠️ **The GPG server key is critical — losing it makes all stored secrets unrecoverable.**
   Verify the backup before touching Railway.
4. `docker compose up -d`; run `cake passbolt migrate` if needed; verify `/healthcheck`.
5. TLS: Caddy/Traefik with Let's Encrypt, or a Cloudflare origin cert; point DNS (Cloudflare,
   orange-cloud) at the VM.
6. Verify login **and decrypt an existing secret** end-to-end before decommissioning Railway.
7. Decommission Railway services only after a verified backup + working new instance.

## 6. Links

- Oracle Free Tier: https://www.oracle.com/cloud/free/ · Console: https://signup.cloud.oracle.com/
- RackNerd: https://www.racknerd.com/ · Deals: https://www.racknerd.com/BlackFriday/
- Hetzner Cloud: https://www.hetzner.com/cloud
- Vultr pricing: https://www.vultr.com/pricing/
