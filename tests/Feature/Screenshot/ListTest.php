<?php

namespace Tests\Feature\Screenshot;

use App\Models\Screenshot;
use App\User;
use Tests\Facades\ScreenshotFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ListTest extends TestCase
{
    private const URI = 'v1/screenshots/list';
    private const IS_PAGINATE = 1;
    private const PAGINATE_LIMIT = 5;
    private const COUNT_SCREENSHOTS = 10;

    /**
     * @var User
     */
    private $admin;

    /**
     * @var User
     */
    private $commonUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = UserFactory::asAdmin()->withTokens()->create();
        $this->commonUser = UserFactory::withTokens()->asUser()->create();
        ScreenshotFactory::withRandomRelations()->createMany(self::COUNT_SCREENSHOTS);
    }

    public function test_list()
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertOk();
        $response->assertJson(Screenshot::get()->toArray());
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

    public function test_common_list()
    {
        $response = $this->actingAs($this->commonUser)->postJson(self::URI);

        $response->assertOk();
        $response->assertJson(Screenshot::all()->toArray());
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
