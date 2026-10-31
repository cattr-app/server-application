<?php

namespace Tests\Feature\Routes;

use App\Enums\AttachmentStatus;
use App\Enums\UniversalReportBase;
use App\Enums\UniversalReportType;
use App\Models\Attachment;
use App\Models\Priority;
use App\Models\ProjectGroup;
use App\Models\Status;
use App\Models\TaskComment;
use App\Models\UniversalReport;
use App\Models\User;
use App\Services\AttachmentService;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\Facades\IntervalFactory;
use Tests\Facades\InvitationFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * Calls every registered GET route as an admin on seeded data and checks that none of them
 * returns a 5xx. It catches crashes on endpoints that have no feature test yet; the response
 * contents are the job of the feature tests.
 */
class GetRoutesSmokeTest extends TestCase
{
    protected bool $recordsRouteCoverage = false;

    /** Routes that cannot be called with a plain authenticated GET, keyed by URI. */
    private const EXCLUDED = [
        'api/(.*)' => 'catch-all that answers unknown API paths with 404, not an endpoint',
        'api/tmp-attachment-link/{attachment}' => 'signed URL download, see Attachments\TemporaryDownloadTest',
        'api/time-intervals/{interval}/screenshot' => 'signed URL download, see TimeIntervals\ScreenshotTest',
        'api/time-intervals/{interval}/thumb' => 'signed URL download, see TimeIntervals\ScreenshotTest',
        'broadcasting/auth' => 'websocket channel authorization, needs a broadcaster and a channel name',
    ];

    private User $admin;

    /** Values for route parameters, keyed by parameter name. */
    private array $parameters;

    /** Query strings for routes that need one to get past validation, keyed by URI. */
    private array $queries;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(AttachmentService::DISK);

        $this->admin = UserFactory::asAdmin()->withTokens()->create();
        $user = UserFactory::refresh()->asUser()->create();

        $task = TaskFactory::refresh()->forUser($user)->create();
        $interval = IntervalFactory::forUser($user)->forTask($task)->create();
        $invitation = InvitationFactory::create();

        $attachment = Attachment::create([
            'attachmentable_id' => $task->id,
            'attachmentable_type' => $task::class,
            'original_name' => 'notes.txt',
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => 5,
            'project_id' => $task->project_id,
            'user_id' => $user->id,
            'status' => AttachmentStatus::GOOD,
        ]);
        Storage::disk(AttachmentService::DISK)->put("projects/$task->project_id/$attachment->id", 'notes');

        $comment = TaskComment::create(['task_id' => $task->id, 'user_id' => $user->id, 'content' => 'comment']);
        $group = ProjectGroup::create(['name' => 'Group']);

        $report = new UniversalReport([
            'name' => 'Report',
            'type' => UniversalReportType::COMPANY,
            'base' => UniversalReportBase::PROJECT,
            'data_objects' => [],
            'fields' => [],
            'charts' => [],
        ]);
        $report->user_id = $this->admin->id;
        $report->save();

        $this->parameters = [
            'key' => $invitation->key,
            'user' => $user->id,
            'interval' => $interval->id,
            'attachment' => $attachment->id,
        ];

        $this->queries = [
            'api/priorities/show' => ['id' => Priority::firstOrFail()->id],
            'api/statuses/show' => ['id' => Status::firstOrFail()->id],
            'api/projects/show' => ['id' => $task->project_id],
            'api/projects/gantt-data' => ['id' => $task->project_id],
            'api/projects/phases' => ['id' => $task->project_id],
            'api/project-members/list' => ['project_id' => $task->project_id],
            'api/project-groups/show' => ['id' => $group->id],
            'api/tasks/show' => ['id' => $task->id],
            'api/task-comment/list' => ['task_id' => $task->id],
            'api/task-comment/show' => ['id' => $comment->id],
            'api/users/show' => ['id' => $user->id],
            'api/time-intervals/show' => ['id' => $interval->id],
            'api/report/universal-report/show' => ['id' => $report->id],
            'api/report/universal-report/data-objects-and-fields' => ['base' => UniversalReportBase::PROJECT->value],
        ];
    }

    public function test_get_routes_do_not_return_server_errors(): void
    {
        $failures = [];

        foreach ($this->getRoutes() as $route) {
            $uri = $this->buildUri($route);
            $response = $this->actingAs($this->admin)->getJson($uri);

            if ($response->status() >= 500) {
                $message = $response->exception?->getMessage() ?? $response->json('error.message');
                $failures[] = sprintf('GET %s -> %d %s', $uri, $response->status(), $message);
            }
        }

        $this->assertSame([], $failures, 'GET routes returned a server error');
    }

    public function test_excluded_routes_exist(): void
    {
        $uris = collect(Route::getRoutes()->getRoutes())->map->uri()->all();

        $this->assertSame([], array_values(array_diff(array_keys(self::EXCLUDED), $uris)));
    }

    /** @return RoutingRoute[] */
    private function getRoutes(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(static fn(RoutingRoute $route) => in_array('GET', $route->methods(), true))
            ->reject(static fn(RoutingRoute $route) => $route->isFallback)
            ->reject(static fn(RoutingRoute $route) => array_key_exists($route->uri(), self::EXCLUDED))
            ->values()
            ->all();
    }

    private function buildUri(RoutingRoute $route): string
    {
        $uri = $route->uri();

        foreach ($route->parameterNames() as $name) {
            $this->assertArrayHasKey(
                $name,
                $this->parameters,
                "No seeded value for {{$name}} in GET $uri: add one to \$parameters or exclude the route"
            );

            $uri = str_replace("{{$name}}", (string)$this->parameters[$name], $uri);
        }

        $query = $this->queries[$route->uri()] ?? [];

        // An absolute URL, so that TestCase does not prefix non-API routes (actuator, sanctum) with /api
        return url($uri) . ($query ? '?' . http_build_query($query) : '');
    }
}
