<?php

namespace XcentricItFoundation\LaravelCrudController\Tests\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Entity
 *
 * @property string $name
 * @property string $parent_class_id
 * @property string $module_id
 * @property-read Entity $parent_class
 * @property-read Module $module
 * @property-read Collection<EntityField> $fields
 * @property-read Collection<EntityInterface> $interfaces
 */
class Entity extends AbstractModel
{
    protected $table = 'entities';

    public function parent_class(): BelongsTo
    {
        return $this->belongsTo(__CLASS__, 'parent_class_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(EntityField::class);
    }

    public function interfaces(): BelongsToMany
    {
        return $this->belongsToMany(EntityInterface::class, 'entities_entity_interfaces');
    }
}
