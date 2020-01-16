<?php

namespace Tests\Feature\Statistic\ProjectReport;

use App\Models\Project;
use App\Models\Task;
use App\Models\TimeInterval;
use App\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\Facades\IntervalFactory;
use Tests\Facades\ScreenshotFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;
use function Clue\StreamFilter\fun;

class ListTest extends TestCase
{
    const COUNT = 10;
    const URI = 'v1/project-report/list';

    /**
     * @var User
     */
    private $admin;

    /**
     * @var array
     */
    private $pids;

    /**
     * @var array
     */
    private $uids;

    /**
     * @var Collection
     */
    private $intervals;
    /**
     * @var int
     */
    private $duration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->intervals = IntervalFactory::createMany(self::COUNT);

        /** @var TimeInterval $interval */
        foreach ($this->intervals as $interval) {
            ScreenshotFactory::setInterval($interval)->create();
            $this->uids[] = $interval->user->id;
            $this->pids[] = $interval->task->project->id;
            $this->duration += Carbon::parse($interval->end_at)->diffInSeconds($interval->start_at);
        }
    }

    public function test_list(): void
    {
        $requestData = [
            'start_at' => date(DATE_ISO8601, 0),
            'end_at' => date(DATE_ISO8601, time()),
            'uids' => $this->uids,
            'pids' => $this->pids
        ];

        $response = $this->actingAs($this->admin)->postJson(self::URI, $requestData);
        $response->assertOk();

        $duration = 0;

        foreach ($response->json()['projects'] as $projects) {
            foreach ($projects['users'] as $users) {
                $duration += $users['tasks_time'];
            }
        }

        $this->assertEquals($this->duration, $duration);
    }

    public function test_unauthorized()
    {
        $response = $this->getJson(self::URI);
        $response->assertUnauthorized();
    }

    public function test_without_params()
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);
        $response->assertValidationError();
    }
}
