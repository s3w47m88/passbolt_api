<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Create the `attachments` table holding AES-256-GCM ciphertext blobs linked to
 * resources. The decryption key lives in the resource's OpenPGP secret, never here.
 */
class V5162AddAttachmentsTable extends AbstractMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('attachments', [
            'id' => false,
            'primary_key' => ['id'],
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'uuid', ['null' => false])
            ->addColumn('resource_id', 'uuid', ['null' => false])
            ->addColumn('filename', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('mime', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('size', 'biginteger', ['null' => false, 'default' => 0])
            // MySQL LONGBLOB (up to 4 GB); our max ciphertext is ~25 MB.
            ->addColumn('data', 'blob', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::BLOB_LONG, 'null' => false])
            ->addColumn('created_by', 'uuid', ['null' => true, 'default' => null])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['resource_id'])
            ->create();
    }
}
