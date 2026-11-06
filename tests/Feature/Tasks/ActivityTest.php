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

        $first->assertOk()
            ->assertJsonPath('pagination.total', 60)
            ->assertJsonPath('pagination.perPage', 30)
            ->assertJsonPath('pagination.totalPages', 2);
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
            $response = $this->request('all', ['page' => $page])
                ->assertOk()
                ->assertJsonPath('pagination.total', $total);
            foreach ($response->json('data') as $item) {
                $ids[] = (isset($item['content']) ? 'c' : 'h') . $item['id'];
            }
        }

        $this->assertCount($total, $ids);
        $this->assertCount($total, array_unique($ids));
    }

    public function test_all_ignores_a_requested_column_order_when_merging(): void
    {
        TaskComment::query()->where('task_id', $this->task->id)->delete();
        $other = UserFactory::refresh()->asUser()->create();
        $this->assertGreaterThan($this->admin->id, $other->id);

        // The older half belongs to the user with the greater id, so ordering by user_id contradicts created_at
        foreach ([[$other, '2026-01-01 00:00:00'], [$this->admin, '2026-02-01 00:00:00']] as [$user, $createdAt]) {
            for ($i = 0; $i < 30; $i++) {
                $comment = TaskComment::create([
                    'task_id' => $this->task->id,
                    'user_id' => $user->id,
                    'content' => "Comment $i",
                ]);
                $comment->forceFill(['created_at' => $createdAt])->saveQuietly();
            }
        }

        $ids = [];
        foreach ([1, 2] as $page) {
            $response = $this->request('all', ['page' => $page, 'orderBy' => ['user_id', 'asc']])->assertOk();
            $ids = array_merge($ids, collect($response->json('data'))->pluck('id')->all());
        }

        $this->assertCount(60, array_unique($ids));
    }

    public static function mergedDirections(): array
    {
        return [
            // The comment goes first among entries created at the same time
            'asc' => ['asc', ['c:A', 'c:C', 'h:B', 'h:D']],
            'desc' => ['desc', ['h:D', 'h:B', 'c:C', 'c:A']],
        ];
    }

    #[DataProvider('mergedDirections')]
    public function test_all_orders_sources_by_creation_time(string $direction, array $expected): void
    {
        TaskComment::query()->where('task_id', $this->task->id)->delete();
        TaskHistory::query()->where('task_id', $this->task->id)->delete();

        $entries = [
            'c:A' => [TaskComment::class, '2026-01-01 10:00:00'],
            'h:B' => [TaskHistory::class, '2026-01-01 11:00:00'],
            'c:C' => [TaskComment::class, '2026-01-01 11:00:00'],
            'h:D' => [TaskHistory::class, '2026-01-01 12:00:00'],
        ];
        $keys = [];
        foreach ($entries as $key => [$model, $createdAt]) {
            $row = $model === TaskComment::class
                ? ['content' => $key]
                : ['field' => 'task_name', 'old_value' => 'old', 'new_value' => $key];
            $created = $model::create(['task_id' => $this->task->id, 'user_id' => $this->admin->id] + $row);
            $model::query()->whereKey($created->id)->update(['created_at' => $createdAt]);
            $keys[($model === TaskComment::class ? 'c' : 'h') . $created->id] = $key;
        }

        $actual = [];
        foreach ([1, 2] as $page) {
            $response = $this->request('all', ['page' => $page, 'orderBy' => ['id', $direction]])->assertOk();
            foreach ($response->json('data') as $item) {
                $actual[] = $keys[(isset($item['content']) ? 'c' : 'h') . $item['id']];
            }
        }

        $this->assertSame($expected, $actual);
    }

    public function test_all_past_the_last_page_is_empty(): void
    {
        $response = $this->request('all', ['page' => 99])->assertOk();

        $this->assertSame([], $response->json('data'));
        $response->assertJsonPath('pagination.total', count($this->commentIds));
    }
}
