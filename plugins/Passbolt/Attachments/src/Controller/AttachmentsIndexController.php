<?php
declare(strict_types=1);

namespace Passbolt\Attachments\Controller;

use App\Controller\AppController;
use App\Model\Entity\Permission;
use Passbolt\Attachments\Controller\Traits\AttachmentsAccessTrait;

class AttachmentsIndexController extends AppController
{
    use AttachmentsAccessTrait;

    /**
     * List attachment metadata (no ciphertext) for a resource.
     * Requires read access on the resource.
     *
     * @param string $resourceId Resource UUID.
     * @return void
     */
    public function index(string $resourceId): void
    {
        $this->assertJson();
        $this->assertResourceAccess($resourceId, Permission::READ);

        /** @var \Passbolt\Attachments\Model\Table\AttachmentsTable $Attachments */
        $Attachments = $this->fetchTable('Passbolt/Attachments.Attachments');
        $attachments = $Attachments->find()
            ->where(['resource_id' => $resourceId])
            ->select(['id', 'resource_id', 'filename', 'mime', 'size', 'created', 'modified', 'created_by'])
            ->orderAsc('created')
            ->all();

        $this->success(__('The operation was successful.'), $attachments);
    }
}
