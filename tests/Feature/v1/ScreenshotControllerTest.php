<?php

namespace Tests\Feature\v1;

use Artisan;
use DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ScreenshotControllerTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp()
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => DatabaseSeeder::class]);
    }

    public function test_Create_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        $data = [
            'time_interval_id'  => 1,
            'screenshot'        => '```binarydata```'
        ];

        $response = $this->postJson('/api/v1/screenshots/create', $data, $headers);
        echo 'Wrong: ' . var_export($response->content(), true);

        $response->assertStatus(200);
    }

    public function test_Destroy_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        $data = [
            'screenshot'        => 'samplebinarydata',
            'time_interval_id'  => '1'
        ];

        /**
         * Upload screenshot and get ID
         */
        $id = $this->post('/api/v1/screenshots/create', $data, $headers);

        $deleteScreenshotData = [
            'id' => $id
        ];

        $response = $this->post('/api/v1/screenshots/destroy', $deleteScreenshotData, $headers);
        $response->assertStatus(200);
    }
}
