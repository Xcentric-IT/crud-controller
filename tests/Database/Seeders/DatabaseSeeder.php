<?php

namespace XcentricItFoundation\LaravelCrudController\Tests\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ModuleSeeder::class,
            EntitySeeder::class,
            EntityFieldSeeder::class,
            EntityInterfaceSeeder::class,
        ]);
    }
}
