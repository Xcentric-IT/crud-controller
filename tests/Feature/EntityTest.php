<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use XcentricItFoundation\LaravelCrudController\Tests\Models\Entity;
use XcentricItFoundation\LaravelCrudController\Tests\Models\EntityField;
use XcentricItFoundation\LaravelCrudController\Tests\Models\EntityInterface;
use XcentricItFoundation\LaravelCrudController\Tests\Models\Module;
use XcentricItFoundation\LaravelCrudController\Tests\TestCase;

class EntityTest extends TestCase
{
    protected function getModel(): Model
    {
        return Entity::query()->firstOrFail();
    }

    protected function fields(): array
    {
        return [
            'name',
            'parent_class_id',
            'module_id',
        ];
    }

    protected function getApiUrl(?string $id = null): string
    {
        return 'entity' . ($id ? "/$id" : '');
    }

    public function testReadMore(): void
    {
        $response = $this->get($this->getApiUrl());

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function testReadOne(): void
    {
        /** @var Entity $entity */
        $entity = $this->getModel();

        $response = $this->get($this->getApiUrl($entity->getKey()));

        $response->assertStatus(200);

        foreach ($this->fields() as $field) {
            self::assertEquals($entity->$field, $response->json('data.' . $field));
        }
    }

    public function testCreate(): void
    {
        /** @var Module $module */
        $module = Module::query()->firstOrFail();

        $requestData = [
            'name' => 'Test Created Model',
            'parent_class' => null,
            'module' => $module->toArray(),
        ];

        $response = $this->post($this->getApiUrl(), $requestData);

        $response->assertStatus(201);

        $data = $requestData;
        $data['parent_class_id'] = $data['parent_class'] ? $data['parent_class']['id'] : null;
        $data['module_id'] = $data['module']['id'];

        foreach ($this->fields() as $field) {
            self::assertEquals($data[$field], $response->json('data.' . $field));
        }
    }

    public function testCreateWithRelations(): void
    {
        /** @var Module $module */
        $module = Module::query()->firstOrFail();

        /** @var EntityInterface $entityInterface */
        $entityInterface = EntityInterface::query()->where('name', '=', 'TestInterfaceThree')->firstOrFail();

        /** @var EntityField $entityField */
        $entityField = EntityField::query()->where('name', '=', 'Field Three')->firstOrFail();

        $requestData = [
            'name' => 'Test Created Model',
            'parent_class' => null,
            'module' => $module->toArray(),
            'fields' => [
                $entityField->toArray(),
                [
                    'name' => 'Field Four',
                ],
                [
                    'name' => 'Field Five',
                ],
            ],
            'interfaces' => [
                $entityInterface->toArray(),
                [
                    'name' => 'TestInterfaceFour',
                    'fqn' => 'App\Contracts\TestInterfaceFour',
                ],
            ],
        ];

        $response = $this->post($this->getApiUrl(), $requestData);

        $response->assertStatus(201);

        $data = $requestData;
        $data['parent_class_id'] = $data['parent_class'] ? $data['parent_class']['id'] : null;
        $data['module_id'] = $data['module']['id'];

        foreach ($this->fields() as $field) {
            self::assertEquals($data[$field], $response->json('data.' . $field));
        }

        /** @var Entity $entity */
        $entity = Entity::query()->findOrFail($response->json('data.id'));
        $entityFields = $entity->fields;
        $entityInterfaces = $entity->interfaces;

        self::assertEquals(3, $entityFields->count());
        self::assertArrayHasKey($entityField->getKey(), $entityFields->pluck('name', 'id'));

        self::assertEquals(2, $entityInterfaces->count());
        self::assertArrayHasKey($entityInterface->getKey(), $entityInterfaces->pluck('name', 'id'));
    }

    public function testUpdate(): void
    {
        /** @var Entity $entity */
        $entity = Entity::query()->with(['parentClass', 'module'])->firstOrFail();

        $data = $entity->toArray();
        $data['name'] = 'Test Updated Model';

        $requestData = $data;
        unset($requestData['parent_class_id'], $requestData['module_id']);

        $response = $this->put($this->getApiUrl($entity->getKey()), $requestData);

        $response->assertStatus(200);

        $fieldName = 'name';
        foreach ($this->fields() as $field) {
            if ($field === $fieldName) {
                self::assertNotEquals($entity->name, $response->json('data.' . $fieldName));
            }
            self::assertEquals($data[$field], $response->json('data.' . $field));
        }
    }

    public function testUpdateWithRelations(): void
    {
        /** @var Entity $entity */
        $entity = Entity::query()->with(['parentClass', 'module', 'fields', 'interfaces'])->firstOrFail();

        /** @var EntityInterface $entityInterface */
        $entityInterface = EntityInterface::query()->where('name', '=', 'TestInterfaceThree')->firstOrFail();

        /** @var EntityField $entityField */
        $entityField = EntityField::query()->where('name', '=', 'Field Three')->firstOrFail();

        $data = $entity->toArray();
        $data['name'] = 'Test Updated Model';
        $data['fields'] = [
            ...$data['fields'],
            ...[
                [
                    'name' => 'Field Four',
                ],
                [
                    'name' => 'Field Five',
                ],
            ],
        ];
        $data['interfaces'] = [
            ...$data['interfaces'],
            ...[
                $entityInterface->toArray(),
                [
                    'name' => 'TestInterfaceFour',
                    'fqn' => 'App\Contracts\TestInterfaceFour',
                ],
            ],
        ];

        $requestData = $data;
        unset($requestData['parent_class_id'], $requestData['module_id']);

        $response = $this->put($this->getApiUrl($entity->getKey()), $requestData);

        $response->assertStatus(200);

        $fieldName = 'name';
        foreach ($this->fields() as $field) {
            if ($field === $fieldName) {
                self::assertNotEquals($entity->name, $response->json('data.' . $fieldName));
            }
            self::assertEquals($data[$field], $response->json('data.' . $field));
        }

        $entity->refresh();
        $entityFields = $entity->fields;
        $entityInterfaces = $entity->interfaces;

        self::assertEquals(5, $entityFields->count());
        self::assertArrayHasKey($entityField->getKey(), $entityFields->pluck('name', 'id'));

        self::assertEquals(4, $entityInterfaces->count());
        self::assertArrayHasKey($entityInterface->getKey(), $entityInterfaces->pluck('name', 'id'));
    }

    public function testDelete(): void
    {
        /** @var Entity $entity */
        $entity = $this->getModel();

        $apiUrl = $this->getApiUrl($entity->getKey());

        $deleteResponse = $this->delete($apiUrl);
        $deleteResponse->assertStatus(204);

        $response = $this->get($apiUrl);
        $response->assertJsonCount(0, 'data');
    }

    public function testAddRelation(): void
    {
        /** @var Entity $entity */
        $entity = $this->getModel();

        /** @var EntityInterface $entityInterface */
        $entityInterface = EntityInterface::query()->where('name', '=', 'TestInterfaceThree')->firstOrFail();

        $data = [
            'id' => $entityInterface->id,
        ];

        $apiUrl = $this->getApiUrl($entity->getKey() . '/relation/interfaces');

        $response = $this->put($apiUrl, $data);
        $response->assertStatus(200);

        $entity->refresh();
        self::assertEquals(3, $entity->interfaces->count());
    }

    public function testRemoveRelation(): void
    {
        /** @var Entity $entity */
        $entity = $this->getModel();

        /** @var EntityInterface $entityInterface */
        $entityInterface = EntityInterface::query()->where('name', '=', 'TestInterfaceOne')->firstOrFail();

        $apiUrl = $this->getApiUrl($entity->getKey() . '/relation/interfaces/' . $entityInterface->id);

        $response = $this->delete($apiUrl);
        $response->assertStatus(200);

        $entity->refresh();
        self::assertEquals(1, $entity->interfaces->count());
    }

    public function testFiltering(): void
    {
        /** @var Entity $entity */
        $entity = $this->getModel();

        foreach ($this->fields() as $field) {
            $response = $this->get($this->getApiUrl() . '?filter[' . $field . ']=' . $entity->$field);

            $response->assertStatus(200);
            $response->assertJsonCount(1, 'data');

            self::assertEquals($entity->$field, $response->json('data.0.'.$field));
        }
    }

    public function testIncludes(): void
    {
        /** @var Entity $entity */
        $entity = $this->getModel();

        $response = $this->get($this->getApiUrl($entity->getKey()) . '?include=interfaces');
        $response->assertJsonCount(2, 'data.interfaces');
    }
}
