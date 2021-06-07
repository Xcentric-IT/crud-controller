<?php

namespace XcentricItFoundation\LaravelCrudController;


use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller as BaseController;

class LaravelCrudController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, ParsesQuery, CrudCallbacks;

    public const HTTP_STATUS_EMPTY = 204;

    public const PER_PAGE = 20;

    protected Request $request;

    /**
     * Controller constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getRequestValidator(): LaravelCrudRequest
    {
        $controllerClass = ModelHelper::getRequestValidatorNamespace($this->request->route('model'), $this->request->route('namespace'));

        if (class_exists($controllerClass)) {
            return resolve($controllerClass);
        }

        return resolve(LaravelCrudRequest::class);
    }

    public function getModelName(): string
    {
        return ModelHelper::getModelNamespace($this->request->route('model'), $this->request->route('namespace'));
    }

    public function readOne(string $id): JsonResource
    {
        return $this->createResource(
            $this->parseRequest($this->request, $this->getModelName())->find($id)
        );
    }

    public function readMore(): JsonResource
    {
        return $this->createResourceCollection(
            $this->parseRequest($this->request, $this->getModelName())
                ->paginate($this->perPage())
        );
    }

    /**
     * @return JsonResource
     */
    public function create(): JsonResource
    {
        $data = $this->request->all();
        $this->getRequestValidator()->validate();
        $model = $this->createModel();
        $data = $this->resolveRelationFields($model, $data);
        $this->beforeCreate($model);
        $model->fill($data)->save();
        $this->afterCreate($model);

        return $this->createResource($model);
    }

    /**
     * @param string $id
     * @return JsonResource
     */
    public function update(string $id): JsonResource
    {
        $data = $this->request->all();
        $this->getRequestValidator()->validate();

        $model = $this->createNewModelQuery()->find($id);
        $data = $this->resolveRelationFields($model, $data);
        $this->beforeUpdate($model);
        $model->fill($data)->save();
        $this->afterUpdate($model);
        return $this->createResource($model);
    }

    /**
     * @param string $id
     * @return JsonResponse
     * @throws \Exception
     */
    public function delete(string $id): JsonResponse
    {
        tap($this->createNewModelQuery()->find($id), function (Model $model): void {
            $this->beforeDelete($model);
            $model->delete();
            $this->afterDelete($model);
        });
        return $this->returnNoContent();
    }

    /**
     * @return JsonResponse
     */
    public function returnNoContent(): JsonResponse
    {
        return response()->json(null, self::HTTP_STATUS_EMPTY);
    }

    protected function createNewModelQuery(): Builder
    {
        return resolve($this->getModelName())->newQuery();
    }

    protected function createModel(): Model
    {
        return resolve($this->getModelName());
    }

    protected function perPage(): int
    {
        return $this->request->query->has('per_page') ? $this->request->query->getInt('per_page') : self::PER_PAGE;
    }

    /**
     * @param Collection<Model>|Model|LengthAwarePaginator|null $resource
     * @return BaseResource
     */
    protected function createResource($resource): BaseResource
    {
        return new BaseResource($resource);
    }

    /**
     * @param Collection|LengthAwarePaginator|null $resource
     * @return AnonymousResourceCollection
     */
    protected function createResourceCollection($resource): AnonymousResourceCollection
    {
        return BaseResource::collection($resource);
    }

    /**
     * @param Model $model
     * @param array $data
     * @return array
     */
    private function resolveRelationFields(Model $model, array $data): array
    {
        $parsedData = [];

        foreach ($data as $key => $item) {
            if (is_array($item)) {
                $relationField = "{$key}_id";

                if ($model->isFillable($relationField)) {
                    $key = $relationField;
                    $item = $item['id'] ?? null;
                }
            }

            $parsedData[$key] = $item;
        }

        return $parsedData;
    }
}
