<?php

namespace Tests\Feature\v1;

use Artisan;
use DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RoleSeeder;
use Tests\TestCase;
use AdminTableSeeder;

/**
 * Class AuthControllerTest
 * @package Tests\Feature
 */
class RoleControllerTest extends TestCase
{
    public function test_Create_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $data = [
            "name" => "SampleRightRole"
        ];

        $expectedFields = [
            "res" => [
                "name", "updated_at", "created_at", "id"
            ]
        ];

        $expectedJson = [
            "res" => [
                "name" => "SampleRightRole"
            ]
        ];

        $response = $this->postJson("/v1/roles/create", $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
        $response->assertJson($expectedJson);
    }

    public function test_Create_ExpectFail_EmptyMail()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $data = [];

        $expectedFields = [
            "error", "reason" => [
                "name"
            ]
        ];

        $expectedJson = [
            "error" => "Validation fail",
            "reason" => [
                "name" => ["The name field is required."]
            ]
        ];

        $response = $this->postJson("/v1/roles/create", $data, $headers);

        $response->assertStatus(400);
        $response->assertJsonStructure($expectedFields);
        $response->assertJson($expectedJson);
    }

    public function test_Destroy_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $createData = [
            "name" => "SampleRightRole"
        ];

        $roleId = $this->postJson("/v1/roles/create", $createData, $headers)
            ->json("res")["id"];

        $data = [
            "id" => $roleId
        ];

        $expectedFields = [
            "message"
        ];

        $expectedJson = [
            "message" => "Item has been removed"
        ];

        $response = $this->postJson("/v1/roles/remove", $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
        $response->assertJson($expectedJson);
    }

    public function test_Destroy_ExpectFail_EmptyId()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $data = [
            "name" => "SampleRightRole"
        ];

        $expectedFields = [
            "error", "reason"
        ];

        $expectedJson = [
            "error" => "Validation fail",
            "reason" => "Id invalid"
        ];

        $this->postJson("/v1/roles/create", $data, $headers);
        $response = $this->postJson("/v1/roles/remove", [], $headers);

        $response->assertStatus(400);
        $response->assertJsonStructure($expectedFields);
        $response->assertJson($expectedJson);
    }

    public function test_Edit_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $createRoleData = [
            "name" => "SampleRightRole"
        ];

        $expectedFields = [
            "res" => [
                "id", "name", "deleted_at", "created_at", "updated_at"
            ]
        ];

        $expectedJson = [
            "res" => [
                "name" => "YetAnotherRoleName",
                "deleted_at" => null
            ]
        ];

        $roleId = $this->postJson("/v1/roles/create", $createRoleData, $headers)
            ->json("res")["id"];

        $editRoleData = [
            "id"    => $roleId,
            "name"  => "YetAnotherRoleName"
        ];

        $response = $this->postJson("/v1/roles/edit", $editRoleData, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
        $response->assertJson($expectedJson);
    }

    public function test_Edit_ExpectFail_EmptyId()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $editRoleData = [
            "name"  => "YetAnotherRoleName"
        ];

        $expectedFields = [
            "error", "reason" => [
                "id"
            ]
        ];

        $expectedJson = [
            "error" => "Validation fail",
            "reason" => [
                "id" => ["The id field is required."]
            ]
        ];

        $response = $this->postJson("/v1/roles/edit", $editRoleData, $headers);

        $response->assertStatus(400);
        $response->assertJsonStructure($expectedFields);
        $response->assertJson($expectedJson);
    }

    public function test_Edit_ExpectFail_EmptyName()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $expectedFields = [
            "error", "reason" => [
                "name"
            ]
        ];

        $expectedJson = [
            "error" => "Validation fail",
            "reason" => [
                "name" => [
                    "The name field is required."
                ]
            ]
        ];

        $createRoleData = [
            "name" => "SampleRightRole"
        ];

        $roleId = $this->postJson("/v1/roles/create", $createRoleData, $headers)
            ->json("res")["id"];

        $editRoleData = [
            "id"    => $roleId
        ];

        $response = $this->postJson("/v1/roles/edit", $editRoleData, $headers);

        $response->assertStatus(400);
        $response->assertJsonStructure($expectedFields);
        $response->assertJson($expectedJson);
    }

    public function test_Show_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $expectedFields = [
            "id", "name", "deleted_at", "created_at", "updated_at"
        ];

        $expectedJson = [
            "name" => "SampleRightRole",
            "deleted_at" => null
        ];

        $createRoleData = [
            "name" => "SampleRightRole"
        ];

        $roleId = $this->postJson("/v1/roles/create", $createRoleData, $headers)
            ->json("res")["id"];

        $showRoleData = [
            "id"    => $roleId,
        ];

        $response = $this->postJson("/v1/roles/show", $showRoleData, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
        $response->assertJson($expectedJson);
    }

    public function test_Show_ExpectFail_EmptyId()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $expectedFields = [
            "error", "reason"
        ];

        $expectedJson = [
            "error"     => "Validation fail",
            "reason"    => "Id invalid"
        ];

        $response = $this->postJson("/v1/roles/show", [], $headers);

        $response->assertStatus(400);
        $response->assertJsonStructure($expectedFields);
        $response->assertJson($expectedJson);
    }
}
