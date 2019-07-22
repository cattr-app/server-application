<?php

namespace Tests\Feature\v1;

use Tests\TestCase;

class RuleController extends TestCase
{
    public function test_Actions_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $expectedFields = [
            "*" => [
                "object", "action", "name"
            ]
        ];

        $response = $this->getJson("/v1/rules/actions", $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_Edit_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $data = [
            "role_id"   => 2,
            "object"    => "projects",
            "action"    => "create",
            "allow"     =>  0
        ];

        $expectedFields = [
            "message"
        ];

        $response = $this->postJson("/v1/rules/edit", $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_BulkEdit_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $data = [
            "rules" => [
                [
                    "role_id"   => 2,
                    "object"    => "projects",
                    "action"    => "create",
                    "allow"     =>  0
                ],
                [
                    "role_id"   => 2,
                    "object"    => "projects",
                    "action"    => "list",
                    "allow"     => 0
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

        $response = $this->postJson("/v1/rules/bulk-edit", $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }
}
