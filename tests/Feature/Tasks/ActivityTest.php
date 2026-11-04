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

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->task = TaskFactory::create();

        foreach (['First', 'Second'] as $content) {
            TaskComment::create([
                'task_id' => $this->task->id,
                'user_id' => $this->admin->id,
                'content' => $content,
            ]);
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

    private function commentIds(array $items): array
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

    public function test_default_direction_is_ascending(): void
    {
        $default = $this->commentIds($this->request('comments')->json('data'));
        $asc = $this->commentIds($this->request('comments', ['orderBy' => ['id', 'asc']])->json('data'));
        $desc = $this->commentIds($this->request('comments', ['orderBy' => ['id', 'desc']])->json('data'));

        $this->assertCount(2, $default);
        $this->assertSame($asc, $default);
        $this->assertSame(array_reverse($asc), $desc);
    }
}
