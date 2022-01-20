<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\QueryBuilder\QueryBuilderServiceProvider;
use XcentricItFoundation\LaravelCrudController\LaravelCrudController;
use XcentricItFoundation\LaravelCrudController\LaravelCrudControllerServiceProvider;
use XcentricItFoundation\LaravelCrudController\Tests\Database\Seeders\DatabaseSeeder;
use XcentricItFoundation\LaravelCrudController\Tests\Database\Seeders\EntityFieldSeeder;
use XcentricItFoundation\LaravelCrudController\Tests\Database\Seeders\EntityInterfaceSeeder;
use XcentricItFoundation\LaravelCrudController\Tests\Database\Seeders\EntitySeeder;
use XcentricItFoundation\LaravelCrudController\Tests\Database\Seeders\ModuleSeeder;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    /**
    * Run a specific seeder before each test.
    *
    * @var string
    */
    protected string $seeder = DatabaseSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            ModuleSeeder::class,
            EntitySeeder::class,
            EntityFieldSeeder::class,
            EntityInterfaceSeeder::class,
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelCrudControllerServiceProvider::class,
            QueryBuilderServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

    protected function defineRoutes($router)
    {
        $model = 'entity';
        $namespace = 'XcentricItFoundation\LaravelCrudController\Tests';
        $crudController = LaravelCrudController::class;

        $router->group(['prefix' => $model], function () use ($router, $crudController, $model, $namespace) {
            $router->get('/', ['uses' => $crudController . '@readMore', 'model' => $model, 'namespace' => $namespace]);
            $router->get('/{id}', ['uses' => $crudController . '@readOne', 'model' => $model, 'namespace' => $namespace]);
            $router->post('/', ['uses' => $crudController . '@create', 'model' => $model, 'namespace' => $namespace]);
            $router->put('/{id}', ['uses' => $crudController . '@update', 'model' => $model, 'namespace' => $namespace]);
            $router->delete('/{id}', ['uses' => $crudController . '@delete', 'model' => $model, 'namespace' => $namespace]);
            $router->put('/{id}/relation/{relationField}', ['uses' => $crudController . '@addRelation', 'model' => $model, 'namespace' => $namespace]);
            $router->delete('/{id}/relation/{relationField}/{relationId}', ['uses' => $crudController . '@removeRelation', 'model' => $model, 'namespace' => $namespace]);
        });
    }
}
