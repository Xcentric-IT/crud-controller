<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions\Crud;

use Illuminate\Database\Eloquent\Model;
use XcentricItFoundation\LaravelCrudController\Actions\ActionPayloadInterface;

class CrudActionPayload implements ActionPayloadInterface
{
    protected array $additionalData = [];

    public function __construct(
        protected array $data,
        protected Model $model,
        protected array $originalData = [],
    ) {
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getOriginalData(): array
    {
        return $this->originalData;
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    public function getAdditionalData(): array
    {
        return $this->additionalData;
    }

    public function setAdditionalData(array $additionalData): CrudActionPayload
    {
        $this->additionalData = $additionalData;
        return $this;
    }
}
