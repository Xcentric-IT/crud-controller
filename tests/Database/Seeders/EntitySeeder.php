<?php

namespace XcentricItFoundation\LaravelCrudController\Tests\Database\Seeders;

use Illuminate\Database\Seeder;
use XcentricItFoundation\LaravelCrudController\Tests\Models\Entity;
use XcentricItFoundation\LaravelCrudController\Tests\Models\Module;

class EntitySeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::query()->firstOrFail();

        Entity::query()->create([
            'name' => 'Test Entity',
            'module_id' => $module->getKey(),
        ]);

        $parentEntity = Entity::query()->firstOrFail();

        Entity::query()->create([
            'name' => 'Test Child Entity',
            'module_id' => $module->getKey(),
            'parent_class_id' => $parentEntity->getKey(),
        ]);
    }
}
