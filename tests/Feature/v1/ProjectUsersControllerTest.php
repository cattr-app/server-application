<?php

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectUsersControllerTest extends TestCase
{
    public function test_BulkCreate_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        $data = [
            "relations" => [
                [
                    "project_id"  => 1,
                    "user_id"     => 1
                ]
            ]
        ];

        $expectedFields = [
            "messages" => [
                "*" => [
                        "project_id", "user_id", "updated_at", "created_at"
                    ]
            ]
        ];

        $expectedJson = [
            "messages" => [
                [
                    "project_id" => 1,
                    "user_id"    => 1,
                ]
            ]
        ];

        $response = $this->postJson("/api/v1/projects-users/bulk-create", $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
        $response->assertJson($expectedJson);
    }

    public function test_BulkDestroy_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $data = [
            "relations" => [
                [
                    "project_id"  => 1,
                    "user_id"     => 1
                ]
            ]
        ];

        $expectedFields = [
            "messages"
        ];

        $expectedJson = [
            "messages" => [
                [
                    "message" => "Item has been removed"
                ]
            ]
        ];

        $this->postJson("/api/v1/projects-users/bulk-create", $data, $headers);
        $response = $this->postJson("/api/v1/projects-users/bulk-remove", $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
        $response->assertJson($expectedJson);
    }

    public function test_Create_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $data = [
            "project_id"  => 1,
            "user_id"     => 1
        ];

        $expectedFields = [
            "*" => [
                "project_id", "user_id", "updated_at", "created_at"
            ]
        ];

        $expectedJson = [
            [
                "project_id" => 1,
                "user_id"    => 1,
            ]
        ];

        $response = $this->postJson("/api/v1/projects-users/create", $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
        $response->assertJson($expectedJson);
    }

    public function test_Destroy_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $data = [
            "project_id"  => 1,
            "user_id"     => 1
        ];

        $expectedFields = [
            "message"
        ];

        $this->postJson("/api/v1/projects-users/create", $data, $headers);
        $response = $this->postJson("/api/v1/projects-users/remove", $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }

    // @todo: check is right
    public function test_List_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $expectedFields = [
            "*" => [
                "project_id", "user_id", "updated_at", "created_at"
            ]
        ];

        $expectedJson = [
            [
                "project_id" => 1,
                "user_id"    => 1,
            ]
        ];

        $createData = [
            "project_id"  => 1,
            "user_id"     => 1
        ];

        $this->postJson("/api/v1/projects-users/create", $createData, $headers);

        $response = $this->getJson("/api/v1/projects-users/list", $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
        $response->assertJson($expectedJson);
    }
}
