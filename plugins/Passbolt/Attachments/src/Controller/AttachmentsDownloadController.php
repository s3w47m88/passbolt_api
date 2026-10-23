<?php
declare(strict_types=1);

namespace Passbolt\Attachments\Controller;

use App\Controller\AppController;
use App\Model\Entity\Permission;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Validation\Validation;
use Passbolt\Attachments\Controller\Traits\AttachmentsAccessTrait;

class AttachmentsDownloadController extends AppController
{
    use AttachmentsAccessTrait;

    /**
     * Stream a ciphertext blob (raw bytes, application/octet-stream — NOT the
     * JSON envelope). The client decrypts it with the AES key from the resource
     * secret. Requires read access on the resource.
     *
     * @param string $resourceId Resource UUID.
     * @param string $attachmentId Attachment UUID.
     * @return \Cake\Http\Response
     */
    public function download(string $resourceId, string $attachmentId): Response
    {
        $this->assertResourceAccess($resourceId, Permission::READ);
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

        $bytes = $attachment->get('data');
        if (is_resource($bytes)) {
            $bytes = stream_get_contents($bytes);
        }

        // Bypass the JSON view: return opaque ciphertext. The real filename is
        // inside the encrypted secret, so we expose only the id here.
        return $this->response
            ->withType('application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $attachmentId . '.enc"')
            ->withHeader('Cache-Control', 'no-store')
            ->withStringBody((string)$bytes);
    }
}
