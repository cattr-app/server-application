<?php

namespace Tests\Feature\Webcam;

use App\Enums\WebcamState;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Storage;
use Tests\Facades\IntervalFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class CreateIntervalWithWebcamTest extends TestCase
{

    private const URI = 'time-intervals/create';

    private User $admin;
    private array $intervalData;

    protected function setUp(): void
    {

        parent::setUp();

        Storage::fake();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();

        $task = TaskFactory::forUser($this->admin)->create();
        $task->project->webcam_state = WebcamState::REQUIRED;
        $task->project->save();

        $this->intervalData = IntervalFactory::createRandomModelData();
        $this->intervalData['task_id'] = $task->id;
        $this->intervalData['user_id'] = $this->admin->id;

    }

    public function test_create_interval_with_webcam_screenshot(): void
    {

        $this->intervalData['webcam_screenshot'] = UploadedFile::fake()->image('webcam.jpg');

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->intervalData);

        $response->assertOk();

    }

    public function test_create_interval_without_webcam_when_optional(): void
    {

        $task = TaskFactory::forUser($this->admin)->create();
        $task->project->webcam_state = WebcamState::OPTIONAL;
        $task->project->save();

        $data = IntervalFactory::createRandomModelData();
        $data['task_id'] = $task->id;
        $data['user_id'] = $this->admin->id;

        $response = $this->actingAs($this->admin)->postJson(self::URI, $data);

        $response->assertOk();

    }

    public function test_create_interval_with_webcam_when_forbidden_ignores_file(): void
    {

        $task = TaskFactory::forUser($this->admin)->create();
        $task->project->webcam_state = WebcamState::FORBIDDEN;
        $task->project->save();

        $data = IntervalFactory::createRandomModelData();
        $data['task_id'] = $task->id;
        $data['user_id'] = $this->admin->id;
        $data['webcam_screenshot'] = UploadedFile::fake()->image('webcam.jpg');

        $response = $this->actingAs($this->admin)->postJson(self::URI, $data);

        $response->assertOk();

    }

    public function test_unauthorized(): void
    {

        $response = $this->postJson(self::URI);

        $response->assertUnauthorized();

    }

}
