<?php

namespace XcentricItFoundation\LaravelCrudController\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Class AbstractModel
 *
 * @property string $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
abstract class AbstractModel extends Model
{
    use HasFactory;

    protected $keyType = 'uuid';

    public $incrementing = false;

    protected $guarded = [
        'created_at',
        'updated_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = (string)Str::uuid();
            }
        });
    }
}
