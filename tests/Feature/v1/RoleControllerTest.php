<?php

namespace Tests\Feature\v1;

use Artisan;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use RoleSeeder;
use Tests\TestCase;
use UsersTableSeeder;

/**
 * Class AuthControllerTest
 * @package Tests\Feature
 */
class RoleControllerTest extends TestCase
{
  use DatabaseMigrations;

  public function setUp()
  {
    parent::setUp();

    Artisan::call('db:seed', ['--class' => UsersTableSeeder::class]);
    Artisan::call('db:seed', ['--class' => RoleSeeder::class]);
  }

  public function test_Create_ExpectPass()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $data = [
      'name' => 'SampleRightRole'
    ];

    $response = $this->postJson('/api/v1/roles/create', $data, $headers);

    $response->assertStatus(200);
  }

  public function test_Create_ExpectFail_EmptyMail()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $data = [];

    $response = $this->postJson('/api/v1/roles/create', $data, $headers);

    $response->assertStatus(400);
  }

  public function test_Destroy_ExpectPass()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $data = [
      'name' => 'SampleRightRole'
    ];

    $this->postJson('/api/v1/roles/create', $data, $headers);
    $response = $this->postJson('/api/v1/roles/destroy', $data, $headers);

    $response->assertStatus(200);
  }

  public function test_Destroy_ExpectFail_EmptyName()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $data = [
      'name' => 'SampleRightRole'
    ];

    $this->postJson('/api/v1/roles/create', $data, $headers);
    $response = $this->postJson('/api/v1/roles/destroy', [], $headers);

    $response->assertStatus(400);
  }
}
