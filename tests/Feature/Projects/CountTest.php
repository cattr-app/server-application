<?php

namespace Tests\Feature\Projects;

use App\Models\Project;
use Tests\Factories\ProjectFactory;
use Tests\Factories\UserFactory;
use App\User;
use Tests\TestCase;

/**
 * Class CountTest
 * @package Tests\Feature\Projects
 */
class CountTest extends TestCase
{
    private const URI = 'v1/projects/count';

    private const PROJECTS_AMOUNT = 10;

    /**
     * @var User
     */
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = app(UserFactory::class)
            ->withTokens()
            ->asAdmin()
            ->create();

        app(ProjectFactory::class)->createMany(self::PROJECTS_AMOUNT);
    }

    public function test_count()
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertApiSuccess();
        $response->assertJson(['total' => Project::count()]);
    }

    public function test_unauthorized()
    {
        $response = $this->getJson(self::URI);

        $response->assertApiError(401);
    }
}
