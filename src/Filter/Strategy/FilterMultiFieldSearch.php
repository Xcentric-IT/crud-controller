<?php

namespace XcentricItFoundation\LaravelCrudController\Filter\Strategy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\Filters\Filter;

class FilterMultiFieldSearch implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        if (str_contains($property, ':')) {
            $property = explode(':', $property)[1];
        }

        $query->where(function(Builder $subQuery) use ($value, $property) {
            $properties = explode(',', $property);
            foreach ($properties as $aProperty) {
                $subQuery->orWhere($aProperty, 'LIKE', '%' . $value . '%');
            }

            if(str_contains($value, ' ')){
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
}
