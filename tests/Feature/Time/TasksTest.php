<?php

namespace Tests\Feature\Time;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * Class TasksTest
 */
class TasksTest extends TestCase
{
    private const URI = 'v1/time/tasks';

    private const INTERVALS_AMOUNT = 10;

    /**
     * @var Collection
     */
    private $intervals;

    /**
     * @var User
     */
    private $admin;

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
            'end_at' => Carbon::create($this->intervals->max('end_at'))->addMinute(),
            'user_id' => $this->admin->id
        ];

        $response = $this->actingAs($this->admin)->postJson(self::URI, $requestData);
        $response->assertSuccess();

        //TODO CHECK RESPONSE CONTENT
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }

    //TODO ASK QUESTIONS
//
//    public function test_without_params(): void
//    {
//        $response = $this->actingAs($this->admin)->getJson(self::URI);
//
//        $response->assertValidationError();
//    }
}
