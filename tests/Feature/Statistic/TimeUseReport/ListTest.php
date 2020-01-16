<?php

namespace Tests\Feature\Statistic\TimeUseReport;

use App\Models\TimeInterval;
use App\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ListTest extends TestCase
{
    const COUNT = 10;
    const URI = 'v1/time-use-report/list';
    /**
     * @var Collection
     */
    private $intervals;
    /**
     * @var array
     */
    private $uids;
    /**
     * @var User
     */
    private $admin;
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
            $this->duration += Carbon::parse($interval->end_at)->diffInSeconds($interval->start_at);
            $this->uids[] = $interval->user->id;
        }
    }

    public function test_list(): void
    {
        $requestData = [
            'start_at' => date(DATE_ISO8601, 0),
            'end_at' => date(DATE_ISO8601, time()),
            'user_ids' => $this->uids
        ];

        $response = $this->actingAs($this->admin)->postJson(self::URI, $requestData);
        $response->assertOk();

        $totalTime = array_sum(array_column($response->json(), 'total_time'));

        $this->assertEquals($this->duration, $totalTime);
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
