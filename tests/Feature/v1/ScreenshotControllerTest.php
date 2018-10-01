<?php

namespace Tests\Feature\v1;

use Artisan;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ScreenshotControllerTest extends TestCase
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
      'time_interval_id'  => 1,
      'screenshot'        => ''
    ];

    $response = $this->postJson('/api/v1/screenshots/create', $data, $headers);
    echo 'WRONG: ' . var_export($response->content(), true);

    $response->assertStatus(200);
  }

  public function test_Destroy_ExpectPass()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $screenshotData = "{
  \"screenshot\": ```sample binary data```,
  \"time_interval_id\": 1
}";


    /**
     * Upload screenshot and get ID
     */
    $id = $this->postJson('/api/v1/screenshots/create', $screenshotData, $headers);

    $deleteScreenshotData = [
      'id' => $id
    ];

    $response = $this->postJson('/api/v1/screenshots/destroy', $deleteScreenshotData, $headers);
    echo 'WRONG: ' . var_export($response, true);

    $response->assertStatus(200);
  }
}
