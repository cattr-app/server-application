<?php

namespace Tests\Feature\v1;

use Tests\TestCase;

class UserControllerTest extends TestCase
{
    public function test_Create_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $data = [
            "id"        => 42,
            "full_name" => "Captain John Doe",
            "email"     => "johndoe@example.com",
            "password"  => "SomeSuperSecretPasswordMoreThan8OSymbols",
            "role_id"   => 1,
            "active"    => true
        ];

        $expectedFields = [
            "res" => [
                "full_name", "email", "role_id", "active",
                "updated_at", "created_at", "id"
            ]
        ];

        $response = $this->postJson("/v1/users/create", $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_Destroy_ExpectFail_WrongId()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $data = [
            "id" => "SampleWrongId"
        ];

        $expectedFields = [
            "error", "reason"
        ];

        $response = $this->postJson("/v1/users/remove", $data, $headers);

        $response->assertStatus(400);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_Edit_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $data = [
            "id"        => 1,
            "role_id"   => 1,
            "full_name" => "Captain Admin Example com",
            "email"     => "admin@example.com",
            "active"    => true
        ];
        $expectedFields = [
            "res" => [
                "id","full_name","email",
                "url","company_id","level","payroll_access",
                "billing_access","avatar","screenshots_active","manual_time",
                "permanent_tasks","computer_time_popup","poor_time_popup",
                "blur_screenshots","web_and_app_monitoring","webcam_shots",
                "screenshots_interval","user_role_value","active",
                "deleted_at","created_at","updated_at","role_id","timezone",
                "attached_users"
            ]
        ];

        $response = $this->postJson("/v1/users/edit", $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_List_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];
        $expectedFields = [
            "*" => [
                "id","full_name","email","url","company_id",
                "level","payroll_access","billing_access","avatar","screenshots_active",
                "manual_time","permanent_tasks","computer_time_popup","poor_time_popup",
                "blur_screenshots","web_and_app_monitoring","webcam_shots",
                "screenshots_interval","user_role_value","active","deleted_at","created_at",
                "updated_at","role_id","timezone","attached_users"
            ]
        ];

        $response = $this->postJson("/v1/users/list", [], $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_Relations_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $expectedFields = [

        ];

        $response = $this->postJson("/v1/users/list", [], $headers);
        $response->assertStatus(200);

        $response->assertJsonStructure($expectedFields);
    }

    public function test_Show_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $data = [
            "id" => 1
        ];

        $response = $this->postJson("/v1/users/show", $data, $headers);
        $response->assertStatus(200);
    }

    public function test_Show_ExpectFail_EmptyId()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $response = $this->postJson("/v1/users/show", [], $headers);
        $response->assertStatus(400);
    }

    public function test_BulkEdit_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $data = [
            "users" => [
                [
                    "id"        => 1,
                    "full_name" => "Lol kek cheburek",
                    "email"     => "admeen@example.com",
                    "active"    => true,
                    "role_id"   => 1,
                ],
            ]
        ];

        $expectedFields = [
            "messages" => [
                "*" => [
                    "id", "full_name",
                    "email","url","company_id","level",
                    "payroll_access","billing_access","avatar",
                    "screenshots_active","manual_time","permanent_tasks",
                    "computer_time_popup","poor_time_popup","blur_screenshots",
                    "web_and_app_monitoring","webcam_shots",
                    "screenshots_interval","user_role_value","active",
                    "deleted_at","created_at","updated_at","role_id",
                    "timezone","attached_users"
                ]
            ]
        ];

        $response = $this->postJson("/v1/users/bulk-edit", $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }
}
