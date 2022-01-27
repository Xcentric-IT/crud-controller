<?php

namespace XcentricItFoundation\LaravelCrudController\Tests\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class EntityInterface
 *
 * @property string $name
 * @property string $fqn
 * @property-read Collection<Entity> $entities
 */
class EntityInterface extends AbstractModel
{
    protected $table = 'entity_interfaces';

    public function entities(): BelongsToMany
    {
        return $this->belongsToMany(Entity::class, 'entities_entity_interfaces');
    }
}
