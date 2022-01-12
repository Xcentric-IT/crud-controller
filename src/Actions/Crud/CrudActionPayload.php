<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions\Crud;

use App\Actions\ActionPayloadInterface;
use Illuminate\Database\Eloquent\Model;

class CrudActionPayload implements ActionPayloadInterface
{
    /**
     * @param array $data
     * @param Model $model
     */
    public function __construct(public array $data, public Model $model)
    {
    }
}
