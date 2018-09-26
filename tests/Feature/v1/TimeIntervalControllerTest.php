<?php

namespace Tests\Feature\v1;

use Artisan;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class TimeIntervalControllerTest extends TestCase
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
      'task_id'   => 42,
      'user_id'   => 1,
      'start_at'  => '2011-07-14T19:43:37+0100',
      'end_at'    => '2012-07-14T19:43:37+0100'
    ];

    $response = $this->postJson('/api/v1/time-intervals/create', $data, $headers);
    $response->assertStatus(200);
  }

  public function test_Destroy_ExpectPass()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $response = $this->postJson('/api/v1/time-intervals/destroy', [], $headers);
    $response->assertStatus(200);
  }

  public function test_Edit_ExpectPass()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $response = $this->postJson('/api/v1/time-intervals/edit', [], $headers);
    $response->assertStatus(200);
  }

  public function test_List_ExpectPass()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $response = $this->postJson('/api/v1/time-intervals/list', [], $headers);
    $response->assertStatus(200);
  }

  public function test_Show_ExpectPass()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $response = $this->postJson('/api/v1/time-intervals/show', [], $headers);

    $response->assertStatus(200);
  }
}
