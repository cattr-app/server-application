<?php

namespace Tests\Feature\v1;

use Artisan;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class RuleController  extends TestCase
{
    use DatabaseMigrations;

    public function setUp()
    {
        parent::setUp();

        Artisan::call('db:seed');
    }

    public function test_Actions_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        $response = $this->getJson('/api/v1/rules/actions', $headers);
        $response->assertStatus(200);
    }

    public function test_Edit_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        $data = [
            'role_id' => 1,
            'object' => 'name',
            'action' => 'des troy',
            'allow'  => true
        ];

        $response = $this->postJson('/api/v1/projects-users/edit', $data, $headers);

        $response->assertStatus(200);
    }

    public function test_BulkEdit_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        $data = [
            'role_id' => 1,
            'object' => 'name',
            'action' => 'des troy',
            'allow'  => true
        ];

        $response = $this->postJson('/api/v1/projects-users/bulk-edit', $data, $headers);

        $response->assertStatus(200);
    }
}
