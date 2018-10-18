<?php

namespace Tests\Feature\v1;

use Tests\TestCase;

class RelationsUsersControllerTest extends TestCase
{
    public function test_List_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $expectedFields = [
            "*" => [
                "user_id", "attached_user_id", "created_at", "updated_at"
            ]
        ];

        $response = $this->postJson("/api/v1/attached-users/list", [], $headers);
        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_BulkCreate_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $data = [
            "user_id"           => 1,
            "attached_user_id"  => 1
        ];

        $expectedFields = [
            "*" => [
                "user_id", "attached_user_id", "updated_at", "created_at", "id"
            ]
        ];

        $response = $this->postJson("/api/v1/attached-users/create", $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_BulkDestroy_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $data = [
            "user_id"           => 1,
            "attached_user_id"  => 1
        ];

        $response = $this->postJson("/api/v1/attached-users/remove", $data, $headers);

        // @todo: check is test right
        $response->assertJsonStructure();
        // $response->assertStatus(404);
    }

    public function test_Create_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $data = [
            "user_id"           => 1,
            "attached_user_id"  => 1
        ];

        $response = $this->postJson("/api/v1/attached-users/create", $data, $headers);
        $response->assertStatus(200);
    }

    public function test_Destroy_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $data = [
            "user_id"           => 1,
            "attached_user_id"  => 1
        ];

        $response = $this->postJson("/api/v1/attached-users/remove", $data, $headers);

        // @todo: check is test right
        $this->markTestSkipped('Not finished yet.');
        $response->assertStatus(404);
    }
}
