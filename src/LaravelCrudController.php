<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller as BaseController;
use XcentricItFoundation\LaravelCrudController\Actions\Crud\AddRelation;
use XcentricItFoundation\LaravelCrudController\Actions\Crud\Create;
use XcentricItFoundation\LaravelCrudController\Actions\Crud\CrudActionPayload;
use XcentricItFoundation\LaravelCrudController\Actions\Crud\Delete;
use XcentricItFoundation\LaravelCrudController\Actions\Crud\RemoveRelation;
use XcentricItFoundation\LaravelCrudController\Actions\Crud\Update;
use XcentricItFoundation\LaravelCrudController\Actions\ExecutableAction;
use XcentricItFoundation\LaravelCrudController\Services\Crud\EntityRelationsService;

class LaravelCrudController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, ParsesQuery, CrudCallbacks;

    public const HTTP_STATUS_EMPTY = 204;

    public const PER_PAGE = 20;

    public function __construct(
        protected Request $request
    ) {
    }

    public function readOne(string $id): JsonResource
    {
        return $this->createResource(
            $this->parseRequest($this->request, $this->getModel())->find($id)
        );
    }

    public function readMore(): JsonResource
    {
        return $this->createResourceCollection(
            $this
                ->parseRequest($this->request, $this->getModel())
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

    public function addRelation(string $id, string $relationField, string $relationId = null): JsonResource
    {
        $data = $relationId !== null ? ['id' => $relationId] : $this->request->all();
        $data['relationField'] = $relationField;

        $this->request->validate([
            'id' => 'required|string',
        ],[
            'id.required'=>$relationField . ' is requred.'
        ]);

        $model = $this->createNewModelQuery()->find($id);
        $this->beforeUpdate($model);

        $this->getAddRelationAction()->run(new CrudActionPayload($data, $model));

        $this->afterUpdate($model);
        return $this->createResource($model);
    }

    public function removeRelation(string $id, string $relationField, string $relationId = null): JsonResource
    {
        $data = $relationId !== null ? ['id' => $relationId] : $this->request->all();
        $data['relationField'] = $relationField;

        $model = $this->createNewModelQuery()->find($id);
        $this->beforeUpdate($model);

        $this->getRemoveRelationAction()->run(new CrudActionPayload($data, $model));

        $this->afterUpdate($model);
        return $this->createResource($model);
    }

    public function returnNoContent(): JsonResponse
    {
        return response()->json(null, self::HTTP_STATUS_EMPTY);
    }

    protected function getRequestValidator(): LaravelCrudRequest
    {
        $requestClass = ModelHelper::getRequestValidatorFqn($this->request->route()->getAction('model'), $this->request->route()->getAction('namespace'));

        if (class_exists($requestClass)) {
            return resolve($requestClass);
        }

        return resolve(LaravelCrudRequest::class);
    }

    protected function getModel(): string
    {
        return ModelHelper::getModelFqn($this->request->route()->getAction('model'), $this->request->route()->getAction('namespace'));
    }

    protected function createNewModelQuery(): Builder
    {
        return resolve($this->getModel())->newQuery();
    }

    protected function createModel(): Model
    {
        return resolve($this->getModel());
    }

    protected function getCreateAction(): ExecutableAction
    {
        return new Create(new EntityRelationsService());
    }

    protected function getUpdateAction(): ExecutableAction
    {
        return new Update(new EntityRelationsService());
    }

    protected function getDeleteAction(): ExecutableAction
    {
        return new Delete(new EntityRelationsService());
    }

    protected function getAddRelationAction(): ExecutableAction
    {
        return new AddRelation(new EntityRelationsService());
    }

    protected function getRemoveRelationAction(): ExecutableAction
    {
        return new RemoveRelation(new EntityRelationsService());
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
        $data = $this->request->all();
        $this->getRequestValidator()->validate();
        return $data;
    }
}
