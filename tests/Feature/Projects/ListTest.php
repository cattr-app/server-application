<?php

namespace Tests\Feature\Projects;

use App\Models\Project;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;
use App\User;
use Tests\TestCase;

/**
 * Class ListTest
 * @package Tests\Feature\Projects
 */
class ListTest extends TestCase
{
    private const URI = 'v1/projects/list';

    private const PROJECTS_AMOUNT = 10;
    private const IS_PAGINATE = 1;
    private const PAGINATE_LIMIT = 5;

    /**
     * @var User
     */
    private $admin;
    private $commonUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->commonUser = UserFactory::withTokens()->asUser()->create();

        ProjectFactory::createMany(self::PROJECTS_AMOUNT);
    }

    public function test_list()
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertOk();
        $response->assertJson(Project::all()->toArray());
    }

    public function test_paginate_list()
    {
        for($i = 1; $i < 3; $i++) {
            $url = self::URI . '?' . 'paginate=' . self::IS_PAGINATE . '&perPage=' . self::PAGINATE_LIMIT . '&page=' . $i;
            $response = $this->actingAs($this->admin)->get($url);

            $paginationData = $response->json();

            $response->assertOk();

            $this->assertEquals($i, $paginationData['current_page']);
            $this->assertEquals(self::PAGINATE_LIMIT, count($paginationData['data']));

            sleep(2);
        }
    }

    public function test_unauthorized()
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_common_user()
    {
        $response = $this->actingAs($this->commonUser)->getJson(self::URI);

        $response->assertOk();
        $response->assertJson([]);
    }
}
