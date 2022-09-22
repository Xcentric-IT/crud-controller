@php
echo '<?php'
@endphp

/**
 * DO NOT EDIT!!!
 * Autogenerated CRUD routes for module {{\Illuminate\Support\Str::ucfirst($module)}}
 * If custom routes are needed for this module
 * please define them in {{\Illuminate\Support\Str::ucfirst($module)}}/Routes/custom/ directory, they will be automatically loaded
 */

use Illuminate\Support\Facades\Route;
@foreach($controllersFqn as $controllerFqn)
use {{ $controllerFqn }};
@endforeach

@foreach($models as $model)
/**
 * CRUD routes for model {{$model['class']}}
 */
Route::group(["prefix" => "{{$routePrefix}}{{$model['slug']}}"], function ($router) {
    /**
     * @OA\Get(
     *     path="/{{$routePrefix}}{{$model['slug']}}",
     *     operationId="get{{str_replace('\\', '', $namespace)}}{{$model['name']}}List",
     *     tags={"{{$namespace}}\{{$model['name']}}"},
     *     summary="Get {{$model['humanName']}} list.",
     *     @OA\Parameter(in="header", name="Accept", required=true, example="application/json"),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/{{$namespace}}\{{$model['name']}}")
     *             ),
     *             @OA\Property(
     *                 property="links",
     *                 type="object",
     *                 properties={
     *                     @OA\Property(property="first", type="string"),
     *                     @OA\Property(property="last", type="string"),
     *                     @OA\Property(property="next", type="string"),
     *                     @OA\Property(property="prev", type="string")
     *                 }
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 properties={
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="from", type="string"),
     *                     @OA\Property(property="last_page", type="integer"),
     *                     @OA\Property(
     *                         property="links",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             properties={
     *                                 @OA\Property(property="active", type="boolean"),
     *                                 @OA\Property(property="label", type="string"),
     *                                 @OA\Property(property="url", type="string")
     *                             }
     *                         ),
     *                     ),
     *                     @OA\Property(property="path", type="string"),
     *                     @OA\Property(property="per_page", type="integer"),
     *                     @OA\Property(property="to", type="integer"),
     *                     @OA\Property(property="total", type="integer")
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad request."),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(@OA\Property(property="message", type="string", default="Unauthenticated."))),
     *     @OA\Response(response=500, description="Internal server error.")
     * )
     */
    $router->get('/', ['uses' => {{ $model['controllerClassName'] }} . '@readMore', 'model' => '{{$model['slug']}}', 'namespace' => '{{$namespace}}']);
    /**
     * @OA\Get(
     *     path="/{{$routePrefix}}{{$model['slug']}}/{id}",
     *     operationId="get{{str_replace('\\', '', $namespace)}}{{$model['name']}}",
     *     tags={"{{$namespace}}\{{$model['name']}}"},
     *     summary="Get {{$model['humanName']}}.",
     *     @OA\Parameter(in="header", name="Accept", required=true, example="application/json"),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Successful operation.", @OA\JsonContent(ref="#/components/schemas/{{$namespace}}\{{$model['name']}}")),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(@OA\Property(property="message", type="string", default="Unauthenticated."))),
     *     @OA\Response(response=404, description="{{ucfirst($model['humanName'])}} not found."),
     *     @OA\Response(response=500, description="Internal server error.")
     * )
     */
    $router->get('/{id}', ['uses' => {{ $model['controllerClassName'] }} . '@readOne', 'model' => '{{$model['slug']}}', 'namespace' => '{{$namespace}}']);
    /**
     * @OA\Post(
     *     path="/{{$routePrefix}}{{$model['slug']}}",
     *     operationId="create{{str_replace('\\', '', $namespace)}}{{$model['name']}}",
     *     tags={"{{$namespace}}\{{$model['name']}}"},
     *     @OA\Parameter(in="header", name="Accept", required=true, example="application/json"),
     *     summary="Create {{$model['humanName']}}.",
     *     requestBody={"$ref": "#/components/requestBodies/{{$namespace}}\{{$model['name']}}"},
     *     @OA\Response(response=201, description="Successful operation.", @OA\JsonContent(ref="#/components/schemas/{{$namespace}}\{{$model['name']}}")),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(@OA\Property(property="message", type="string", default="Unauthenticated."))),
     *     @OA\Response(response=422, description="Error: Unprocessable Content."),
     *     @OA\Response(response=500, description="Internal server error.")
     * )
     */
    $router->post('/', ['uses' => {{ $model['controllerClassName'] }} . '@create', 'model' => '{{$model['slug']}}', 'namespace' => '{{$namespace}}']);
    /**
    * @OA\Post(
    *     path="/{{$routePrefix}}{{$model['slug']}}/mass-create",
    *     operationId="create{{str_replace('\\', '', $namespace)}}{{$model['name']}}",
    *     tags={"{{$namespace}}\{{$model['name']}}"},
    *     @OA\Parameter(in="header", name="Accept", required=true, example="application/json"),
    *     summary="Create {{$model['humanName']}}.",
    *     requestBody={"$ref": "#/components/requestBodies/{{$namespace}}\{{$model['name']}}"},
    *     @OA\Response(response=201, description="Successful operation.", @OA\JsonContent(ref="#/components/schemas/{{$namespace}}\{{$model['name']}}")),
    *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(@OA\Property(property="message", type="string", default="Unauthenticated."))),
    *     @OA\Response(response=422, description="Error: Unprocessable Content."),
    *     @OA\Response(response=500, description="Internal server error.")
    * )
    */
    $router->post('/mass-create', ['uses' => {{ $model['controllerClassName'] }} . '@massCreate', 'model' => '{{$model['slug']}}', 'namespace' => '{{$namespace}}']);
    /**
     * @OA\Put(
     *     path="/{{$routePrefix}}{{$model['slug']}}/{id}",
     *     operationId="put{{str_replace('\\', '', $namespace)}}{{$model['name']}}",
     *     tags={"{{$namespace}}\{{$model['name']}}"},
     *     summary="Put {{$model['humanName']}}.",
     *     @OA\Parameter(in="header", name="Accept", required=true, example="application/json"),
     *     requestBody={"$ref": "#/components/requestBodies/{{$namespace}}\{{$model['name']}}"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=201, description="Successful operation.", @OA\JsonContent(ref="#/components/schemas/{{$namespace}}\{{$model['name']}}")),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", default="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(response=404, description="{{ucfirst($model['humanName'])}} not found."),
     *     @OA\Response(response=422, description="Error: Unprocessable Content."),
     *     @OA\Response(response=500, description="Internal server error.")
     * )
     */
    $router->put('/{id}', ['uses' => {{ $model['controllerClassName'] }} . '@update', 'model' => '{{$model['slug']}}', 'namespace' => '{{$namespace}}']);
    /**
    * @OA\Delete(
    *     path="/{{$routePrefix}}{{$model['slug']}}/mass-delete",
    *     operationId="delete{{str_replace('\\', '', $namespace)}}{{$model['name']}}",
    *     tags={"{{$namespace}}\{{$model['name']}}"},
    *     summary="Delete {{$model['humanName']}}.",
    *     @OA\Parameter(in="header", name="Accept", required=true, example="application/json"),
    *     @OA\Parameter(name="ids", in="path", required=true, @OA\Schema(type="array", format="uuid")),
    *     @OA\Response(response=204, description="No content, delete successful."),
    *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(@OA\Property(property="message", type="string", default="Unauthenticated."))),
    *     @OA\Response(response=404, description="{{ucfirst($model['humanName'])}} not found."),
    *     @OA\Response(response=500, description="Internal server error.")
    * )
    */
    $router->delete('/mass-delete', ['uses' => {{ $model['controllerClassName'] }} . '@massDelete', 'model' => '{{$model['slug']}}', 'namespace' => '{{$namespace}}']);
    /**
     * @OA\Delete(
     *     path="/{{$routePrefix}}{{$model['slug']}}/{id}",
     *     operationId="delete{{str_replace('\\', '', $namespace)}}{{$model['name']}}",
     *     tags={"{{$namespace}}\{{$model['name']}}"},
     *     summary="Delete {{$model['humanName']}}.",
     *     @OA\Parameter(in="header", name="Accept", required=true, example="application/json"),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=204, description="No content, delete successful."),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(@OA\Property(property="message", type="string", default="Unauthenticated."))),
     *     @OA\Response(response=404, description="{{ucfirst($model['humanName'])}} not found."),
     *     @OA\Response(response=500, description="Internal server error.")
     * )
     */
    $router->delete('/{id}', ['uses' => {{ $model['controllerClassName'] }} . '@delete', 'model' => '{{$model['slug']}}', 'namespace' => '{{$namespace}}']);
    $router->put('/{id}/relation/{relationField}', ['uses' => {{ $model['controllerClassName'] }} . '@addRelation', 'model' => '{{$model['slug']}}', 'namespace' => '{{$namespace}}']);
    $router->delete('/{id}/relation/{relationField}/{relationId}', ['uses' => {{ $model['controllerClassName'] }} . '@removeRelation', 'model' => '{{$model['slug']}}', 'namespace' => '{{$namespace}}']);
});
@endforeach
