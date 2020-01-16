<?php


namespace Tests\Feature\TimeIntervals;


use App\Models\Task;
use App\Models\TimeInterval;
use App\User;
use Tests\Facades\IntervalFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class EditTest extends TestCase
{
    private const URI = 'v1/time-intervals/edit';

    /**
     * @var User
     */
    private $admin;

    /**
     * @var TimeInterval
     */
    private $interval;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->interval = IntervalFactory::create()->makeHidden('updated_at');
    }

    public function test_edit()
    {
        $this->interval->count_keyboard = $this->interval->count_keyboard + 5;

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->interval->toArray());

        $response->assertOk();
        $response->assertJson(['res' => $this->interval->toArray()]);
        $this->assertDatabaseHas('time_intervals', $this->interval->toArray());
    }

    public function test_not_existing_interval()
    {
        ++$this->interval->id;

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->interval->toArray());

        $response->assertItemNotFound();
    }

    public function test_unauthorized()
    {
        $response = $this->postJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_without_params()
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertValidationError();
    }
}
