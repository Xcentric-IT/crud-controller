<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions\Crud;

use Illuminate\Database\Eloquent\Model;
use XcentricItFoundation\LaravelCrudController\Actions\ActionPayloadInterface;

class CrudActionPayload implements ActionPayloadInterface
{
    protected array $data = [];

    public function __construct(public array $modelData, public Model $model)
    {
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }
}
