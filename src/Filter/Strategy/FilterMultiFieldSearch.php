<?php

namespace XcentricItFoundation\LaravelCrudController\Filter\Strategy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Filters\FiltersExact;

class FilterMultiFieldSearch extends FiltersExact
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        if (Str::contains($property, ':')) {
            $property = explode(':', $property)[1];
        }

        $query->where(function(Builder $subQuery) use ($value, $property) {
            $properties = explode(',', $property);
            foreach ($properties as $aProperty) {
                if ($this->addRelationConstraint && $this->isRelationProperty($subQuery, $aProperty)) {
                    $this->withRelationConstraint($subQuery, $value, $aProperty);

                    return;
                }

                $wrappedProperty = $subQuery->qualifyColumn($aProperty);

                $subQuery->orWhere($wrappedProperty, 'LIKE', '%' . $value . '%');
            }

            if(Str::contains($value, ' ')){
                $combinations = $this->combinations($properties);
                foreach ($combinations as $combination){
                    $subQuery->orWhereRaw(sprintf('CONCAT_WS(\' \', %s)', implode(', ', $combination)) . ' LIKE ' . sprintf('\'%%%s%%\'', $value));
                }
            }
        });
    }

    protected function combinations(array $array): array
    {
        if (count($array) === 2) {
            return [
                [$array[0], $array[1]],
                [$array[1], $array[0]]
            ];
        }
        if (count($array) > 2) {
            $newArray = [];
            foreach ($array as $index => $item){
                $tempArray = $array;
                unset($tempArray[$index]);

                $combinations = $this->combinations(array_values($tempArray));
                foreach ($combinations as &$combination){
                    array_unshift($combination, $item);
                }
                $newArray = array_merge($newArray, $combinations);
            }
            return $newArray;
        }
        return $array;
    }

    protected function withRelationConstraint(Builder $query, mixed $value, string $property): void
    {
        [$relation, $property] = collect(explode('.', $property))
            ->pipe(function (Collection $parts) {
                return [
                    $parts->except(count($parts) - 1)->implode('.'),
                    $parts->last(),
                ];
            });

        $query->orWhereHas($relation, function (Builder $query) use ($value, $property) {
            $this->relationConstraints[] = $property = $query->qualifyColumn($property);

            $this->__invoke($query, $value, $property);
        });
    }
}
