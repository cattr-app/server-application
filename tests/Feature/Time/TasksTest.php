<?php

namespace Tests\Feature\Time;

use App\Models\User;
use Illuminate\Support\Collection;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class TasksTest extends TestCase
{
    private const URI = 'time/tasks';

    private const INTERVALS_AMOUNT = 10;

    private Collection $intervals;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->intervals = IntervalFactory::forUser($this->admin)->createMany(self::INTERVALS_AMOUNT);
    }

    public function test_total(): void
    {
        $requestData = [
            'start_at' => $this->intervals->min('start_at'),
            'end_at' => $this->intervals->max('end_at')->addMinute(),
            'user_id' => $this->admin->id
        ];

        $response = $this->actingAs($this->admin)->postJson(self::URI, $requestData);
        $response->assertOk();

        //TODO CHECK RESPONSE CONTENT
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_wrong_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, ['task_id' => 'wrong']);

        $response->assertValidationError();
    }
}
