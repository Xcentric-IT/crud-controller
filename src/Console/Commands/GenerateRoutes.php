<?php

namespace XcentricItFoundation\LaravelCrudController\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Console\Command\Command as CommandAlias;
use XcentricItFoundation\LaravelCrudController\Attribute\SkipRouteGenerate;
use XcentricItFoundation\LaravelCrudController\LaravelCrudController;
use XcentricItFoundation\LaravelCrudController\ModelHelper;

class GenerateRoutes extends Command
{
    private const EXCLUDE_MODULE = 'exclude-module';

    private const ROUTES_TEMPLATE_NAMESPACE = 'CommandRoutes';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bizhive:generate-routes
                            {module?}
                            {--' . self::EXCLUDE_MODULE . '= : Which module (by providing its name) to be excluded from generating routes}';

    /**
     * @var string
     */
    protected $description = 'Generate routes for BizHive Modules';

    private string $module;

    private bool $moduleIsApp = false;

    private string $moduleNamespace;

    private string $template = 'routes';

    private string $rootPath = 'modules';

    /**
     * Execute the console command.
     *
     * @throws ReflectionException
     */
    public function handle(): int
    {
        $module = $this->argument('module');
        $excludeModule = $this->option(self::EXCLUDE_MODULE);

        if (is_null($module) === false) {
            // generate routes only for specified module
            $this->generateRoutesForModule($module);
            return CommandAlias::SUCCESS;
        }
        $modules = $this->getAllModules($excludeModule);
        $this->info('Generating routes...');
        foreach ($modules as $module) {
            $this->generateRoutesForModule($module);
        }
        $this->info('Done');
        $this->newLine();
        return CommandAlias::SUCCESS;
    }

    /**
     * @throws ReflectionException
     */
    protected function generateRoutesForModule(string $module): int
    {
        $this->module = $module;
        $this->moduleIsApp = Str::lower($this->module) === 'app';
        if ($this->validateModule() === false) {
            $this->newLine();
            $this->error('<bg=Red>Command FAILED! Please check and fix errors above');
            return CommandAlias::FAILURE;
        }
        $this->setModuleNamespace();
        $this->addViewNamespace();
        $this->generateRoutes();

        return CommandAlias::SUCCESS;
    }

    private function validateModule(): bool
    {
        $this->rootPath = ($this->moduleIsApp) ? '' : 'modules';
        if (File::exists($this->modulePath()) === false) {
            $this->newLine();
            $this->error('Module ' . $this->module . ' doesn\'t exist! Aborting command.');
            $this->comment('Please make sure that module ' . $this->module . ' exists in following location:');
            $this->comment('- ' . $this->modulePath());
            $this->newLine();
            $this->line('<fg=Cyan>You can generate new BizHive module using command:');
            $this->line('<fg=Cyan>php artisan generate:module ' . $this->module);
            return false;
        }
        if (File::exists($this->modulePath('Models')) === false) {
            $this->newLine();
            $this->error('Module ' . $this->module . ' doesn\'t have Models directory, please create it');
            return false;
        }
        if (File::exists($this->modulePath('Routes')) === false) {
            $this->newLine();
            $this->error('Module ' . $this->module . ' doesn\'t have Routes directory, please create it');
            return false;
        }
        return true;
    }

    private function setModuleNamespace(): void
    {
        $this->moduleNamespace = ($this->moduleIsApp)
            ? 'App'
            : 'Modules\\' . $this->module;
    }

    /**
     * @throws ReflectionException
     */
    private function generateRoutes(): void
    {
        $models = $this->getModelsArray();

        $view = view(self::ROUTES_TEMPLATE_NAMESPACE . '::' . $this->template, [
            'module' => Str::snake($this->module, '-'),
            'moduleName' => $this->module,
            'namespace' => $this->moduleNamespace,
            'models' => $models,
            'controllersFqn' => $this->getAllControllersInUse($models),
            'routePrefix' => $this->routePrefix()
        ]);

        $filePath = $this->modulePath('Routes') . DIRECTORY_SEPARATOR . 'api.php';
        if (File::exists($filePath)) {
            File::delete($filePath);
        }
        File::put($filePath, $view->render());
        $this->info('Module: ' . $this->module . ' (path: '.$filePath.')');
    }

    /**
     * @return string[]
     */
    private function getAllModules(?string $excludeModule): array
    {
        $modules = ['app'];
        if (File::exists(app()->basePath('modules')) === false) {
            return $modules;
        }
        $modulesDirs = File::directories(app()->basePath('modules'));
        foreach ($modulesDirs as $module) {
            $modules[] = basename($module);
        }

        $modules = array_filter($modules, function ($item) use ($excludeModule) {
            return $item !== $excludeModule;
        });

        return $modules;
    }

    /**
     * Prepare list of models in given module for which routes need to be generated
     *
     * @throws ReflectionException
     */
    private function getModelsArray(): array
    {
        $models = [];
        foreach ($this->getModuleModels()->values() as $modelClass) {
            $modelName = Str::substr($modelClass, strrpos($modelClass, '\\') + 1);
            $controller = $this->resolveModelController($modelName);
            $controllerClassName = class_basename($controller) . '::class';
            $models[] = [
                'class' => $modelClass,
                'name' => $modelName,
                'humanName' => str_replace('-', ' ', Str::snake($modelName,  '-')),
                'slug' => Str::snake($modelName,  '-'),
                'controller' => $controller,
                'controllerClassName' => $controllerClassName
            ];
        }
        return $models;
    }

    /**
     * Prepare list of controllers in use for routes that will be generated
     */
    private function getAllControllersInUse(array $models): array
    {
        $controllersInUse = [];
        foreach ($models as $model) {
            if (!in_array($model['controller'], $controllersInUse, true)) {
                $controllersInUse[] = $model['controller'];
            }
        }
        return $controllersInUse;
    }

    private function getModuleModels(): Collection
    {
        $modelsPath = $this->modulePath('Models');
        return collect(File::allFiles($modelsPath))
            ->map(function ($item) {
                $path = $item->getRelativePathName();
                $class = sprintf('\%s%s',
                    "{$this->moduleNamespace}\\Models\\",
                    strtr(Str::substr($path, 0, strrpos($path, '.')), '/', '\\'));
                return $class;
            })
            ->filter(function ($class) {
                return $this->modelValidForRouteGenerating($class);
            });
    }

    /**
     * @throws ReflectionException
     */
    private function modelValidForRouteGenerating(string $class): bool
    {
        if (class_exists($class) === false) {
            return false;
        }
        $reflection = new ReflectionClass($class);
        if ($reflection->isSubclassOf(Model::class) === false) {
            return false;
        }
        if ($reflection->isAbstract()) {
            return false;
        }
        if ($reflection->getAttributes(SkipRouteGenerate::class)){
            return false;
        }
        return true;
    }

    /**
     * @throws ReflectionException
     */
    private function resolveModelController(string $modelName): string
    {
        $controllerClass = ModelHelper::getControllerFqn($modelName, $this->moduleNamespace);
        if (class_exists($controllerClass) && $this->controllerIsValid($controllerClass)) {
            return $controllerClass;
        }
        return LaravelCrudController::class;
    }

    /**
     * @throws ReflectionException
     */
    private function controllerIsValid(string $controllerClass): bool
    {
        $reflection = new ReflectionClass($controllerClass);
        $valid = $reflection->isSubclassOf(LaravelCrudController::class);
        if ($valid === false) {
            $this->warn('Custom controller ' . $controllerClass . ' found but it\'s not valid. Controller must extend LaravelCrudController');
        }
        return $valid;
    }

    private function routePrefix(): string
    {
        if ($this->moduleIsApp === false) {
            return Str::snake($this->module, '-') . '/';
        }
        return '';
    }

    private function addViewNamespace(): void
    {
        View::addNamespace(self::ROUTES_TEMPLATE_NAMESPACE, __DIR__ . DIRECTORY_SEPARATOR . 'views');
    }

    private function modulePath(string $suffix = ''): string
    {
        return app()->basePath($this->rootPath)
            . DIRECTORY_SEPARATOR
            . $this->module
            . DIRECTORY_SEPARATOR
            . $suffix;
    }
}
