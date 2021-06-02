<?php

namespace XcentricItFoundation\LaravelCrudController;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

/**
 * Class AbstractResource
 * @package App\Http\Resources
 */
class BaseResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function with($request)
    {
        $with = parent::with($request);

        $includeRelations = $request->query('include', '');

        if ($includeRelations !== '') {
            foreach (Arr::wrap(explode(',', $includeRelations)) as $include) {
                $with[] = $include;
            }
        }

        return $with;
    }
}
