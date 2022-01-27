<?php

namespace XcentricItFoundation\LaravelCrudController\Tests\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class EntityField
 *
 * @property string $name
 * @property string $entity_id
 * @property-read Entity $entity
 */
class EntityField extends AbstractModel
{
    protected $table = 'entity_fields';

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }
}
