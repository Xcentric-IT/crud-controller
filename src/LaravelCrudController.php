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
use Illuminate\Support\Str;

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
        [$data, $relations] = $this->resolveRelationFields($model, $data);
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
        [$data, $relations] = $this->resolveRelationFields($model, $data);
        $model->fill($data)->save();
        $this->fillRelationships($model, $relations);
        $this->afterUpdate($model);
        return $this->createResource($model);
    }

    /**
     * @param string $id
     * @return JsonResource
     */
    public function addRemoveRelation(string $id, string $relationField, string $relationId = null, bool $add = true): JsonResource
    {
        $data = $relationId !== null ? ['id' => $relationId] : $this->request->all();
        $this->getRequestValidator()->validate();

        $model = $this->createNewModelQuery()->find($id);
        $this->beforeUpdate($model);
        $this->addRemoveRelationships($model, $relationField, $data, $add);
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

    private function resolveRelationFields(Model $model, array $data)
    {
        $parsedData = [];
        $parsedRelationData = [];
        foreach ($data as $key => $item) {
            $key_camel = Str::camel($key);
            if ($model->isRelation($key_camel)) {
                if ($model->isFillable($key) || $model->isFillable($key_camel)) {
                    $parsedRelationData[$key] = $item;
                } elseif ($model->isFillable($key . '_id')) {
                    $parsedData[$key . '_id'] = (!empty($item)  && is_array($item) && array_key_exists('id', $item)) ? $item['id'] : $item;;
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
            $key_camel = Str::camel($key);
            if ($model->isRelation($key_camel) && ($model->isFillable($key) || $model->isFillable($key_camel))) {
                $relationship_type = get_class($model->$key_camel());
                switch ($relationship_type) {
                    case BelongsToMany::class:
                    case MorphToMany::class:
                        $this->syncBelongsToManyRelationship($model, $key_camel, $item);
                        break;
                    case MorphMany::class:
                    case HasMany::class:
                        $this->syncHasManyRelationship($model, $key_camel, $item);
                        break;
                    case BelongsTo::class:
                        $this->syncBelongsToRelationship($model, $key_camel, $item);
                        break;
                    default:
                        break;
                }
            }
        }
    }

    private function addRemoveRelationships(Model $model, string $relationField, array $item, bool $add = true): void
    {
        $key_camel = Str::camel($relationField);
        if ($model->isRelation($key_camel) && ($model->isFillable($relationField) || $model->isFillable($key_camel))) {
            $relationship_type = get_class($model->$key_camel());
            switch ($relationship_type) {
                case BelongsToMany::class:
                    $this->appendDetachBelongsToManyRelationship($model, $key_camel, $item, $add);
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * @param Model $model
     * @param $relationship_name
     * @param array $data
     */
    private function syncBelongsToManyRelationship(Model $model, $relationship_name, array $data)
    {
        $present_ids = [];
        foreach ($data as $related) {
            $id = array_key_exists('id', $related) ? $related['id'] : null;
            $relation = $model->$relationship_name();
            $present_ids[$id] = $this->getPivotColumnData($relation, $related['pivot'] ?? []);

            if (isset($related['DIRTY'])) {
                /** @var Model $subModel */
                $subModel = $relation->getRelated();
                $subModel = $subModel->newModelQuery()->find($related['id']) ?? $subModel;
                $subModel->fill($related)->save();
            }
        }

        $model->$relationship_name()->sync($present_ids);
    }

    /**
     * @param Model $model
     * @param $relationship_name
     * @param array $data
     */
    private function appendDetachBelongsToManyRelationship(Model $model, $relationship_name, array $data, bool $append = true)
    {
        $present_ids = [];
        $id = array_key_exists('id', $data) ? $data['id'] : null;
        $relation = $model->$relationship_name();
        $present_ids[$id] = $append ? $this->getPivotColumnData($relation, $data['pivot'] ?? []) : $id;

        if (isset($data['DIRTY'])) {
            /** @var Model $subModel */
            $subModel = $relation->getRelated();
            $subModel = $subModel->newModelQuery()->find($data['id']) ?? $subModel;
            $subModel->fill($data)->save();
        }

        if ($append) {
            $model->$relationship_name()->syncWithoutDetaching($present_ids);
        } else {
            $model->$relationship_name()->detach($present_ids);
        }
    }

    /**
     * @param Model $model
     * @param $relationship_name
     * @param array $data
     */
    private function syncHasManyRelationship(Model $model, $relationship_name, array $data)
    {
        $unSyncedSubModels = $model->$relationship_name()->pluck('id')->all();
        foreach ($data as $related) {
            $id = array_key_exists('id', $related) ? $related['id'] : null;
            if (isset($related['DIRTY'])) {
                /** @var Model $subModel */
                $subModel = $model->$relationship_name()->getRelated();
                $subModel = $subModel->newModelQuery()->find($id);
                if ($subModel) {
                    $subModel->fill($related)->save();
                    $model->$relationship_name()->save($subModel);
                } else {
                    $subModel = $model->$relationship_name()->create($related);
                }
            } else {
                /** @var Model $subModel */
                $subModel = $model->$relationship_name()->getRelated();
                $subModel = $subModel->newModelQuery()->find($id);
                $model->tasks()->save($subModel);
            }

            if (($index = array_search($subModel->id, $unSyncedSubModels)) !== false) {
                unset($unSyncedSubModels[$index]);
            }
        }

        foreach ($unSyncedSubModels as $unSyncedSubModel) {
            $model->$relationship_name()->where('id', '=', $unSyncedSubModel)->delete();
        }
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

    /**
     * @param BelongsToMany $relation
     * @param array $data
     * @return array
     */
    private function getPivotColumnData(BelongsToMany $relation, array $data): array
    {
        $pivotData = [];
        foreach ($data as $key => $value) {
            if (
                in_array($key, $relation->getPivotColumns())
                && $key !== $relation->getParent()->getCreatedAtColumn()
                && $key !== $relation->getParent()->getUpdatedAtColumn()
            ) {
                $pivotData[$key] = $value;
            }
        }
        return $pivotData;
    }
}
