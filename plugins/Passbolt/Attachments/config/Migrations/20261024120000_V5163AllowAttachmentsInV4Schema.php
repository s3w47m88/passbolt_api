<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Add the optional `attachments` array to the SECRET schema of the v4
 * password-bearing resource types. This instance stores every resource as the
 * v4 "password-and-description" type (not v5), so the attachments feature must
 * validate against the v4 secret schemas, not only the v5 ones patched by
 * V5162AllowAttachmentsInSecretSchema.
 *
 * Additive and backward-compatible: stock clients ignore the extra key and it
 * is never required. Rewrites the `definition` JSON of the affected
 * resource_types rows (no core fork).
 */
class V5163AllowAttachmentsInV4Schema extends AbstractMigration
{
    private const SLUGS = ['password-and-description', 'password-description-totp', 'password-string'];

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
            $this->getUpdateBuilder()
                ->update('resource_types')
                ->set('definition', $json)
                ->where(['id' => $row['id']])
                ->execute();
        }
    }
}
