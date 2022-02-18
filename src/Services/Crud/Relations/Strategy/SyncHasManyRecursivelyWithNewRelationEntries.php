<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy;

class SyncHasManyRecursivelyWithNewRelationEntries extends SyncHasManyRecursively
{
    protected function prepareRelationData(array $data): array
    {
        unset($data['id']);
        return $data;
    }
}
