# Build a Native macOS Passbolt App (1Password-style)

## Context

Passbolt has no standalone native macOS desktop app — only a Windows client
(UWP+WebView2), a Safari-extension-packaged Mac App Store entry, and browser
extensions. We want a true native Mac app for our self-hosted (Railway) server.

A native Swift iOS client is already checked out at
`mobile-passbolt-ios/`, and its architecture makes a macOS port the lowest-risk
"fully native" path: ~35 SPM targets with a clean interface/impl split, all
OS access funneled through one `OSFeatures` seam, and — critically — the
OpenPGP core is a Gopenpgp xcframework that **already ships a `macos-arm64_x86_64`
slice**. So the crypto and all business logic are macOS-ready today; the real
work is the presentation layer (iPhone-only UIKit + phone-shaped SwiftUI).

**Goal:** a native, notarized macOS app at feature parity — pair to our server,
browse/view/edit/share secrets, password generator, folders, Touch ID unlock,
menu-bar quick access, and (stretch) system autofill — reusing the iOS Swift
codebase.

**Chosen approach:** Mac Catalyst target first (fastest route to a running,
logging-in app that reuses domain + crypto), then progressively de-iOS-ify the
UI for a real desktop window. This beats a from-scratch SwiftUI rewrite (months,
re-implements crypto/auth) and beats a WKWebView shell (UI stays a web bundle).

## Key files & seams (from exploration)

- Xcode project: `mobile-passbolt-ios/Passbolt/Passbolt.xcodeproj`
- Package: `mobile-passbolt-ios/Passbolt/PassboltPackage/Package.swift`
  (`swift-tools-version:6.0`, `platforms: [.iOS(.v16)]` — must add `.macOS`)
- OS seam (re-implement per-platform here): `Sources/OSFeatures/Features/Static/`
  — `OSBiometry`, `OSKeychain`, `OSCamera`, `OSPasteboard`, `OSLinkOpener`, `OSFiles`
- Crypto (reuse as-is): `Sources/Crypto/PGP.swift` +
  `Vendor/Gopenpgp.xcframework` (has `macos-arm64_x86_64`)
- Networking (reuse): `Sources/OSFeatures/Features/Dynamic/NetworkRequestExecutor.swift`
- Session/auth (reuse): `Sources/Session/` (`SessionActor`, GPGAuth challenge, MFA)
- Account model / server domain: `Sources/CommonModels/Accounts/Account.swift` (`domain`)
- Pairing (needs desktop UX): `Sources/PassboltAccountSetup/` +
  `PassboltApp/Screens/AccountTransfer/` (camera QR — deprecate on Mac, see below)
- UI backbone (biggest rework): `UIComponents` (UIKit VC stack), `Display`,
  phone nav `UICommons/Views/SwiftUI/DrawerMenu/`, `MainTabs`

## Plan

### Phase 0 — Toolchain & signing
- Confirm Xcode 16 + Swift 6, Apple Developer team, and a Mac App ID /
  provisioning profile. Add macOS keychain-sharing + hardened-runtime
  entitlements. Store no secrets in-repo (Railway server URL only, from `.env`).

### Phase 1 — Compile the domain for macOS (Catalyst)
- In `Package.swift`, add `.macOS(.v13)` to `platforms` (keep `.iOS(.v16)`).
- In `project.pbxproj`: set `SUPPORTS_MACCATALYST = YES`,
  `SUPPORTED_PLATFORMS` to include `macosx`, `TARGETED_DEVICE_FAMILY = "1,2"`.
- Build the package for macOS; triage compile errors target-by-target. Expect the
  domain/crypto/db/network targets to build with little change (Foundation +
  the macOS Gopenpgp slice). Guard genuinely iOS-only APIs with
  `#if targetEnvironment(macCatalyst)` / `#if os(macOS)` at the `OSFeatures` seam
  — the app never `#if os`-guards today, so this is greenfield but localized.

### Phase 2 — OSFeatures for macOS
Re-implement/condition each static feature:
- `OSKeychain`: macOS keychain semantics — drop iOS app-group access groups,
  add `kSecUseDataProtectionKeychain`; keep `SecItem*` + `LAContext` biometric gating.
- `OSBiometry`: `LAContext` Touch ID / Apple Watch (LocalAuthentication exists on macOS).
- `OSPasteboard`/`OSLinkOpener`/`OSFiles`: map `UIPasteboard`→`NSPasteboard`,
  `UIApplication.open`→`NSWorkspace`, document picker→`NSOpenPanel` (Catalyst may
  bridge some automatically; verify).
- `OSCamera`/QR: **desktops lack cameras** — replace primary pairing with
  **AccountKit file import** (the extension already exports this via
  `desktopTransferModel`/`AccountKitEntity`; the iOS side has `AccountKitTransfer`).
  Keep QR as optional fallback.

### Phase 3 — Pairing to our server, first login
- Wire the AccountKit-import pairing path end to end against our Railway Passbolt
  server; complete GPGAuth login, passphrase entry, MFA, and initial resource sync.
- **Milestone:** app lists real resources from our server and copies a decrypted
  password. Verifies crypto + network + session on macOS.

### Phase 4 — Desktop UI adaptation
- Replace phone tab-bar/drawer navigation with a macOS
  `NavigationSplitView` (sidebar: folders/tags/groups; list: resources;
  detail: secret). Reuse existing SwiftUI screens where they render acceptably in a
  regular-width window; rebuild the UIKit `UIComponents` navigation shell.
- Add a **menu-bar item** (`MenuBarExtra`) for 1Password-style quick access +
  global hotkey search.
- Feature-parity surfaces: password generator, create/edit/delete resource,
  share (users/groups permissions), folders, resource types (incl. TOTP/PIN),
  search/filter, settings. All back onto existing domain modules.

### Phase 5 — Autofill (stretch) & polish
- macOS **Credential Provider** (`ASCredentialProviderExtension`) mirroring the
  iOS `PassboltAutofill` target for system autofill in browsers/apps.
- Auto-update (Sparkle) for a direct-download build, or ship via Mac App Store.
- App icon, about, first-run, empty/loading states (per-region placeholders).

### Phase 6 — Signing, notarization, distribution
- Code-sign + hardened runtime, `notarytool` staple. Decide MAS vs direct DMG
  (MAS = sandbox constraints on keychain/autofill; direct = Sparkle updates).

## Verification
- **Build:** `xcodebuild -scheme Passbolt -destination 'platform=macOS'` green.
- **Login (real, no mocks):** pair via AccountKit to our Railway server, log in,
  and show the raw decrypted secret + resource list — never claim it works
  without showing real server data.
- **Crypto:** unit-test `PGP.swift` decrypt against a known resource on macOS slice.
- **Unlock:** lock app → Touch ID → passphrase recovered from keychain.
- **Parity checklist:** create/edit/share/delete a resource, generate a password,
  browse folders, TOTP display — each confirmed against the server UI.
- **Distribution:** `spctl -a -vvv` + `notarytool` staple pass on the signed build.

## Open decisions to confirm during build
- Catalyst (faster, phone-flavored UI) vs. converting to a native SwiftUI-macOS
  target (cleaner desktop UX, more UI rework). Plan starts Catalyst; can graduate.
- MAS vs. direct-download distribution (affects sandbox/autofill/update choices).
- Separate repo/target vs. adding a macOS target inside `mobile-passbolt-ios`
  (recommend: add target in-tree to share the SPM package).
