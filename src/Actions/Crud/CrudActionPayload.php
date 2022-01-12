<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions\Crud;

use Illuminate\Database\Eloquent\Model;
use XcentricItFoundation\LaravelCrudController\Actions\ActionPayloadInterface;

class CrudActionPayload implements ActionPayloadInterface
{
    public function __construct(public array $data, public Model $model)
    {
    }
}
