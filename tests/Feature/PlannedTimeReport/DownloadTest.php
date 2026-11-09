<?php

namespace Tests\Feature\PlannedTimeReport;

use App\Models\CronTaskWorkers;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Settings;
use Tests\Facades\ProjectFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;
use Tests\TestResponse;

class DownloadTest extends TestCase
{
    private const URI = '/api/report/planned-time/download';

    private const CSV = 'text/csv';

    private User $member;
    private User $worker;
    private Project $project;
    private Project $foreignProject;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('reports');
        Settings::scope('core.reports')->set('planned_time_report_date', '2026-03-12 04:00:00');

        $this->member = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->worker = UserFactory::refresh()->asUser()->create();

        $this->project = ProjectFactory::refresh()->forUsers([$this->member])->create();
        $this->foreignProject = ProjectFactory::refresh()->create();

        $this->task = $this->task($this->project, 7200);
        $this->worker($this->task, 5400);
        $this->worker($this->task($this->foreignProject, null), 1800);
    }

    private function task(Project $project, ?int $estimate): Task
    {
        $task = TaskFactory::refresh()->forProject($project)->create();
        $task->update(['estimate' => $estimate]);

        return $task;
    }

    private function worker(Task $task, int $duration): void
    {
        CronTaskWorkers::query()->insert([
            'user_id' => $this->worker->id,
            'task_id' => $task->id,
            'duration' => $duration,
            'offset' => 0,
            'created_by_cron' => true,
        ]);
    }

    private function download(User $user, array $data = [], string $accept = self::CSV): TestResponse
    {
        return $this->actingAs($user)->postJson(self::URI, $data, ['Accept' => $accept]);
    }

    private function filePath(TestResponse $response): string
    {
        return preg_replace('#^/storage/+#', '', $response->json('data.url'));
    }

    /** Rows of the CSV file behind the URL of the response, the header row first. */
    private function csvRows(TestResponse $response): array
    {
        $path = $this->filePath($response);
        $this->assertStringEndsWith('.csv', $path);

        $content = Storage::disk('reports')->get($path);
        $this->assertNotNull($content);

        return array_map('str_getcsv', array_values(array_filter(preg_split('/\R/', ltrim($content, "\xEF\xBB\xBF")))));
    }

    public static function allRoles(): array
    {
        return [
            'admin' => ['asAdmin'],
            'manager' => ['asManager'],
            'auditor' => ['asAuditor'],
            'user' => ['asUser'],
        ];
    }

    #[DataProvider('allRoles')]
    public function test_role_can_download_report(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->download($user, ['projects' => [$this->project->id]]);

        $response->assertOk()->assertJsonStructure(['data' => ['url']]);
        $this->assertCount(1, Storage::disk('reports')->allFiles('reports'));
    }

    public function test_csv_contains_the_header_the_worker_row_the_estimate_and_the_subtotal(): void
    {
        $response = $this->download($this->member, ['projects' => [$this->project->id]]);

        $response->assertOk();
        $rows = $this->csvRows($response);
        $this->assertSame(['Project Name', 'Task Name', 'User Name', 'Hours', 'Hours (decimal)'], $rows[0]);
        $this->assertSame(
            [$this->project->name, $this->task->task_name, $this->worker->full_name],
            array_slice($rows[1], 0, 3)
        );
        $this->assertEquals(1.5, $rows[1][4]);
        $this->assertSame("Estimate for {$this->task->task_name}", $rows[2][1]);
        $this->assertEquals(2.0, $rows[2][4]);
        $this->assertSame("Subtotal for {$this->project->name}", $rows[3][0]);
        $this->assertEquals(1.5, $rows[3][4]);
        // the last row tells when the time was calculated
        $this->assertStringContainsString('2026-03-12 04:00:00', end($rows)[1]);
    }

    public function test_projects_filter_limits_the_report_of_admin(): void
    {
        $admin = UserFactory::refresh()->asAdmin()->withTokens()->create();

        $response = $this->download($admin, ['projects' => [$this->foreignProject->id]]);

        $response->assertOk();
        $content = Storage::disk('reports')->get($this->filePath($response));
        $this->assertStringContainsString($this->foreignProject->name, $content);
        $this->assertStringNotContainsString($this->project->name, $content);
    }

    public function test_user_gets_only_own_projects(): void
    {
        $response = $this->download($this->member, ['projects' => [$this->project->id, $this->foreignProject->id]]);

        $response->assertOk();
        $content = Storage::disk('reports')->get($this->filePath($response));
        $this->assertStringContainsString($this->project->name, $content);
        $this->assertStringNotContainsString($this->foreignProject->name, $content);
    }

    public function test_without_projects_the_report_covers_all_accessible_projects(): void
    {
        $admin = UserFactory::refresh()->asAdmin()->withTokens()->create();

        $response = $this->download($admin);

        $response->assertOk();
        $content = Storage::disk('reports')->get($this->filePath($response));
        $this->assertStringContainsString($this->project->name, $content);
        $this->assertStringContainsString($this->foreignProject->name, $content);
    }

    public static function acceptHeaders(): array
    {
        return [
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'xlsx', "PK\x03\x04"],
            'html' => ['text/html', 'html', '<'],
        ];
    }

    #[DataProvider('acceptHeaders')]
    public function test_file_format_follows_the_accept_header(
        string $accept,
        string $extension,
        string $signature
    ): void {
        $response = $this->download($this->member, ['projects' => [$this->project->id]], $accept);

        $response->assertOk();
        $path = $this->filePath($response);
        $this->assertStringEndsWith(".$extension", $path);
        $this->assertStringStartsWith($signature, ltrim(Storage::disk('reports')->get($path)));
    }

    public function test_unsupported_accept_header_is_rejected(): void
    {
        $this->download($this->member, [], 'application/json')->assertValidationError();

        $this->assertSame([], Storage::disk('reports')->allFiles('reports'));
    }

    public static function invalidPayloads(): array
    {
        return [
            'projects not an array' => [['projects' => 1]],
            'unknown project' => [['projects' => [0]]],
        ];
    }

    #[DataProvider('invalidPayloads')]
    public function test_validation(array $payload): void
    {
        $this->download($this->member, $payload)->assertValidationError();

        $this->assertSame([], Storage::disk('reports')->allFiles('reports'));
    }

    public function test_unauthorized(): void
    {
        $this->postJson(self::URI, [], ['Accept' => self::CSV])->assertUnauthorized();
    }
}
