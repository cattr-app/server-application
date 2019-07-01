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
                    "project_id"    => 777,
                    "role_id"       => 5981
                ]
            ]
        ];

        $expectedJson = [
            "messages" =>[
                [
                    "project_id" => 1,
                    "role_id"    => 1
                ],
                [
                    "error" => "Validation fail",
                    "reason" => [
                        "project_id" => ["The selected project id is invalid."],
                        "role_id"    => ["The selected role id is invalid."]
                    ],
                    "code" => 400
                ]
            ]
        ];

        $response = $this->postJson("/v1/projects-roles/bulk-create", $data, $headers);

        $response->assertStatus(200);
        $response->assertJson($expectedJson);
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
                ],
                [
                    "project_id"    => 777,
                    "role_id"       => 5981
                ]
            ]
        ];

        $expectedJson = [
            "messages" => [
                [
                    "message" => "Item has been removed"
                ],
                [
                    "error"     => "Validation fail",
                    "reason"    => [
                        "project_id" => ["The selected project id is invalid."],
                        "role_id"    => ["The selected role id is invalid."]
                    ],
                    "code" => 400
                ]
            ]
        ];

        $this->postJson("/v1/projects-roles/bulk-create", $data, $headers);
        $response = $this->postJson("/v1/projects-roles/bulk-remove", $data, $headers);

        $response->assertStatus(200);
        $response->assertJson($expectedJson);
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

        $expectedJson = [
            [
                "project_id" => 1,
                "role_id"    => 1,
            ]
        ];

        $response = $this->postJson("/v1/projects-roles/create", $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
        $response->assertJson($expectedJson);
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

        $expectedJson = [
            "message" => "Item has been removed"
        ];

        $this->postJson("/v1/projects-roles/create", $data, $headers);
        $response = $this->postJson("/v1/projects-roles/remove", $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
        $response->assertJson($expectedJson);
    }

    public function test_List_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        $createData = [
            "project_id"    => 1,
            "role_id"       => 1,
        ];

        $expectedFields = [
            "*" => [
                "project_id", "role_id", "created_at", "updated_at"
            ]
        ];

        $expectedJson = [
            [
                "project_id" => 1,
                "role_id"    => 1
            ]
        ];

        $this->postJson("/v1/projects-roles/create", $createData, $headers);

        $response = $this->postJson("/v1/projects-roles/list", [], $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
        $response->assertJson($expectedJson);
    }
}
