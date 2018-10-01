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

    Artisan::call('db:seed');
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

    /**
     * Create role and get ID
     */
    $roleId = $this->postJson('/api/v1/roles/create', $data, $headers)
      ->json('res')['id'];

    $destroyData = [
      'id' => (string)$roleId
    ];

    $response = $this->postJson('/api/v1/roles/destroy', $destroyData, $headers);

    $response->assertStatus(200);
  }

  public function test_Destroy_ExpectFail_EmptyId()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $data = [
      'name' => 'SampleRightRole'
    ];


    $this->postJson('/api/v1/roles/create', $data, $headers);
    $response = $this->postJson('/api/v1/roles/remove', [], $headers);
    echo 'WRONG: ' . var_export($response->content(), true);

    $response->assertStatus(400);
  }

  public function test_Edit_ExpectPass()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $createRoleData = [
      'name' => 'SampleRightRole'
    ];

    $roleId = $this->postJson('/api/v1/roles/create', $createRoleData, $headers)
      ->json('res')['id'];

    $editRoleData = [
      'id'    => $roleId,
      'name'  => 'YetAnotherRoleName'
    ];

    $response = $this->postJson('/api/v1/roles/edit', $editRoleData, $headers);

    $response->assertStatus(200);
  }

  public function test_Edit_ExpectFail_EmptyId()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $editRoleData = [
      'name'  => 'YetAnotherRoleName'
    ];

    $response = $this->postJson('/api/v1/roles/edit', $editRoleData, $headers);

    $response->assertStatus(400);
  }

  public function test_Edit_ExpectFail_EmptyName()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $createRoleData = [
      'name' => 'SampleRightRole'
    ];

    $roleId = $this->postJson('/api/v1/roles/create', $createRoleData, $headers)
      ->json('res')['id'];

    $editRoleData = [
      'id'    => $roleId
    ];

    $response = $this->postJson('/api/v1/roles/edit', $editRoleData, $headers);

    $response->assertStatus(400);
  }

  public function test_Show_ExpectPass()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $createRoleData = [
      'name' => 'SampleRightRole'
    ];

    $roleId = $this->postJson('/api/v1/roles/create', $createRoleData, $headers)
      ->json('res')['id'];

    $showRoleData = [
      'id'    => $roleId,
    ];

    $response = $this->postJson('/api/v1/roles/show', $showRoleData, $headers);

    $response->assertStatus(200);
  }

  public function test_Show_ExpectFail_EmptyId()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $response = $this->postJson('/api/v1/roles/show', [], $headers);

    $response->assertStatus(400);
  }
}
