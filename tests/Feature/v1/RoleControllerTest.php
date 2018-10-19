<?php

namespace Tests\Feature\v1;

use Artisan;
use DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RoleSeeder;
use Tests\TestCase;
use UsersTableSeeder;

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

        $response = $this->postJson("/api/v1/roles/create", $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
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

        $response = $this->postJson("/api/v1/roles/create", $data, $headers);

        $response->assertStatus(400);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_Destroy_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $createData = [
            "name" => "SampleRightRole"
        ];

        $roleId = $this->postJson("/api/v1/roles/create", $createData, $headers)
            ->json("res")["id"];

        $data = [
            "id" => $roleId
        ];

        $expectedFields = [
            "message"
        ];

        $response = $this->postJson("/api/v1/roles/remove", $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
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

        $this->postJson("/api/v1/roles/create", $data, $headers);
        $response = $this->postJson("/api/v1/roles/remove", [], $headers);

        $response->assertStatus(400);
        $response->assertJsonStructure($expectedFields);
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

        $roleId = $this->postJson("/api/v1/roles/create", $createRoleData, $headers)
            ->json("res")["id"];

        $editRoleData = [
            "id"    => $roleId,
            "name"  => "YetAnotherRoleName"
        ];

        $response = $this->postJson("/api/v1/roles/edit", $editRoleData, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
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

        $response = $this->postJson("/api/v1/roles/edit", $editRoleData, $headers);

        $response->assertStatus(400);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_Edit_ExpectFail_EmptyName()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $createRoleData = [
            "name" => "SampleRightRole"
        ];

        $roleId = $this->postJson("/api/v1/roles/create", $createRoleData, $headers)
            ->json("res")["id"];

        $editRoleData = [
            "id"    => $roleId
        ];

        $response = $this->postJson("/api/v1/roles/edit", $editRoleData, $headers);
        $response->assertStatus(400);
    }

    public function test_Show_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $createRoleData = [
            "name" => "SampleRightRole"
        ];

        $roleId = $this->postJson("/api/v1/roles/create", $createRoleData, $headers)
            ->json("res")["id"];

        $showRoleData = [
            "id"    => $roleId,
        ];

        $response = $this->postJson("/api/v1/roles/show", $showRoleData, $headers);

        $response->assertStatus(200);
    }

    public function test_Show_ExpectFail_EmptyId()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $response = $this->postJson("/api/v1/roles/show", [], $headers);

        $response->assertStatus(400);
    }
}
