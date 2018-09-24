<?php

namespace Tests\Feature;

use Artisan;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use UsersTableSeeder;

/**
 * Class AuthControllerTest
 * @package Tests\Feature
 */
class AuthControllerTest extends TestCase
{
  use DatabaseMigrations;

  public function setUp()
  {
    parent::setUp();

    Artisan::call('db:seed', ['--class' => UsersTableSeeder::class]);
  }

  public function test_Login_ExpectPass()
  {
    $auth = [
      'login'     => 'admin@example.com',
      'password'  => 'admin'
    ];

    $response = $this->postJson('/api/auth/login', $auth);
    $response->assertStatus(200);
  }

  public function test_Login_ExpectFail_NoPassword()
  {
    $auth = [
      'login' => 'admin@example.com',
    ];

    $response = $this->postJson('/api/auth/login', $auth);

    $response->assertStatus(401);
  }

  public function test_Login_ExpectFail_NoLogin()
  {
    $auth = [
      'password' => 'admin',
    ];

    $response = $this->postJson('/api/auth/login', $auth);

    $response->assertStatus(401);
  }

  public function test_Login_ExpectFail_NoData()
  {
    $auth = [];

    $response = $this->postJson('/api/auth/login', $auth);

    $response->assertStatus(401);
  }
}
