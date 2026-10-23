<?php
declare(strict_types=1);

/**
 * Passbolt Attachments — end-to-end-encrypted file attachments on resources.
 *
 * Out-of-tree CakePHP plugin (does NOT edit Passbolt core). The server stores
 * only AES-256-GCM ciphertext blobs, opaque to it; the per-file data key lives
 * inside each resource's existing OpenPGP-encrypted secret (see the `attachments`
 * array added to the v5 secret schema), so sharing/revocation apply for free.
 *
 * Mirrors the Passbolt/Folders plugin skeleton. Loaded via
 * $app->addPlugin('Passbolt/Attachments', ['bootstrap' => true, 'routes' => true]).
 */

namespace Passbolt\Attachments;

use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;

class AttachmentsPlugin extends BasePlugin
{
    /**
     * @inheritDoc
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);
    }
}
