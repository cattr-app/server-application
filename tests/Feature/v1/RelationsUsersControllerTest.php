<?php

namespace Tests\Feature\v1;

use Artisan;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class RelationsUsersControllerTest extends TestCase
{
  use DatabaseMigrations;

  public function setUp()
  {
    parent::setUp();

    Artisan::call('db:seed');
  }

  public function test_List_ExpectPass()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $response = $this->postJson('/api/v1/attached-users/list', [], $headers);
    $response->assertStatus(200);
  }

  public function test_BulkCreate_ExpectPass()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $data = [
        'user_id' => 1,
        'attached_user_id' => 1
    ];

    $response = $this->postJson('/api/v1/attached-users/create', $data, $headers);

    $response->assertStatus(200);
  }

  public function test_BulkDestroy_ExpectPass()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $data = [];

    $response = $this->postJson('/api/v1/attached-users/destroy', [], $headers);
    $response->assertStatus(200);
  }

  public function test_Create_ExpectPass()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $data = [
      'user_id' => 1,
      'attached_user_id' => 1
    ];

    $response = $this->postJson('/api/v1/attached-users/create', $data, $headers);
    $response->assertStatus(200);
  }

  public function test_Destroy_ExpectPass()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $data = [];

    $response = $this->postJson('/api/v1/attached-users/destroy', $data, $headers);
    $response->assertStatus(200);
  }
}
