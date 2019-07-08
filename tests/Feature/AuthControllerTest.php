<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class AuthControllerTest
 * @package Tests\Feature
 */
class AuthControllerTest extends TestCase
{

    public function test_Login_ExpectPass()
    {
        $auth = [
            "login"     => "admin@example.com",
            "password"  => "admin"
        ];

        $expectedFields = [
            "access_token", "token_type", "expires_in", "user"
        ];

        $response = $this->postJson("auth/login", $auth);
        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_Login_ExpectFail_NoPassword()
    {
        $auth = [
            "login" => "admin@example.com",
        ];

        $expectedFields = [
            "error"
        ];

        $response = $this->postJson("auth/login", $auth);

        $response->assertStatus(401);
        $response->assertJsonStructure($expectedFields);


    }

    public function test_Login_ExpectFail_NoLogin()
    {
        $auth = [
            "password" => "admin",
        ];

        $expectedFields = [
            "error"
        ];

        $response = $this->postJson("/auth/login", $auth);

        $response->assertStatus(401);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_Login_ExpectFail_NoData()
    {
        $auth = [];

        $expectedFields = [
            "error"
        ];

        $response = $this->postJson("/auth/login", $auth);

        $response->assertStatus(401);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_Login_ExpectFail_WrongData()
    {
        $auth = [
            "login"    => "admin@example.com",
            "password" => "inwalidpassword",
        ];

        $expectedFields = [
            "error"
        ];

        $response = $this->postJson("/auth/login", $auth);

        $response->assertStatus(401);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_Me_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $expectedFields = [
            "id","full_name","email","url",
            "company_id","level","payroll_access","billing_access",
            "avatar","screenshots_active","manual_time","permanent_tasks",
            "computer_time_popup","poor_time_popup","blur_screenshots",
            "web_and_app_monitoring","webcam_shots","screenshots_interval",
            "user_role_value","active","deleted_at","created_at","updated_at",
            "role_id","timezone"
        ];

        $response = $this->getJson("/auth/me", $headers);
        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_Me_ExpectFail_WrongJWT()
    {
        $headers = [
            "Authorization" => "Bearer samplewrongjwt"
        ];

        $expectedFields = [
            "error", "reason"
        ];

        $response = $this->getJson("/auth/me", $headers);

        $response->assertStatus(403);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_Refresh_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $expectedFields = [
            "access_token", "token_type", "expires_in", "user"
        ];

        $response = $this->postJson("/auth/refresh", [], $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }
}
