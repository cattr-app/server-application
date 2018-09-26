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

    $imageBase64 = 'R0lGODlhEAAOALMAAOazToeHh0tLS/7LZv/0jvb29t/f3//Ub/
/ge8WSLf/rhf/3kdbW1mxsbP//mf///yH5BAAAAAAALAAAAAAQAA4AAARe8L1Ekyky67QZ1hLnjM5UUde0ECwLJoExKcpp
V0aCcGCmTIHEIUEqjgaORCMxIC6e0CcguWw6aFjsVMkkIr7g77ZKPJjPZqIyd7sJAgVGoEGv2xsBxqNgYPj/gAwXEQA7';

    $data = [
      'time_interval_id'  => 300,
      'screenshot'        => $imageBase64
    ];

    $response = $this->postJson('/api/v1/screenshots/create', $data, $headers);

    $response->assertStatus(200);
  }

  public function test_Destroy_ExpectPass()
  {
    $headers = [
      'Authorization' => 'Bearer ' . $this->getAdminToken()
    ];

    $screenshotData = [
      'time_interval_id'  => 1,
      'screenshot'        => `screnshot_invaleeeeed`
    ];

    /**
     * Upload screenshot and get ID
     */
    $id = $this->postJson('/api/v1/screenshots/create', $screenshotData, $headers);

    $deleteScreenshotData = [
      'id' => $id
    ];

    $response = $this->postJson('/api/v1/screenshots/destroy', $deleteScreenshotData, $headers);

    $response->assertStatus(200);
  }
}
