<?php
declare(strict_types=1);

namespace Passbolt\Attachments\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Cake\Core\Configure;

/**
 * Attachments model. Rows hold AES-256-GCM ciphertext (`data`) linked to a
 * resource; the decryption key lives in the resource's OpenPGP secret, never here.
 *
 * @property \App\Model\Table\ResourcesTable&\Cake\ORM\Association\BelongsTo $Resources
 */
class AttachmentsTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('attachments');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('Resources', [
            'className' => 'Resources',
            'foreignKey' => 'resource_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * @inheritDoc
     */
    public function validationDefault(Validator $validator): Validator
    {
        $max = (int)Configure::read('passbolt.plugins.attachments.maxFileSize', 26214400);

        $validator
            ->uuid('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->uuid('resource_id')
            ->notEmptyString('resource_id');

        $validator
            ->utf8Extended('filename')
            ->maxLength('filename', 255)
            ->notEmptyString('filename');

        $validator
            ->maxLength('mime', 255)
            ->allowEmptyString('mime');

        $validator
            ->nonNegativeInteger('size')
            ->add('size', 'maxFileSize', [
                'rule' => fn ($value) => (int)$value <= $max,
                'message' => __('The attachment exceeds the maximum allowed size.'),
            ]);

        $validator
            ->requirePresence('data', 'create')
            ->add('data', 'maxFileSize', [
                'rule' => fn ($value) => is_string($value) && strlen($value) <= $max + 4096,
                'message' => __('The attachment ciphertext exceeds the maximum allowed size.'),
            ]);

        return $validator;
    }

    /**
     * Enforce the FK to an existing resource at the DB-rule level.
     *
     * @inheritDoc
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['resource_id'], 'Resources'), 'resource_exists', [
            'errorField' => 'resource_id',
            'message' => __('The resource does not exist.'),
        ]);

        return $rules;
    }
}
