<?php

namespace XcentricItFoundation\LaravelCrudController\Tests\Database\Seeders;

use Illuminate\Database\Seeder;
use XcentricItFoundation\LaravelCrudController\Tests\Models\Entity;
use XcentricItFoundation\LaravelCrudController\Tests\Models\EntityInterface;

class EntityInterfaceSeeder extends Seeder
{
    public function run(): void
    {
        /** @var Entity $entity */
        $entity = Entity::query()->firstOrFail();

        $testInterfaceOne = EntityInterface::query()->create([
            'name' => 'TestInterfaceOne',
            'fqn' => 'App\Contracts\TestInterfaceOne',
        ]);

        $testInterfaceTwo = EntityInterface::query()->create([
            'name' => 'TestInterfaceTwo',
            'fqn' => 'App\Contracts\TestInterfaceTwo',
        ]);

        EntityInterface::query()->create([
            'name' => 'TestInterfaceThree',
            'fqn' => 'App\Contracts\TestInterfaceThree',
        ]);

        $entity->interfaces()->sync([
            $testInterfaceOne->id,
            $testInterfaceTwo->id,
        ]);
    }
}
