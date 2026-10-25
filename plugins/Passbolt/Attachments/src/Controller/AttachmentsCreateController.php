<?php
declare(strict_types=1);

namespace Passbolt\Attachments\Controller;

use App\Controller\AppController;
use App\Model\Entity\Permission;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\InternalErrorException;
use Passbolt\Attachments\Controller\Traits\AttachmentsAccessTrait;
use Psr\Http\Message\UploadedFileInterface;

class AttachmentsCreateController extends AppController
{
    use AttachmentsAccessTrait;

    /**
     * Upload a ciphertext blob for a resource (multipart, field `data`).
     * The bytes are already AES-256-GCM encrypted client-side; the server
     * stores them opaquely. Requires update access on the resource.
     *
     * Metadata fields accepted alongside the file: `filename`, `mime`, `size`,
     * and `id` (the client-chosen attachment UUID that also appears in the
     * resource secret's `attachments[]` entry, so the two stay linked).
     *
     * @param string $resourceId Resource UUID.
     * @return void
     */
    public function create(string $resourceId): void
    {
        $this->assertJson();
        $this->assertResourceAccess($resourceId, Permission::UPDATE);

        $file = $this->request->getData('data');
        if (!($file instanceof UploadedFileInterface)) {
            throw new BadRequestException(__('A ciphertext file is required in the `data` field.'));
        }
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new BadRequestException(
                __('The attachment upload failed (php upload error {0}).', (string)$file->getError())
            );
        }

        $ciphertext = $file->getStream()->getContents();
        if ($ciphertext === '') {
            throw new BadRequestException(__('The attachment is empty.'));
        }

        $uac = $this->User->getAccessControl();
        /** @var \Passbolt\Attachments\Model\Table\AttachmentsTable $Attachments */
        $Attachments = $this->fetchTable('Passbolt/Attachments.Attachments');

        $data = [
            'resource_id' => $resourceId,
            'filename' => (string)$this->request->getData('filename', $file->getClientFilename() ?? 'attachment'),
            'mime' => $this->request->getData('mime', $file->getClientMediaType()),
            'size' => (int)$this->request->getData('size', $file->getSize() ?? strlen($ciphertext)),
            'data' => $ciphertext,
            'created_by' => $uac->getId(),
        ];

        $entity = $Attachments->newEntity($data, [
            'accessibleFields' => [
                'resource_id' => true, 'filename' => true, 'mime' => true,
                'size' => true, 'data' => true, 'created_by' => true,
            ],
        ]);

        // Honour the client-chosen UUID so it matches the secret's attachments[] id.
        $clientId = $this->request->getData('id');
        if (is_string($clientId) && $clientId !== '') {
            $entity->set('id', $clientId);
            $entity->setDirty('id', true);
        }

        if ($entity->getErrors()) {
            throw new BadRequestException(__('The attachment is not valid.'));
        }
        if (!$Attachments->save($entity)) {
            if ($entity->getErrors()) {
                throw new BadRequestException(__('The attachment could not be saved.'));
            }
            throw new InternalErrorException(__('The attachment could not be saved.'));
        }

        $entity->setHidden(['data']);
        $this->success(__('The operation was successful.'), $entity);
    }
}
