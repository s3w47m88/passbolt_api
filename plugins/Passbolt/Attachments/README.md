# Passbolt/Attachments plugin

End-to-end-encrypted file attachments on resources. The server stores only
AES-256-GCM ciphertext (opaque); the per-file data key lives inside each
resource's OpenPGP secret (`attachments[]`), so Passbolt's native sharing and
revocation apply with no extra key management. See
`../../../docs/attachments-fork-design.md` for the full design.

## Contents
- `src/AttachmentsPlugin.php` — CakePHP BasePlugin.
- `config/routes.php` — `/attachments/*` routes (JSON; download returns raw bytes).
- `config/bootstrap.php`, `config/config.php` — plugin config (size cap, enabled).
- `src/Model/{Table,Entity}` — `attachments` table + entity (blob hidden from JSON).
- `src/Controller/*` — Index (list), Create (upload), Download (bytes), Delete.
- `src/Controller/Traits/AttachmentsAccessTrait.php` — read/update authz via PermissionsTable.
- `config/Migrations/` — create `attachments` table; add `attachments` to the
  v5 secret schema (by rewriting `resource_types.definition`, no core fork).

## API
| Method | Path | Perm | Body / Response |
|---|---|---|---|
| GET | `/attachments/{resourceId}.json` | read | list metadata (no bytes) |
| POST | `/attachments/{resourceId}.json` | update | multipart: `data` (ciphertext), `id`,`filename`,`mime`,`size` |
| GET | `/attachments/{resourceId}/{attachmentId}` | read | raw ciphertext (`application/octet-stream`) |
| DELETE | `/attachments/{resourceId}/{attachmentId}.json` | update | — |

## Wiring into the image (custom build)
1. COPY this dir to `<app>/plugins/Passbolt/Attachments`.
2. Add PSR-4 autoload to the app `composer.json`:
   `"Passbolt\\Attachments\\": "./plugins/Passbolt/Attachments/src"` then
   `composer dump-autoload`.
3. Register the plugin in the app bootstrap (one line, after the core feature
   plugins): `$app->addPlugin('Passbolt/Attachments', ['bootstrap' => true, 'routes' => true]);`
   (patch the SolutionBootstrapper, or load from a `config/plugins.php` hook).
4. COPY the two migration files into the app `config/Migrations/` (migrations
   are centralized), then `bin/cake passbolt migrate` runs them at startup.
5. Raise body-size limits in the image: nginx `client_max_body_size 30m;`,
   php `upload_max_filesize=30M` and `post_max_size=30M`.

The exact in-image app path (e.g. `/usr/share/php/passbolt`) is confirmed
against the running container during the build stage.
