<?php

namespace XcentricItFoundation\LaravelCrudController\Tests\Database\Seeders;

use Illuminate\Database\Seeder;
use XcentricItFoundation\LaravelCrudController\Tests\Models\Module;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        Module::query()->create([
            'name' => 'Test',
            'slug' => 'test',
        ]);
    }
}
