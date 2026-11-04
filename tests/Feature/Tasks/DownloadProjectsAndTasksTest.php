<?php

namespace Tests\Feature\Tasks;

use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class DownloadProjectsAndTasksTest extends TestCase
{
    private const URI = 'offline-sync/download-projects-and-tasks';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();
    }

    public function test_download_headers(): void
    {
        $response = $this->actingAs($this->admin)->get(self::URI . '/' . $this->admin->id);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/octet-stream');
        $response->assertHeader('Content-Disposition', 'attachment; filename=ProjectsAndTasks.cattr');
        $this->assertFalse($response->headers->has('0'));
        $this->assertFalse($response->headers->has('1'));
    }

    public function test_unauthorized(): void
    {
        $response = $this->get(self::URI . '/' . $this->admin->id);

        $response->assertUnauthorized();
    }
}
