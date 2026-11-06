<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskHistory;
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
        return $this->actingAs($this->admin)->postJson(self::URI, $extra + [
            'task_id' => $this->task->id,
            'type' => $type,
            'page' => 1,
        ]);
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
            'sparse' => [[0 => 'id', 2 => 'desc']],
        ];
    }

    #[DataProvider('invalidOrderBy')]
    public function test_malformed_order_by_is_rejected(array $orderBy): void
    {
        $this->request('comments', ['orderBy' => $orderBy])->assertUnprocessable();
    }

    public function test_direction_is_case_insensitive(): void
    {
        $desc = $this->idsOf($this->request('comments', ['orderBy' => ['id', 'DESC']])->json('data'));

        $this->assertSame(array_reverse($this->commentIds), $desc);
    }

    public function test_default_direction_is_ascending(): void
    {
        $default = $this->idsOf($this->request('comments')->json('data'));
        $idOnly = $this->idsOf($this->request('comments', ['orderBy' => ['id']])->json('data'));
        $asc = $this->idsOf($this->request('comments', ['orderBy' => ['id', 'asc']])->json('data'));
        $desc = $this->idsOf($this->request('comments', ['orderBy' => ['id', 'desc']])->json('data'));

        $this->assertSame($this->commentIds, $default);
        $this->assertSame($this->commentIds, $idOnly);
        $this->assertSame($this->commentIds, $asc);
        $this->assertSame(array_reverse($this->commentIds), $desc);
    }

    private function createHistory(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            TaskHistory::create([
                'task_id' => $this->task->id,
                'user_id' => $this->admin->id,
                'field' => 'task_name',
                'old_value' => "old $i",
                'new_value' => "new $i",
            ]);
        }
    }

    public function test_all_paginates_the_merged_stream(): void
    {
        TaskComment::query()->where('task_id', $this->task->id)->delete();
        $this->createHistory(60);

        $first = $this->request('all');
        $second = $this->request('all', ['page' => 2]);
        $third = $this->request('all', ['page' => 3]);

        $first->assertOk()->assertJsonPath('pagination.total', 60)->assertJsonPath('pagination.perPage', 30)->assertJsonPath('pagination.totalPages', 2);
        $this->assertCount(30, $first->json('data'));
        $this->assertCount(30, $second->json('data'));
        $this->assertCount(0, $third->json('data'));
        $this->assertCount(60, collect($first->json('data'))->merge($second->json('data'))->pluck('id')->unique());
    }

    public function test_all_pages_do_not_overlap_when_sources_are_mixed(): void
    {
        $this->createHistory(40);

        $total = count($this->commentIds) + TaskHistory::query()->where('task_id', $this->task->id)->count();
        $ids = [];
        foreach ([1, 2] as $page) {
            $response = $this->request('all', ['page' => $page])->assertOk()->assertJsonPath('pagination.total', $total);
            foreach ($response->json('data') as $item) {
                $ids[] = (isset($item['content']) ? 'c' : 'h') . $item['id'];
            }
        }

        $this->assertCount($total, $ids);
        $this->assertCount($total, array_unique($ids));
    }
}
