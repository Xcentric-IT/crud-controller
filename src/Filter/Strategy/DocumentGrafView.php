<?php

namespace XcentricItFoundation\LaravelCrudController\Filter\Strategy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Common\Models\DocumentGraf;
use Spatie\QueryBuilder\Filters\Filter;

class DocumentGrafView implements Filter
{
    protected string $type = 'full';

    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $model = $query->getModel();

        $this->type = in_array($property, ['parents', 'children', 'full']) ? $property : 'full';

        if (is_array($value)) {
            throw new \Exception('Value can not be array');
        }

        $source = $model->newQuery()->find($value);

        $items = collect([]);

        switch ($this->type) {
            case 'parents':
                $items = $this->getParents($source);
                break;
            case 'children':
                $items = $this->getChildren($source);
                break;
            default:
                $items = $this->getParents($source);
                $items = $this->getChildren($source, $items);
        }

        $query->whereIn('id', $items->pluck(['id']));
    }

    private function getParents(Model $source, ?Collection $parents = null): Collection
    {
        $parents = $parents ?? collect([]);
        foreach ($source->parents as $aParent) {
            $parents->add($aParent);
            $this->getParents($aParent, $parents);
        }
        return $parents;
    }

    private function getChildren(Model $source, ?Collection $children = null): Collection
    {
        $children = $children ?? collect([]);
        foreach ($source->children as $child) {
            $children->add($child);
            $this->getChildren($child, $children);
        }
        return $children;
    }
}
