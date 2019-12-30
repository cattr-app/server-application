<?php


namespace Tests\Feature\Screenshot;


use App\Models\Screenshot;
use App\User;
use Tests\Facades\ProjectFactory;
use Tests\Facades\ScreenshotFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    const URI = '/v1/screenshots/dashboard';

    /**
     * @var User
     */
    private $admin;

    /**
     * @var User
     */
    private $commonUser;

    /**
     * @var Screenshot
     */
    private $screenshot;
    /**
     * @var \App\Models\Project
     */
    private $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();
        $this->commonUser = UserFactory::withTokens()->asUser()->create();
        $this->screenshot = ScreenshotFactory::create();
        $this->project = ProjectFactory::create();

    }

    public function test_dashboard()
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, [
            'id' => $this->screenshot->id,
            "time_interval_id" => $this->screenshot->time_interval_id,
          "user_id" => $this->admin->id,
           "project_id" => $this->project->id,
          "path" => $this->screenshot->path,
           "created_at" => $this->screenshot->created_at,
           "updated_at" => $this->screenshot->updated_at
        ]);

        $response->assertOk();
    }
}
