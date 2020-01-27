<?php

namespace Tests\Feature\ProjectReport;

use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Tests\Facades\IntervalFactory;
use Tests\Facades\ScreenshotFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;
use Tests\TestResponse;


/**
 * Class ListTest
 */
class ListTest extends TestCase
{
    private const URI = 'v1/project-report/list';

    private const INTERVALS_AMOUNT = 10;

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
    /**
     * @var array
     */
    private $requestData;

    /**
     * @param TestResponse $response
     * @return Collection
     */
    private function collectResponseProjects(TestResponse $response): Collection
    {
        return collect($response->json('projects'));
    }

    /**
     * @param TestResponse $response
     * @return Collection
     */
    private function collectResponseUsers(TestResponse $response): Collection
    {
        return $this->collectResponseProjects($response)->pluck('users')->collapse();
    }

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->intervals = IntervalFactory::withRandomRelations()->createMany(self::INTERVALS_AMOUNT);

        $this->intervals->each(function (TimeInterval $interval) {
            ScreenshotFactory::forInterval($interval)->create();
            $this->uids[] = $interval->user_id;
            $this->pids[] = $interval->task->project->id;
            $this->duration += Carbon::parse($interval->end_at)->diffInSeconds($interval->start_at);
        });


        $this->requestData = [
            'start_at' => $this->intervals->min('start_at'),
            'end_at' => Carbon::create($this->intervals->max('end_at'))->addMinute(),
            'uids' => $this->uids,
            'pids' => $this->pids
        ];
    }

    public function test_list(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->requestData);

        $response->assertOk();

        $users = $this->collectResponseUsers($response);

        $this->assertEquals($this->duration, $users->sum('tasks_time'));
        $this->assertEquals(count($this->uids), $users->count());

        //TODO change later
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertValidationError();
    }
}
