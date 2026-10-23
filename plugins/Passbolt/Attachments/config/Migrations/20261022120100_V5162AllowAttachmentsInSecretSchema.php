<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Add an optional `attachments` array to the SECRET schema of the v5 resource
 * types, so the per-file AES key + metadata can live inside the OpenPGP secret
 * and validate server-side. Additive and backward-compatible: stock clients
 * ignore the extra key; `attachments` is never required.
 *
 * Done by rewriting the `definition` JSON of the affected resource_types rows
 * rather than forking core SlugDefinition.php (keeps upstream divergence to zero).
 */
class V5162AllowAttachmentsInSecretSchema extends AbstractMigration
{
    private const SLUGS = ['v5-default', 'v5-default-with-totp', 'v5-note'];

    private function attachmentsPropertySchema(): array
    {
        return [
            'type' => 'array',
            'maxItems' => 64,
            'items' => [
                'type' => 'object',
                'required' => ['id', 'name', 'alg', 'iv', 'key'],
                'properties' => [
                    'id' => ['type' => 'string', 'format' => 'uuid'],
                    'name' => ['type' => 'string', 'maxLength' => 255],
                    'mime' => ['anyOf' => [['type' => 'string', 'maxLength' => 255], ['type' => 'null']]],
                    'size' => ['type' => 'number'],
                    'alg' => ['type' => 'string', 'enum' => ['A256GCM']],
                    // base64 of a 12-byte IV
                    'iv' => ['type' => 'string', 'maxLength' => 32],
                    'sha256' => ['anyOf' => [['type' => 'string', 'maxLength' => 64], ['type' => 'null']]],
                    // base64 of a 32-byte AES-256 key
                    'key' => ['type' => 'string', 'maxLength' => 64],
                ],
            ],
        ];
    }

    public function up(): void
    {
        $this->patch(true);
    }

    public function down(): void
    {
        $this->patch(false);
    }

    /**
     * @param bool $add true to add the attachments property, false to remove it.
     * @return void
     */
    private function patch(bool $add): void
    {
        $rows = $this->fetchAll(
            "SELECT id, slug, definition FROM resource_types WHERE slug IN ('"
            . implode("','", self::SLUGS) . "')"
        );
        foreach ($rows as $row) {
            $def = json_decode((string)$row['definition'], true);
            if (!is_array($def) || !isset($def['secret']['properties'])) {
                continue;
            }
            if ($add) {
                $def['secret']['properties']['attachments'] = $this->attachmentsPropertySchema();
            } else {
                unset($def['secret']['properties']['attachments']);
            }
            $json = json_encode($def);
            // Use the query builder (parameterized); the Phinx adapter has no
            // quoteValue() and matches how core Passbolt migrations update rows.
            $this->getUpdateBuilder()
                ->update('resource_types')
                ->set('definition', $json)
                ->where(['id' => $row['id']])
                ->execute();
        }
    }
}
