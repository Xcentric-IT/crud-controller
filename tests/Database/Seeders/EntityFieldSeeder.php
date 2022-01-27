<?php

namespace XcentricItFoundation\LaravelCrudController\Tests\Database\Seeders;

use Illuminate\Database\Seeder;
use XcentricItFoundation\LaravelCrudController\Tests\Models\Entity;
use XcentricItFoundation\LaravelCrudController\Tests\Models\EntityField;

class EntityFieldSeeder extends Seeder
{
    public function run(): void
    {
        /** @var Entity $entity */
        $entity = Entity::query()->firstOrFail();

        $entityFieldOne = EntityField::query()->create([
            'name' => 'Field One',
            'entity_id' => $entity->id,
        ]);

        $entityFieldTwo = EntityField::query()->create([
            'name' => 'Field Two',
            'entity_id' => $entity->id,
        ]);

        $entityFieldThree = EntityField::query()->create([
            'name' => 'Field Three',
            'entity_id' => $entity->id,
        ]);

        $entity->fields()->saveMany([
            $entityFieldOne,
            $entityFieldTwo,
            $entityFieldThree,
        ]);
    }
}
