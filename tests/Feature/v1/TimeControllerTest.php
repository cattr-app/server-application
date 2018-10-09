<?php

namespace Tests\Feature\v1;

use Artisan;
use DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class TimeControllerTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp()
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => DatabaseSeeder::class]);
    }

    public function test_Project_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        $data = [
            'project_id' => '1',
        ];

        /**
         * Support GET and POST requests
         */
        $getResponse = $this->getJson('/api/v1/time/project?project_id=1', $headers);
        $getResponse->assertStatus(200);

        $postResponse = $this->postJson('/api/v1/time/project', $data, $headers);
        $postResponse->assertStatus(200);
    }

    public function test_Task_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        $data = [
            'task_id' => '1',
        ];

        /**
         * Support GET and POST requests
         */
        $getResponse = $this->getJson('/api/v1/time/task?task_id=1', $headers);
        $getResponse->assertStatus(200);

        $postResponse = $this->postJson('/api/v1/time/task', $data, $headers);
        $postResponse->assertStatus(200);
    }

    public function test_TaskUser_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        $data = [
            'task_id' => '1',
            'user_id' => '1',
        ];

        /**
         * Support GET and POST requests
         */
        $getResponse = $this->getJson('/api/v1/time/task-user?task_id=1&user_id=1', $headers);
        $getResponse->assertStatus(200);

        $postResponse = $this->postJson('/api/v1/time/task-user', $data, $headers);
        $postResponse->assertStatus(200);
    }

    public function test_Tasks_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        /**
         * Support GET and POST requests
         */
        $getResponse = $this->getJson('/api/v1/time/tasks', $headers);
        $getResponse->assertStatus(200);

        $postResponse = $this->postJson('/api/v1/time/tasks', [], $headers);
        $postResponse->assertStatus(200);
    }

    public function test_Total_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        /**
         * Support GET and POST requests
         */
        $getResponse = $this->getJson('/api/v1/time/total', $headers);
        $getResponse->assertStatus(200);

        $postResponse = $this->postJson('/api/v1/time/total', [], $headers);
        $postResponse->assertStatus(200);
    }

}
