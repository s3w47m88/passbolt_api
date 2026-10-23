<?php
declare(strict_types=1);

namespace Passbolt\Attachments\Controller\Traits;

use App\Model\Entity\Permission;
use App\Model\Table\PermissionsTable;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Validation\Validation;

/**
 * Shared authorization helpers for the attachment controllers. Mirrors the
 * core ResourcesUpdateService pattern: 404 when the actor has no read access
 * (hide existence), 403 when below the required permission level.
 */
trait AttachmentsAccessTrait
{
    /**
     * Assert the current user has at least $permissionType on $resourceId.
     * Returns the resource id on success.
     *
     * @param string $resourceId Resource UUID.
     * @param int $permissionType Permission::READ|UPDATE|OWNER.
     * @return string
     */
    protected function assertResourceAccess(string $resourceId, int $permissionType): string
    {
        if (!Validation::uuid($resourceId)) {
            throw new BadRequestException(__('The resource id is not valid.'));
        }

        /** @var \App\Model\Table\PermissionsTable $permissions */
        $permissions = $this->fetchTable('Permissions');
        $uac = $this->User->getAccessControl();
        $permission = $permissions
            ->findHighestByAcoAndAro(PermissionsTable::RESOURCE_ACO, $resourceId, $uac->getId())
            ->first();

        if (empty($permission)) {
            throw new NotFoundException(__('The resource does not exist.'));
        }
        if ((int)$permission->get('type') < $permissionType) {
            throw new ForbiddenException(__('You are not allowed to access this resource.'));
        }

        return $resourceId;
    }
}
