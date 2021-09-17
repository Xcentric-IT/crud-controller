<?php

namespace XcentricItFoundation\LaravelCrudController;

use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedSort;
use XcentricItFoundation\LaravelCrudController\Filter\LaravelCrudFilter;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use XcentricItFoundation\LaravelCrudController\Sort\SortByRelationField;

trait ParsesQuery
{
    /**
     * @param Request $request
     * @param string $model
     * @return QueryBuilder
     */
    public function parseRequest(Request $request, string $model): QueryBuilder
    {
        $request = $this->prepareRequest($request);
        $query = QueryBuilder::for($model, $request);
        $this->parseFilters($request, $query)
            ->parseSorts($request, $query)
            ->parseRelationships($request, $query);

        return $query;
    }

    /**
     * @param Request $request
     * @param QueryBuilder $queryBuilder
     * @return $this
     */
    public function parseFilters(Request $request, QueryBuilder $queryBuilder): self
    {
        $filterService = new LaravelCrudFilter();
        $filterService->parseFilters($request, $queryBuilder);

        return $this;
    }

    /**
     * @param Request $request
     * @param QueryBuilder $queryBuilder
     * @return $this
     */
    public function parseRelationships(Request $request, QueryBuilder $queryBuilder): self
    {
        $includes = explode(',', $request->input('include', ''));

        $queryBuilder->allowedIncludes($includes);

        return $this;
    }

    public function parseSorts(Request $request, QueryBuilder $queryBuilder): self
    {
        $sorts = explode(',', $request->input('sort', ''));

        $queryBuilder->allowedSorts(
            collect($sorts)->filter(function ($sort) {
                return !empty($sort);
            })->map(function ($sort) {
                return $this->getSortMapping(trim($sort));
            })->toArray()
        );

        return $this;
    }

    protected function getSortMapping(string $sort): string | AllowedSort
    {
        return Str::contains($sort, '.') ? AllowedSort::custom($sort, new SortByRelationField(), $sort) : $sort;
    }

    /**
     * @param Request $request
     * @return Request
     */
    protected function prepareRequest(Request $request): Request
    {
        $filters = $request->input('filter', []);

        $request->query->set('filter', $filters);
        return $request;
    }
}
