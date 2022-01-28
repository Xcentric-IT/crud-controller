<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services\Crud\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PivotDataService
{
    public function cleanup(BelongsToMany $relation, array $data): array
    {
        $pivotData = [];

        foreach ($data as $key => $value) {
            if (
                in_array($key, $relation->getPivotColumns(), true)
                && $key !== $relation->getParent()->getCreatedAtColumn()
                && $key !== $relation->getParent()->getUpdatedAtColumn()
            ) {
                $pivotData[$key] = $value;
            }
        }

        return $pivotData;
    }
}
