<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
use XcentricItFoundation\LaravelCrudController\Services\Crud\EntityRelationsService;

class LaravelCrudController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, ParsesQuery, CrudCallbacks;

    public const HTTP_STATUS_EMPTY = 204;

    public const PER_PAGE = 20;

    public function __construct(
        protected Request $request,
        protected EntityRelationsService $entityRelationsService
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
        $data = $this->requestData();

        $model = $this->createModel();

        $this->beforeCreate($model);

        $this->getCreateAction()->run(new CrudActionPayload($data, $model));

        $this->afterCreate($model);
        $model->fresh();

        return $this->createResource($model);
    }

    public function update(string $id): JsonResource
    {
        $data = $this->requestData();

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
        return new Create($this->entityRelationsService);
    }

    protected function getUpdateAction(): ExecutableAction
    {
        return new Update($this->entityRelationsService);
    }

    protected function getDeleteAction(): ExecutableAction
    {
        return new Delete($this->entityRelationsService);
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

    protected function requestData(): array
    {
        return $this->request->all();
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

    private function getPivotColumnData(BelongsToMany $relation, array $data): array
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
