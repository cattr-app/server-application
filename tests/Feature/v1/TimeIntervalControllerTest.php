<?php

namespace Tests\Feature\v1;

use Tests\TestCase;

class TimeIntervalControllerTest extends TestCase
{
    public function test_Create_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $data = [
            "task_id"   => 1,
            "user_id"   => 1,
            "start_at"  => "2017-05-11T00:00:00+08:00",
            "end_at"    => "2017-05-11T00:00:00+08:00"
        ];

        $expectedFields = [
            "interval" => [
                "id", "task_id", "start_at", "end_at",
                "created_at", "updated_at", "count_mouse", "count_keyboard",
                "user_id"
            ]

        ];

        $response = $this->postJson("/api/v1/time-intervals/create", $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_Destroy_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $createData = [
            "task_id"   => 1,
            "user_id"   => 1,
            "start_at"  => "2017-05-11T00:00:00+08:00",
            "end_at"    => "2017-05-11T00:00:00+08:00"
        ];

        $createResponse = $this->postJson("/api/v1/time-intervals/create", $createData, $headers);

        $data = [
            "id" => $createResponse->json("interval.id")
        ];

        $expectedFields = [
            "message"
        ];

        $response = $this->postJson("/api/v1/time-intervals/remove", $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_Edit_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $data = [
            "id"        => 1,
            "task_id"   => 1,
            "user_id"   => 1,
            "start_at"  => "2018-10-03T12:00:00+02:00",
            "end_at"    => "2018-10-03T12:00:00+02:00"
        ];

        $expectedFields = [
            "res" => [
                "id", "task_id", "start_at", "end_at", "created_at",
                "updated_at", "deleted_at", "count_mouse", "count_keyboard",
                "user_id"
            ]
        ];

        $response = $this->postJson("/api/v1/time-intervals/edit", $data, $headers);

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
                "id", "task_id", "start_at", "end_at", "created_at", "updated_at",
                "deleted_at", "count_mouse", "count_keyboard", "user_id"
            ]
        ];

        $response = $this->postJson("/api/v1/time-intervals/list", [], $headers);

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

        $expectedFields = [
            "id", "task_id", "start_at", "end_at", "created_at", "updated_at",
            "deleted_at", "count_mouse", "count_keyboard", "user_id"
        ];

        $response = $this->postJson("/api/v1/time-intervals/show", $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }
}
