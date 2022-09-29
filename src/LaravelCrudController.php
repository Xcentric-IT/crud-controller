<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Providers\FormRequestServiceProvider;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Routing\Route;
use XcentricItFoundation\LaravelCrudController\Actions\ActionPayloadInterface;
use XcentricItFoundation\LaravelCrudController\Actions\Crud\AddRelation;
use XcentricItFoundation\LaravelCrudController\Actions\Crud\Create;
use XcentricItFoundation\LaravelCrudController\Actions\Crud\CrudActionPayload;
use XcentricItFoundation\LaravelCrudController\Actions\Crud\Delete;
use XcentricItFoundation\LaravelCrudController\Actions\Crud\RemoveRelation;
use XcentricItFoundation\LaravelCrudController\Actions\Crud\Update;
use XcentricItFoundation\LaravelCrudController\Actions\ExecutableAction;
use XcentricItFoundation\LaravelCrudController\Actions\ExecutableActionResponseContract;
use XcentricItFoundation\LaravelCrudController\Services\QueryParserService;

class LaravelCrudController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public const HTTP_STATUS_EMPTY = 204;

    public const PER_PAGE = 20;

    public function __construct(
        protected QueryParserService $queryParserService
    ) {
    }

    public function readOne(Request $request, string $id): JsonResource
    {
        /** @var Model $model */
        $model = $this->queryParserService
            ->parseRequest($request, $this->getModel(), $this->getAdditionalFilters())
            ->findOrFail($id);

        $this->authorize('readOne', [$this->getModel(), $model]);

        return $this->createResource($model);
    }

    public function readMore(Request $request): JsonResource
    {
        $this->authorize('readMore', $this->getModel());

        return $this->createResourceCollection(
            $this->queryParserService
                ->parseRequest($request, $this->getModel(), $this->getAdditionalFilters())
                ->paginate($this->perPage())
        );
    }

    public function create(): JsonResource
    {
        $this->authorize('create', $this->getModel());

        $this->resolveRequestValidator();

        $model = $this->createModel();

        $this->onCreate(new CrudActionPayload($this->requestData(), $model));

        return $this->createResource($model);
    }

    public function update(string $id): JsonResource
    {
        /** @var Model $model */
        $model = $this->createNewModelQuery()->findOrFail($id);

        $this->authorize('update', [$this->getModel(), $model]);

        if (config('laravel-crud-controller.merge_model_data_to_request') === true) {
            $this->mergeModelDataToRequest($model);
        }

        $this->resolveRequestValidator();

        /** @var Model $freshModel */
        $freshModel = $model->fresh();

        $this->onUpdate(new CrudActionPayload($this->requestData(), $model, $freshModel->toArray()));

        return $this->createResource($freshModel);
    }

    public function delete(string $id): JsonResponse
    {
        $data = [];

        /** @var Model $model */
        $model = $this->createNewModelQuery()->findOrFail($id);
        $this->authorize('delete', [$this->getModel(), $model]);

        $this->onDelete(new CrudActionPayload($data, $model));

        return $this->returnNoContent();
    }

    public function addRelation(Request $request, string $id, string $relationField): JsonResource
    {
        $request->validate([
            'id' => 'required|string',
        ]);

        $data = $request->all();

        /** @var Model $model */
        $model = $this->createNewModelQuery()->findOrFail($id);
        $this->authorize('update', [$this->getModel(), $model]);

        $actionPayloadAdditionalData = [
            'relationField' => $relationField,
        ];

        $actionPayload = new CrudActionPayload($data, $model);
        $actionPayload->setAdditionalData($actionPayloadAdditionalData);

        $this->onAddRelation($actionPayload);

        return $this->createResource($model);
    }

    public function removeRelation(Request $request, string $id, string $relationField, string $relationId = null): JsonResource
    {
        $data = $relationId !== null ? ['id' => $relationId] : $request->all();

        /** @var Model $model */
        $model = $this->createNewModelQuery()->findOrFail($id);
        $this->authorize('update', [$this->getModel(), $model]);

        $actionPayloadAdditionalData = [
            'relationField' => $relationField,
            'add' => false,
        ];

        $actionPayload = new CrudActionPayload($data, $model);
        $actionPayload->setAdditionalData($actionPayloadAdditionalData);

        $this->onRemoveRelation($actionPayload);

        return $this->createResource($model);
    }

    protected function onCreate(ActionPayloadInterface $actionPayload): ExecutableActionResponseContract
    {
        return $this->getCreateAction()->run($actionPayload);
    }

    protected function onUpdate(ActionPayloadInterface $actionPayload): ExecutableActionResponseContract
    {
        return $this->getUpdateAction()->run($actionPayload);
    }

    protected function onDelete(ActionPayloadInterface $actionPayload): ExecutableActionResponseContract
    {
        return $this->getDeleteAction()->run($actionPayload);
    }

    protected function onAddRelation(ActionPayloadInterface $actionPayload): ExecutableActionResponseContract
    {
        return $this->getAddRelationAction()->run($actionPayload);
    }

    protected function onRemoveRelation(ActionPayloadInterface $actionPayload): ExecutableActionResponseContract
    {
        return $this->getRemoveRelationAction()->run($actionPayload);
    }

    protected function returnNoContent(): JsonResponse
    {
        return response()->json(null, self::HTTP_STATUS_EMPTY);
    }

    protected function getRequestValidator(): string
    {
        /** @var Request $request */
        $request = request();
        /** @var Route $route */
        $route = $request->route();

        $requestClass = ModelHelper::getRequestValidatorFqn($route->getAction('model'), $route->getAction('namespace'));

        if (!class_exists($requestClass)) {
            $requestClass = LaravelCrudRequest::class;
        }

        return $requestClass;
    }

    protected function getModel(): string
    {
        /** @var Request $request */
        $request = request();
        /** @var Route $route */
        $route = $request->route();

        return ModelHelper::getModelFqn($route->getAction('model'), $route->getAction('namespace'));
    }

    protected function getAdditionalFilters(): array
    {
        return [];
    }

    /**
     * Request validation is called on resolving Request class
     * @see FormRequestServiceProvider::boot()
     */
    protected function resolveRequestValidator(): LaravelCrudRequest
    {
        return resolve($this->getRequestValidator());
    }

    protected function createModel(): Model
    {
        return resolve($this->getModel());
    }

    protected function createNewModelQuery(): Builder
    {
        return $this->createModel()->newQuery();
    }

    protected function getCreateAction(): ExecutableAction
    {
        return resolve(Create::class);
    }

    protected function getUpdateAction(): ExecutableAction
    {
        return resolve(Update::class);
    }

    protected function getDeleteAction(): ExecutableAction
    {
        return resolve(Delete::class);
    }

    protected function getAddRelationAction(): ExecutableAction
    {
        return resolve(AddRelation::class);
    }

    protected function getRemoveRelationAction(): ExecutableAction
    {
        return resolve(RemoveRelation::class);
    }

    protected function perPage(): int
    {
        /** @var Request $request */
        $request = request();
        return $request->query->has('per_page')
            ? $request->query->getInt('per_page')
            : self::PER_PAGE;
    }

    protected function createResource(
        Collection|Model|LengthAwarePaginator|null $resource
    ): BaseResource {
        return new BaseResource($resource);
    }

    protected function createResourceCollection(
        Collection|LengthAwarePaginator|null $resource
    ): AnonymousResourceCollection {
        return BaseResource::collection($resource);
    }

    protected function requestData(): array
    {
        return request()->all();
    }

    protected function mergeModelDataToRequest(Model $model): void
    {
        $requestClassFqn = $this->getRequestValidator();

        /** @var LaravelCrudRequest $requestClass */
        $requestClass = new $requestClassFqn;

        $relations = [];
        foreach (array_keys($requestClass->rules()) as $fieldName) {
            if ($model->isRelation($fieldName)) {
                $relations[] = $fieldName;
            }
        }

        if (count($relations) > 0) {
            $model->load($relations);
        }

        request()->mergeIfMissing($model->toArray());
    }
}
