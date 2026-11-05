<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;
use Tests\TestResponse;

class ActivityTest extends TestCase
{
    private const URI = 'tasks/activity';

    private User $admin;
    private Task $task;

    /** @var int[] comment ids in creation (ascending) order */
    private array $commentIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->task = TaskFactory::create();

        foreach (['First', 'Second'] as $content) {
            $this->commentIds[] = TaskComment::create([
                'task_id' => $this->task->id,
                'user_id' => $this->admin->id,
                'content' => $content,
            ])->id;
        }
    }

    private function request(string $type, array $extra = []): TestResponse
    {
        return $this->actingAs($this->admin)->postJson(self::URI, [
            'task_id' => $this->task->id,
            'type' => $type,
            'page' => 1,
        ] + $extra);
    }

    private function idsOf(array $items): array
    {
        return collect($items)->where('content', '!=', null)->pluck('id')->all();
    }

    public static function activityTypes(): array
    {
        return [['all'], ['history'], ['comments']];
    }

    #[DataProvider('activityTypes')]
    public function test_order_by_is_optional(string $type): void
    {
        $this->request($type)->assertOk();
    }

    #[DataProvider('activityTypes')]
    public function test_order_by_without_direction_is_accepted(string $type): void
    {
        $this->request($type, ['orderBy' => ['id']])->assertOk();
    }

    public static function invalidOrderBy(): array
    {
        return [
            'empty' => [[]],
            'unknown direction' => [['id', 'invalid']],
            'too many items' => [['id', 'asc', 'extra']],
            'no column' => [[1 => 'asc']],
        ];
    }

    #[DataProvider('invalidOrderBy')]
    public function test_malformed_order_by_is_rejected(array $orderBy): void
    {
        $this->request('comments', ['orderBy' => $orderBy])->assertUnprocessable();
    }

    public function test_default_direction_is_ascending(): void
    {
        $default = $this->idsOf($this->request('comments')->json('data'));
        $asc = $this->idsOf($this->request('comments', ['orderBy' => ['id', 'asc']])->json('data'));
        $desc = $this->idsOf($this->request('comments', ['orderBy' => ['id', 'desc']])->json('data'));

        $this->assertSame($this->commentIds, $default);
        $this->assertSame($this->commentIds, $asc);
        $this->assertSame(array_reverse($this->commentIds), $desc);
    }
}
