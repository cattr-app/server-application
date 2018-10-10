<?php

namespace Tests\Feature\v1;

use Artisan;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RoleSeeder;
use Tests\TestCase;
use UsersTableSeeder;

class RolesControllerTest extends TestCase
{
    public function test_AllowedRules_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        /**
         * Use role from seed [root 1]
         */
        $data = [
            'id' => 1
        ];

        $response = $this->postJson('/api/v1/roles/allowed-rules', $data, $headers);

        $response->assertStatus(200);
        /*$response->assertJsonStructure([
            '*' => [
                'object' => [
                    'object', 'action', 'name'
                ]
            ]
        ]);*/
    }

    public function test_List_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        $response = $this->postJson('/api/v1/roles/list', [], $headers);
        $response->assertStatus(200);
    }

    public function test_Dashboard_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        $response = $this->postJson('/api/v1/roles/dashboard', [], $headers);
        $response->assertStatus(200);
    }
}
