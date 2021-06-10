<?php

namespace XcentricItFoundation\LaravelCrudController;


use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
        [$data, $relations] = $this->parseRelationships($model, $data);
        $this->beforeCreate($model);
        $model->fill($data)->save();
        $this->fillRelationships($model, $relations);
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
        $this->beforeUpdate($model);
        [$data, $relations] = $this->parseRelationships($model, $data);
        $model->fill($data)->save();
        $this->fillRelationships($model, $relations);
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

    private function parseRelationships(Model $model, array $data)
    {
        $parsedData = [];
        $parsedRelationData = [];
        foreach ($data as $key => $item) {
            if ($model->isRelation($key)) {
                if ($model->isFillable($key)) {
                    $parsedRelationData[$key] = $item;
                } elseif ($model->isFillable($key . '_id')) {
                    $parsedData[$key . '_id'] = array_key_exists('id', $item) ? $item['id'] : null;;
                }
            } else {
                $parsedData[$key] = $item;
            }
        }
        return [$parsedData, $parsedRelationData];
    }

    private function fillRelationships(Model $model, array $data): void
    {
        foreach ($data as $key => $item) {
            if ($model->isRelation($key) && $model->isFillable($key)) {
                $relationship_type = get_class($model->$key());
                switch ($relationship_type) {
                    case BelongsToMany::class:
                    case MorphMany::class:
                    case HasMany::class:
                        $this->syncHasManyRelationship($model, $key, $item);
                        break;
                    case BelongsTo::class:
                        $this->syncBelongsToRelationship($model, $key, $item);
                        break;
                    default:
                        break;
                }
            }
        }
    }

    /**
     * @param Model $model
     * @param $relationship_name
     * @param array $data
     */
    private function syncHasManyRelationship(Model $model, $relationship_name, array $data)
    {
        $present_ids = [];
        foreach ($data as $related) {
            $present_ids[] = array_key_exists('id', $related) ? $related['id'] : null;
        }

        $model->$relationship_name()->syncWithPivotValues($present_ids, ['created_at'=>new \DateTime()]);
    }

    /**
     * @param Model  $model
     * @param string $relationship_name
     * @param array  $data
     * @return mixed
     */
    private function syncBelongsToRelationship(Model $model, $relationship_name, array $data)
    {
        $present_id = array_key_exists('id', $data) ? $data['id'] : null;
        return $model->$relationship_name()->associate($present_id);
    }
}
