<?php

namespace Tests\Feature\ProjectReport;

use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;
use Tests\TestResponse;

class ListTest extends TestCase
{
    private const URI = '/api/report/project';

    private const INTERVALS_AMOUNT = 10;

    private User $admin;

    private array $pids;
    private array $uids;

    private Collection $intervals;

    private int $duration = 0;
    private array $requestData;

    private function collectResponseProjects(TestResponse $response): Collection
    {
        return collect($response->json('data'));
    }

    private function collectResponseUsers(TestResponse $response): Collection
    {
        return $this->collectResponseProjects($response)->pluck('users')->collapse();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->intervals = IntervalFactory::withRandomRelations()->createMany(self::INTERVALS_AMOUNT);

        $this->intervals->each(function (TimeInterval $interval) {
            $this->uids[] = $interval->user_id;
            $this->pids[] = $interval->task->project->id;
            $this->duration += (int) Carbon::parse($interval->end_at)->diffInSeconds($interval->start_at, true);
        });


        $this->requestData = [
            'start_at' => Carbon::parse($this->intervals->min('start_at'))->startOfDay()->toDateTimeString(),
            'end_at' => Carbon::parse($this->intervals->max('end_at'))->endOfDay()->toDateTimeString(),
            'users' => array_values(array_unique($this->uids)),
            'projects' => array_values(array_unique($this->pids))
        ];
    }

    public function test_list(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->requestData);

        $response->assertOk();

        $users = $this->collectResponseUsers($response);

        $this->assertSame($this->duration, (int) $users->sum('time'));
        $this->assertEqualsCanonicalizing(array_unique($this->uids), $users->pluck('id')->all());
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI, $this->requestData);

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertStatus(self::HTTP_UNPROCESSABLE_ENTITY);
    }
}
