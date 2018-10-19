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
 *
 * @package Tests\Feature
 */
class ProjectRolesControllerTest extends TestCase
{
    public function test_BulkCreate_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        $data = [
            "relations" => [
                [
                    "project_id"    => 1,
                    "role_id"       => 1
                ],
                [
                    "project_id"    => 2,
                    "role_id"       => 1
                ]
            ]
        ];

        $expectedFields = [
            "messages" => [
                "*" => [
                    "project_id", "role_id", "updated_at", "created_at"
                ]
            ]
        ];

        $response = $this->postJson("/api/v1/projects-roles/bulk-create", $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_BulkDestroy_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        $data = [
            "relations" => [
                [
                    "project_id"    => 1,
                    "role_id"       => 1
                ]
            ]
        ];

        $expectedFields = [
            "messages" => [
                "*" => [
                    "message"
                ]
            ]
        ];

        $this->postJson("/api/v1/projects-roles/bulk-create", $data, $headers);
        $response = $this->postJson("/api/v1/projects-roles/bulk-remove", $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_Create_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        $data = [
            "project_id"    => 1,
            "role_id"       => 1,
        ];

        $expectedFields = [
            "*" => [
                "project_id", "role_id", "updated_at", "created_at", "id"
            ]
        ];

        $response = $this->postJson("/api/v1/projects-roles/create", $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_Destroy_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        $data = [
            "project_id"    => 1,
            "role_id"       => 1,
        ];

        $expectedFields = ["message"];

        $this->postJson("/api/v1/projects-roles/create", $data, $headers);
        $response = $this->postJson("/api/v1/projects-roles/remove", $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_List_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        $response = $this->postJson("/api/v1/project-roles/list", [], $headers);
        $response->assertStatus(200);
    }
}
