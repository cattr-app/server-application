<?php
namespace Tests\Feature\v1;

use Artisan;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use RoleSeeder;
use Tests\TestCase;
use UsersTableSeeder;

/**
 * Class AuthControllerTest
 * 
 * @package Tests\Feature
 */
class ProjectRolesControllerTest extends TestCase
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
            'name' => 'Sample'
        ];

        $response = $this->postJson('/api/v1/roles/create', $data, $headers);

        $response->assertStatus(200);
    }

    public function test_Destroy_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        $data = [
            'name' => 'Sample'
        ];

        $response = $this->postJson('/api/v1/roles/create', $data, $headers);
        $id = $response->json();


        $response->assertStatus(200);
    }

    public function test_List_ExpectPass()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAdminToken()
        ];

        $response = $this->postJson('/api/v1/roles/list', [], $headers);
        $response->assertStatus(200);
    }
}
