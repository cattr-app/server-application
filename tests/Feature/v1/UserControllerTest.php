<?php

namespace Tests\Feature\v1;

use Artisan;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
  use DatabaseMigrations;

  public function setUp()
  {
    parent::setUp();

    Artisan::call('db:seed');
  }

  public function test_Create_ExpectPass()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $response = $this->postJson('/api/v1/users/create', [], $headers);
    $response->assertStatus(200);
  }

  public function test_Destroy_ExpectPass()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $response = $this->postJson('/api/v1/users/destroy', [], $headers);
    $response->assertStatus(200);
  }

  public function test_Edit_ExpectPass()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    /**
     * Customer ID 1 => admin@example.com
     */
    $data = [
      'id'        => 1,
      'full_name' => 'Captain Admin Example com',
      'email'     => 'admin@example.com',
      'active'    => true
    ];

    $response = $this->postJson('/api/v1/users/edit', $data, $headers);
    $response->assertStatus(200);
  }

  public function test_List_ExpectPass()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $response = $this->postJson('/api/v1/users/list', [], $headers);
    $response->assertStatus(200);
  }

  public function test_Relations_ExpectPass()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $response = $this->postJson('/api/v1/users/list', [], $headers);
    $response->assertStatus(200);
  }

  public function test_Show_ExpectPass()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $response = $this->postJson('/api/v1/users/show', [], $headers);
    $response->assertStatus(200);
  }

  public function test_BulkEdit_ExpectPass()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $data = [
      'users' => [
        [
          'id'        => 1,
          'full_name' => 'Lol kek cheburek',
          'email'     => 'admeen@example.com',
          'active'    => true
        ],
      ]
    ];

    $response = $this->postJson('/api/v1/users/bulk-edit', $data, $headers);
    $response->assertStatus(200);
  }
}
