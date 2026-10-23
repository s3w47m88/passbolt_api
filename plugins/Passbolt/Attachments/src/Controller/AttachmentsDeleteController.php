<?php
declare(strict_types=1);

namespace Passbolt\Attachments\Controller;

use App\Controller\AppController;
use App\Model\Entity\Permission;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
use Cake\Validation\Validation;
use Passbolt\Attachments\Controller\Traits\AttachmentsAccessTrait;

class AttachmentsDeleteController extends AppController
{
    use AttachmentsAccessTrait;

    /**
     * Hard-delete a ciphertext blob. Requires update access on the resource.
     * The caller is expected to also remove the matching entry from the
     * resource secret's `attachments[]` via a normal resource update.
     *
     * @param string $resourceId Resource UUID.
     * @param string $attachmentId Attachment UUID.
     * @return void
     */
    public function delete(string $resourceId, string $attachmentId): void
    {
        $this->assertJson();
        $this->assertResourceAccess($resourceId, Permission::UPDATE);
        if (!Validation::uuid($attachmentId)) {
            throw new BadRequestException(__('The attachment id is not valid.'));
        }

        /** @var \Passbolt\Attachments\Model\Table\AttachmentsTable $Attachments */
        $Attachments = $this->fetchTable('Passbolt/Attachments.Attachments');
        $attachment = $Attachments->find()
            ->where(['id' => $attachmentId, 'resource_id' => $resourceId])
            ->first();

        if (empty($attachment)) {
            throw new NotFoundException(__('The attachment does not exist.'));
        }

        $Attachments->deleteOrFail($attachment);
        $this->success(__('The operation was successful.'));
    }
}
