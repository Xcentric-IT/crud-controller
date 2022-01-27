<?php

namespace XcentricItFoundation\LaravelCrudController\Tests\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Module
 *
 * @property string $name
 * @property string $slug
 */
class Module extends AbstractModel
{
    protected $table = 'modules';

    public function entity(): HasMany
    {
        return $this->hasMany(Entity::class);
    }
}
