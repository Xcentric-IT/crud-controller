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
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Str;
use XcentricItFoundation\LaravelCrudController\Actions\Crud\Create;
use XcentricItFoundation\LaravelCrudController\Actions\Crud\CrudActionPayload;
use XcentricItFoundation\LaravelCrudController\Actions\Crud\Delete;
use XcentricItFoundation\LaravelCrudController\Actions\Crud\Update;
use XcentricItFoundation\LaravelCrudController\Actions\ExecutableAction;

class LaravelCrudController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, ParsesQuery, CrudCallbacks;

    public const HTTP_STATUS_EMPTY = 204;

    public const PER_PAGE = 20;

    public function __construct(
        protected Request $request
    ) {
    }

    public function getRequestValidator(): LaravelCrudRequest
    {
        $requestClass = ModelHelper::getRequestValidatorNamespace($this->request->route()->getAction('model'), $this->request->route()->getAction('namespace'));

        if (class_exists($requestClass)) {
            return resolve($requestClass);
        }

        return resolve(LaravelCrudRequest::class);
    }

    public function getModelName(): string
    {
        return ModelHelper::getModelNamespace($this->request->route()->getAction('model'), $this->request->route()->getAction('namespace'));
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
            $this
                ->parseRequest($this->request, $this->getModelName())
                ->paginate($this->perPage())
        );
    }

    public function create(): JsonResource
    {
        $data = $this->validateAndPrepareData();

        $model = $this->createModel();

        $this->beforeCreate($model);

        $this->getCreateAction()->run(new CrudActionPayload($data, $model));

        $this->afterCreate($model);
        $model->fresh();

        return $this->createResource($model);
    }

    public function update(string $id): JsonResource
    {
        $data = $this->validateAndPrepareData();

        $model = $this->createNewModelQuery()->find($id);

        $this->beforeUpdate($model);

        $this->getUpdateAction()->run(new CrudActionPayload($data, $model));

        $this->afterUpdate($model);
        return $this->createResource($model);
    }

    public function delete(string $id): JsonResponse
    {
        tap($this->createNewModelQuery()->find($id), function (Model $model): void {
            $this->beforeDelete($model);

            $this->getDeleteAction()->run(new CrudActionPayload([], $model));

            $this->afterDelete($model);
        });

        return $this->returnNoContent();
    }

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

    protected function getCreateAction(): ExecutableAction
    {
        return new Create();
    }

    protected function getUpdateAction(): ExecutableAction
    {
        return new Update();
    }

    protected function getDeleteAction(): ExecutableAction
    {
        return new Delete();
    }

    protected function perPage(): int
    {
        return $this->request->query->has('per_page')
            ? $this->request->query->getInt('per_page')
            : self::PER_PAGE;
    }

    protected function createResource(\Illuminate\Support\Collection|Model|LengthAwarePaginator $resource): BaseResource
    {
        return new BaseResource($resource);
    }

    protected function createResourceCollection(
        Collection|LengthAwarePaginator|null $resource
    ): AnonymousResourceCollection {
        return BaseResource::collection($resource);
    }

    protected function validateAndPrepareData(): array
    {
        return $this->getRequestValidator()->validated();
    }

    public function addRelation(string $id, string $relationField, string $relationId = null): JsonResource
    {
        $data = $relationId !== null ? ['id' => $relationId] : $this->request->all();

        $this->request->validate([
            'id' => 'required|string',
        ],[
            'id.required'=>$relationField . ' is requred.'
        ]);

        $model = $this->createNewModelQuery()->find($id);
        $this->beforeUpdate($model);
        $this->addRemoveRelationships($model, $relationField, $data);
        $this->afterUpdate($model);
        return $this->createResource($model);
    }

    public function removeRelation(string $id, string $relationField, string $relationId = null): JsonResource
    {
        $data = $relationId !== null ? ['id' => $relationId] : $this->request->all();

        $model = $this->createNewModelQuery()->find($id);
        $this->beforeUpdate($model);
        $this->addRemoveRelationships($model, $relationField, $data, false);
        $this->afterUpdate($model);
        return $this->createResource($model);
    }

    private function resolveRelationFields(Model $model, array $data)
    {
        $parsedData = [];
        $parsedRelationData = [];
        foreach ($data as $key => $item) {
            $key_camel = Str::camel($key);
            if ($model->isRelation($key_camel)) {
                $isFillable = !$model->isFillable($key) && $model->isFillable($key . '_id');
                if ($isFillable) {
                    $parsedData[$key . '_id'] = (!empty($item)  && is_array($item) && array_key_exists('id', $item)) ? $item['id'] : $item;;
                } else {
                    $parsedRelationData[$key] = $item;
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
            if ($model->isRelation($key_camel)) {
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

    private function syncBelongsToManyRelationship(Model $model, $relationship_name, array $data)
    {
        $present_ids = [];
        foreach ($data as $related) {
            $id = $related['id'] ?? null;
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

    private function appendDetachBelongsToManyRelationship(Model $model, $relationship_name, array $data, bool $append = true)
    {
        $present_ids = [];
        $id = $data['id'] ?? null;
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

    private function syncHasManyRelationship(Model $model, $relationship_name, array $data)
    {
        $unSyncedSubModels = $model->$relationship_name()->pluck('id')->all();
        $subModelClass = $model->$relationship_name()->getRelated();
        foreach ($data as $related) {
            $id = $related['id'] ?? null;
            if (isset($related['DIRTY'])) {
                /** @var Model $subModel */
                $subModel = $subModelClass->newModelQuery()->find($id);
                $subModel->fill($related)->save();
                $model->$relationship_name()->save($subModel);
            } else {
                /** @var Model $subModel */
                $subModel = $model->$relationship_name()->create($related);
            }

            if (($index = array_search($subModel->id, $unSyncedSubModels)) !== false) {
                unset($unSyncedSubModels[$index]);
            }
        }

        foreach ($unSyncedSubModels as $unSyncedSubModel) {
            $record = $model->$relationship_name()->where('id', '=', $unSyncedSubModel)->first();
            $record->delete();
        }
    }
}
