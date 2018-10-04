<?php

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp()
    {
        parent::setUp();

        Artisan::call('db:seed');
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

        $response = $this->postJson('/api/v1/projects/create', $data, $headers);

        $response->assertStatus(200);
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

        $response = $this->postJson('/api/v1/projects/destroy', $data, $headers);
        $response->assertStatus(200);
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

        $response = $this->postJson('/api/v1/projects/edit', $data, $headers);
        $response->assertStatus(200);
    }

    public function test_List_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        $response = $this->getJson('/api/v1/projects/list', $headers);
        $response->assertStatus(200);
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

        $response = $this->postJson('/api/v1/projects/show', $data, $headers);

        $response->assertStatus(200);
    }
}
