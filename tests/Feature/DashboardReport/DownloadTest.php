<?php

namespace Tests\Feature\DashboardReport;

use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\IntervalFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;
use Tests\TestResponse;

class DownloadTest extends TestCase
{
    private const URI = '/api/report/dashboard/download';

    private const CSV = 'text/csv';

    private User $worker;
    private User $otherWorker;
    private TimeInterval $interval;
    private TimeInterval $otherInterval;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('reports');

        $this->worker = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->otherWorker = UserFactory::refresh()->asUser()->withTokens()->create();

        $this->interval = $this->interval($this->worker, '2026-03-10 10:00:00', '2026-03-10 11:00:00');
        $this->otherInterval = $this->interval($this->otherWorker, '2026-03-11 10:00:00', '2026-03-11 10:30:00');
    }

    private function interval(User $user, string $startAt, string $endAt): TimeInterval
    {
        TaskFactory::refresh();
        $interval = IntervalFactory::refresh()->forUser($user)->create();
        $interval->forceFill(['start_at' => $startAt, 'end_at' => $endAt])->save();

        return $interval;
    }

    private function payload(array $data = []): array
    {
        return $data + [
            'start_at' => '2026-03-10 00:00:00',
            'end_at' => '2026-03-11 23:59:59',
            'user_timezone' => 'UTC',
        ];
    }

    private function download(User $user, array $data = [], string $accept = self::CSV): TestResponse
    {
        return $this->actingAs($user)->postJson(self::URI, $this->payload($data), ['Accept' => $accept]);
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

    private function reportedNames(array $rows): array
    {
        $names = array_column(array_slice($rows, 1), 0);
        sort($names);

        return $names;
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

        $response = $this->download($user, ['users' => [$this->worker->id]]);

        $response->assertOk()->assertJsonStructure(['data' => ['url']]);
        $this->assertCount(1, Storage::disk('reports')->allFiles('reports'));
    }

    public function test_csv_contains_the_header_and_the_worked_time(): void
    {
        $admin = UserFactory::refresh()->asAdmin()->withTokens()->create();

        $response = $this->download($admin, ['users' => [$this->worker->id]]);

        $response->assertOk();
        $rows = $this->csvRows($response);
        $this->assertSame(['User Name', 'Hours', 'Hours (decimal)'], array_slice($rows[0], 0, 3));
        $this->assertSame('26-03-10', $rows[0][3]);
        $this->assertSame('26-03-11', $rows[0][4]);
        $this->assertCount(2, $rows);
        $this->assertSame($this->worker->full_name, $rows[1][0]);
        $this->assertEquals(1.0, $rows[1][2]);
    }

    public function test_users_filter_limits_the_report(): void
    {
        $admin = UserFactory::refresh()->asAdmin()->withTokens()->create();

        $response = $this->download($admin, ['users' => [$this->otherWorker->id]]);

        $this->assertSame([$this->otherWorker->full_name], $this->reportedNames($this->csvRows($response)));
    }

    public function test_projects_filter_limits_the_report(): void
    {
        $admin = UserFactory::refresh()->asAdmin()->withTokens()->create();

        $response = $this->download($admin, [
            'users' => [$this->worker->id, $this->otherWorker->id],
            'projects' => [$this->otherInterval->task->project_id],
        ]);

        $this->assertSame([$this->otherWorker->full_name], $this->reportedNames($this->csvRows($response)));
    }

    public function test_user_gets_only_own_time(): void
    {
        $response = $this->download($this->worker, ['users' => [$this->worker->id, $this->otherWorker->id]]);

        $response->assertOk();
        $this->assertSame([$this->worker->full_name], $this->reportedNames($this->csvRows($response)));
    }

    public function test_sorting_by_worked_time(): void
    {
        $admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $users = [$this->worker->id, $this->otherWorker->id];

        $response = $this->download($admin, ['users' => $users, 'sort_column' => 'worked', 'sort_direction' => 'asc']);

        $names = array_column(array_slice($this->csvRows($response), 1), 0);
        $this->assertSame([$this->otherWorker->full_name, $this->worker->full_name], $names);

        $response = $this->download($admin, ['users' => $users, 'sort_column' => 'worked', 'sort_direction' => 'desc']);

        $names = array_column(array_slice($this->csvRows($response), 1), 0);
        $this->assertSame([$this->worker->full_name, $this->otherWorker->full_name], $names);
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
        $admin = UserFactory::refresh()->asAdmin()->withTokens()->create();

        $response = $this->download($admin, ['users' => [$this->worker->id]], $accept);

        $response->assertOk();
        $path = $this->filePath($response);
        $this->assertStringEndsWith(".$extension", $path);
        $this->assertStringStartsWith($signature, ltrim(Storage::disk('reports')->get($path)));
    }

    public function test_unsupported_accept_header_is_rejected(): void
    {
        $admin = UserFactory::refresh()->asAdmin()->withTokens()->create();

        $this->download($admin, [], 'application/json')->assertValidationError();

        $this->assertSame([], Storage::disk('reports')->allFiles('reports'));
    }

    public static function invalidPayloads(): array
    {
        return [
            'no start_at' => [['start_at' => null]],
            'no end_at' => [['end_at' => null]],
            'bad start_at' => [['start_at' => 'not a date']],
            'no user_timezone' => [['user_timezone' => null]],
            'bad user_timezone' => [['user_timezone' => 'Mars/Olympus']],
            'users not an array' => [['users' => 1]],
            'unknown user' => [['users' => [0]]],
            'unknown project' => [['projects' => [0]]],
            'bad sort_column' => [['sort_column' => 'nonsense']],
            'bad sort_direction' => [['sort_direction' => 'sideways']],
        ];
    }

    #[DataProvider('invalidPayloads')]
    public function test_validation(array $override): void
    {
        $admin = UserFactory::refresh()->asAdmin()->withTokens()->create();

        $this->download($admin, $override)->assertValidationError();

        $this->assertSame([], Storage::disk('reports')->allFiles('reports'));
    }

    public function test_unauthorized(): void
    {
        $this->postJson(self::URI, $this->payload(), ['Accept' => self::CSV])->assertUnauthorized();
    }
}
