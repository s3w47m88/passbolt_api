<?php
declare(strict_types=1);

namespace Passbolt\Attachments\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property string $id
 * @property string $resource_id
 * @property string $filename
 * @property string|null $mime
 * @property int $size
 * @property resource|string $data  AES-256-GCM ciphertext, opaque to the server
 * @property string|null $created_by
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime $modified
 */
class Attachment extends Entity
{
    /**
     * Mass-assignable fields. `data` is set explicitly from the uploaded stream,
     * never from request JSON.
     *
     * @var array<string, bool>
     */
    protected $_accessible = [
        'resource_id' => true,
        'filename' => true,
        'mime' => true,
        'size' => true,
        'data' => true,
        'created_by' => true,
    ];

    /**
     * Never expose the raw ciphertext blob in JSON listings.
     *
     * @var array<string>
     */
    protected $_hidden = [
        'data',
    ];
}
