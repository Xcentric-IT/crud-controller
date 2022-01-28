<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions\Crud;

use XcentricItFoundation\LaravelCrudController\Actions\ExecutableAction;
use XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\EntityRelationsService;

abstract class CrudAction implements ExecutableAction
{
    public function __construct(
        protected EntityRelationsService $entityRelationService,
    ) {
    }
}
