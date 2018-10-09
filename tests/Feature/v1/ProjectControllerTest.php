<?php

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp()
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => DatabaseSeeder::class]);
    }

    public function test_Create_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        $data = [
            'name'         => 'SampleOriginalProjectName',
            'description'  => 'Code-monkey development group presents'
        ];

        $expectedFields = [
            "res" => [
                "id", "name", "description", 'created_at', 'updated_at'
            ]
        ];

        $response = $this->postJson('/api/v1/projects/create', $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_Destroy_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        $createData = [
            'name'         => 'SampleOriginalProjectName',
            'description'  => 'Code-monkey development group presents'
        ];

        $createResponse = $this->postJson('/api/v1/projects/create', $createData, $headers);

        $data = [
            'id' => $createResponse->json('res.id')
        ];

        $expectedFields = [
            'message'
        ];

        $response = $this->postJson('/api/v1/projects/remove', $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_Edit_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        $createData = [
            'name'         => 'SampleOriginalProjectName',
            'description'  => 'Code-monkey development group presents'
        ];

        $createResponse = $this->postJson('/api/v1/projects/create', $createData, $headers);

        $data = [
            'id' => $createResponse->json('res.id'),
                'name'         => 'SampleOriginalProjectNameButEdited',
                'description'  => 'Code-monkey development group presents with new description'
        ];

        $expectedFields = [
            "res" => [
                "id", "name", "description", 'created_at', 'updated_at', 'deleted_at'
            ]
        ];

        $response = $this->postJson('/api/v1/projects/edit', $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_List_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        $expectedFields = [
            '*' => [
                "id", "company_id", "name", "description", "deleted_at", "created_at", "updated_at"
            ]
        ];

        $response = $this->getJson('/api/v1/projects/list', $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_Show_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        $createData = [
            'name'         => 'SampleOriginalProjectName',
            'description'  => 'Code-monkey development group presents'
        ];

        $createResponse = $this->postJson('/api/v1/projects/create', $createData, $headers);

        $data = [
            'id' => $createResponse->json('res.id')
        ];

        $expectedFields = [
            "id", "company_id", "name", "description", 'created_at', 'updated_at'
        ];

        $response = $this->postJson('/api/v1/projects/show', $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }
}
