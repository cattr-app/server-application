<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ScreenshotService;
use App\Enums\OfflineScreenshotUploadStatus;
use App\Enums\ScreenshotsState;
use App\Exceptions\Entities\AuthorizationException;
use App\Http\Requests\Interval\BulkDestroyTimeIntervalRequest;
use App\Http\Requests\Interval\BulkEditTimeIntervalRequest;
use App\Http\Requests\Interval\CreateTimeIntervalRequest;
use App\Http\Requests\Interval\DestroyTimeIntervalRequest;
use App\Http\Requests\Interval\IntervalTasksRequest;
use App\Http\Requests\Interval\IntervalTotalRequest;
use App\Http\Requests\Interval\ListIntervalRequest;
use App\Http\Requests\Interval\PutScreenshotRequest;
use App\Http\Requests\Interval\ScreenshotRequest;
use App\Helpers\OfflineScreenshotsArchive;
use App\Http\Requests\Interval\ShowIntervalRequest;
use App\Http\Requests\Interval\ShowOfflineScreenshotsUploadRequest;
use App\Http\Requests\Interval\TrackAppRequest;
use App\Http\Requests\Interval\UploadOfflineIntervalsRequest;
use App\Http\Requests\Interval\UploadOfflineScreenshotsRequest;
use App\Jobs\AssignAppsToTimeInterval;
use App\Jobs\ProcessOfflineScreenshotsUpload;
use App\Models\OfflineScreenshotUpload;
use App\Models\OfflineScreenshotUploadItem;
use App\Models\Task;
use App\Models\TrackedApplication;
use App\Models\User;
use CatEvent;
use DB;
use Filter;
use App\Models\TimeInterval;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use MessagePack\MessagePack;
use phpseclib4\Crypt\RSA;
use phpseclib4\Exception\BadDecryptionException;
use phpseclib4\Exception\KeyConstraintException;
use Settings;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Storage;
use Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use RuntimeException;
use Throwable;
use Validator;
use ZipArchive;

class IntervalController extends ItemController
{
    protected const MODEL = TimeInterval::class;

    public function __construct(protected ScreenshotService $screenshotService)
    {
    }

    /**
     * List
     *
     * Get list of Time Intervals
     *
     * Required permission (any of): `time_intervals_list`, `time_intervals_full_access`
     */

    public function index(ListIntervalRequest $request): JsonResponse
    {
        Filter::listen(Filter::getRequestFilterName(), static function ($filters) use ($request) {
            if ($request->get('project_id')) {
                $filters['task.project_id'] = $request->get('project_id');
            }

            return $filters;
        });

        Filter::listen(Filter::getActionFilterName(), static function ($items) {
            $items->each(
                static fn (TimeInterval $interval) => $interval->append(TimeInterval::SCREENSHOT_URL_ATTRIBUTES)
            );

            return $items;
        });

        return $this->_index($request);
    }

    /**
     * Show
     *
     * Show Time Interval
     *
     * Required permission (any of): `time_intervals_show`, `time_intervals_full_access`
     */
    public function show(ShowIntervalRequest $request): JsonResponse
    {
        Filter::listen(
            Filter::getActionFilterName(),
            static fn (?TimeInterval $interval) => $interval?->append(TimeInterval::SCREENSHOT_URL_ATTRIBUTES),
        );

        return $this->_show($request);
    }

    /**
     * Count
     *
     * Count Time Intervals
     *
     * @deprecated since 1.0.0
     */
    public function count(ListIntervalRequest $request): JsonResponse
    {
        return $this->_count($request);
    }



    /**
     * Destroy
     *
     * Destroy Time Interval
     *
     * Required permission (any of): `time_intervals_remove`, `time_intervals_full_access`
     */
    public function destroy(DestroyTimeIntervalRequest $request): JsonResponse
    {
        return $this->_destroy($request);
    }

    /**
     * Bulk Edit
     *
     * Multiple Edit TimeInterval to assign tasks to them
     */
    public function bulkEdit(BulkEditTimeIntervalRequest $request): JsonResponse
    {
        $intervalsData = collect(
            Filter::process(Filter::getRequestFilterName(), $request->validated())['intervals']
        );

        $intervals = $this->getQuery([
            'where' => [
                'id' => ['in', $intervalsData->pluck('id')->toArray()]
            ]
        ])->get()->toBase();

        CatEvent::dispatch(Filter::getBeforeActionEventName(), [$intervals, $request]);


        $intervals->each(static fn(Model $item) => Filter::process(
            Filter::getActionFilterName(),
            $item->fill(
                Arr::only(
                    $intervalsData->where('id', $item->id)->first() ?: [],
                    'task_id'
                )
            )
        ));

        CatEvent::dispatch(Filter::getAfterActionEventName(), [$intervals, $request]);

        $intervals->each(static function (Model $item) {
            $item->save();
        });

        return responder()->success()->respond(204);
    }

    /**
     * Bulk Destroy
     *
     * Multiple Destroy TimeInterval
     *
     * Required permission (any of): `time_intervals_bulk_remove`, `time_intervals_full_access`
     */
    public function bulkDestroy(BulkDestroyTimeIntervalRequest $request): JsonResponse
    {
        $intervalIds = Filter::process(Filter::getRequestFilterName(), $request->validated())['intervals'];

        $itemsQuery = $this->getQuery(['where' => ['id' => ['in', $intervalIds]]]);

        CatEvent::dispatch(Filter::getBeforeActionEventName(), [$intervalIds, $request]);

        $itemsQuery->eachById(static function ($item) {
            Filter::process(Filter::getActionFilterName(), $item)->delete();
        });

        CatEvent::dispatch(Filter::getAfterActionEventName(), [$intervalIds, $request]);

        return responder()->success()->respond(204);
    }
    /**
     * Adds the current user’s ID
     */
    public function trackApp(TrackAppRequest $request): JsonResponse
    {
        return responder()->success(
            TrackedApplication::create(
                array_merge(
                    $request->validated(),
                    ['user_id' => auth()->user()->id]
                )
            )
        )->respond();
    }

    /**
     * Create
     *
     * Create Time Interval
     *
     * The interval is created together with its screenshot: if the screenshot cannot be stored, nothing is
     * created and the request fails with a server error, so it can be retried as is.
     *
     * Required permission (any of): `time_intervals_create`, `time_intervals_full_access`
     */
    public function create(CreateTimeIntervalRequest $request): JsonResponse
    {
        Filter::listen(
            Filter::getRequestFilterName(),
            static function (array $requestData) {
                $timezone = Settings::scope('core')->get('timezone', 'UTC');

                $requestData['start_at'] = Carbon::parse($requestData['start_at'])->setTimezone($timezone);
                $requestData['end_at'] = Carbon::parse($requestData['end_at'])->setTimezone($timezone);

                return $requestData;
            }
        );

        $screenshotService = $this->screenshotService;

        if ($request->hasFile('screenshot') && optional($request->file('screenshot'))->isValid()) {
            // The listener runs synchronously within this request, so the upload is read straight from PHP's
            // temporary file: nothing is copied to storage/app/tmp and nothing is left behind when the screenshot
            // is not kept or the interval is not created. The encoded screenshot goes to the default disk.
            $screenshot = $request->file('screenshot');

            CatEvent::listen(
                Filter::getAfterActionEventName(),
                static function (TimeInterval $interval) use ($screenshot, $screenshotService) {
                    $projScreenshotsState = $interval->task->project->screenshots_state;
                    $mustCapture = $projScreenshotsState === ScreenshotsState::REQUIRED;
                    $optionalCapture = $projScreenshotsState === ScreenshotsState::OPTIONAL;
                    if ($mustCapture
                        || ($optionalCapture && $interval->user->screenshots_state === ScreenshotsState::REQUIRED)
                    ) {
                        $screenshotService->saveScreenshot($screenshot, $interval);
                    }
                }
            );
        }

        CatEvent::listen(
            Filter::getAfterActionEventName(),
            static function ($data) {
                if (User::find($data['user_id'])->web_and_app_monitoring) {
                    AssignAppsToTimeInterval::dispatch($data)->afterCommit();
                }
            }
        );

        // The interval, its screenshot and the jobs queued for it are one unit: when the screenshot cannot be
        // written the interval is rolled back (and the jobs are not queued), so the client can safely retry.
        return DB::transaction(fn () => $this->_create($request));
    }

    /**
     * Screenshot
     *
     * Stream the screenshot of an interval. Not authenticated: access is granted by the
     * temporary signature (10 min) of the URL from `screenshot_url` or `screenshot-urls`.
     * Used only with the local disk; on S3 the URLs point to the bucket directly.
     */
    public function showScreenshot(Request $request, int $interval): StreamedResponse
    {
        return $this->streamScreenshot($request, $this->screenshotService->getScreenshotPath($interval));
    }

    /**
     * Thumbnail
     *
     * Stream the screenshot thumbnail of an interval, see Screenshot.
     */
    public function showThumbnail(Request $request, int $interval): StreamedResponse
    {
        return $this->streamScreenshot($request, $this->screenshotService->getThumbPath($interval));
    }

    /**
     * @throws Throwable
     */
    private function streamScreenshot(Request $request, string $path): StreamedResponse
    {
        // Checked here rather than by the "signed" middleware: its InvalidSignatureException renders as 500
        throw_unless(
            $request->hasValidSignature(absolute: false),
            new AuthorizationException(AuthorizationException::ERROR_TYPE_FORBIDDEN)
        );
        abort_unless(Storage::exists($path), 404);

        return Storage::response($path, headers: [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=' . ScreenshotService::URL_TTL_MINUTES * 60,
        ]);
    }

    /**
     * Screenshot URLs
     *
     * Fresh temporary URLs of the interval screenshot and thumbnail
     * (for pages open longer than the URL lifetime and for report payloads without URLs)
     */
    public function screenshotUrls(ScreenshotRequest $request, TimeInterval $interval): JsonResponse
    {
        return responder()->success($this->screenshotService->getTemporaryUrls($interval))->respond();
    }

    /**
     * Upload a screenshot of an interval
     *
     * Saves the image from the `screenshot` field (`multipart/form-data`) as the screenshot of an existing time
     * interval. The image is stored as JPEG and its thumbnail is generated in the background.
     *
     * Allowed to the users who may create the interval: administrators, the interval owner (with the user or manager
     * role in the project, or assigned to the task) and managers for the intervals of other users.
     *
     * Responds with 409 if the interval already has a screenshot or if screenshots are not allowed for it: they are
     * disabled for the project of the interval, or optional for the project and not required for the user.
     */
    public function putScreenshot(PutScreenshotRequest $request): JsonResponse
    {
        $data = $request->validated();
        $interval = $request->timeInterval();

        abort_if(
            Storage::exists($this->screenshotService->getScreenshotPath($interval)),
            409,
            __('Screenshot for requested interval already exists')
        );

        $projScreenshotsState = $interval->task->project->screenshots_state;
        $mustCapture = $projScreenshotsState === ScreenshotsState::REQUIRED;
        $optionalCapture = $projScreenshotsState === ScreenshotsState::OPTIONAL;
        if ($mustCapture
            || ($optionalCapture && $interval->user->screenshots_state === ScreenshotsState::REQUIRED)
        ) {
            $this->screenshotService->saveScreenshot($data['screenshot'], $interval);
        } else {
            abort(
                409,
                __('Screenshots disabled for interval\'s project')
            );
        }

        return responder()->success()->respond(204);
    }

    /**
     * Upload offline intervals
     *
     * Imports the intervals recorded while the desktop application was offline. The `file` field
     * (`multipart/form-data`) is a ZIP archive named `*.cattr`, up to 12 MB, with two entries: `Intervals`
     * (MessagePack list of intervals) and `EncryptedDigest` (SHA-256 digest of `Intervals`, encrypted with the key
     * from `GET /offline-sync/public-key`).
     *
     * An archive that cannot be opened or read, with a digest mismatch, without intervals or for an unknown user is
     * rejected with 400. Otherwise the response is 200 and reports each interval separately: an interval is added only
     * if it passes the same validation as `POST /time-intervals/create` and the current user may create it,
     * for the rest `message` explains why (a text or validation messages per field).
     *
     * Any authenticated user may call it.
     *
     * @response array{status: int, success: true, data: list<array{
     *     interval: array<string, mixed>,
     *     message: string|array<string, list<string>>,
     *     success: bool,
     * }>}
     */
    public function uploadOfflineIntervals(UploadOfflineIntervalsRequest $request): JsonResponse
    {
        /**
         * @var UploadedFile $file
         */
        $file = $request->validated()['file'];

        $zip = new ZipArchive;
        $zipOpenResult = $zip->open($file->path());
        abort_if(
            $zipOpenResult === false || (is_int($zipOpenResult) && $zipOpenResult > 0),
            400,
            __('Cannot open file.' . is_int($zipOpenResult) ? " ZipArchive error code: $zipOpenResult" : ""),
        );

        $temporaryDirectory = (new TemporaryDirectory())->deleteWhenDestroyed()->force()->create();
        $zip->extractTo($temporaryDirectory->path());
        $zip->close();

        $privateKey = RSA::load(Settings::scope('core.offline-sync')->get('private_key'));

        $intervalsContent = file_get_contents($temporaryDirectory->path('Intervals'));
        $digestContent = file_get_contents($temporaryDirectory->path('EncryptedDigest'));

        abort_if(
            $intervalsContent === false || $digestContent === false,
            400,
            __('Unable to read content of Intervals or its EncryptedDigest')
        );

        try {
            $digest = $privateKey->withPadding(RSA::ENCRYPTION_OAEP)->withHash('sha256')->decrypt($digestContent);
        } catch (BadDecryptionException | KeyConstraintException) {
            $digest = false;
        }
        $actualDigest = openssl_digest($intervalsContent, 'sha256');

        abort_if(
            $digest === false || $actualDigest === false || $digest !== $actualDigest,
            400,
            __('Unable to verify Intervals digest')
        );

        $intervals = MessagePack::unpack($intervalsContent);

        $timezone = Settings::scope('core')->get('timezone', 'UTC');
        $validatorClass = new CreateTimeIntervalRequest();
        $creationResult = [];

        abort_if(
            count($intervals) === 0,
            400,
            __('File contains 0 intervals')
        );

        $user = User::whereId($intervals[0]['user_id'])->first(['id', 'email', 'full_name', 'screenshots_state']);

        abort_if(
            $user === null,
            400,
            __('User not found')
        );

        $canCreate = function ($interval) use ($request) {
            try {
                return $request->user()->can(
                    'create',
                    [
                        TimeInterval::class,
                        $interval['user_id'],
                        $interval['task_id'],
                        false,
                    ],
                );
            } catch (ModelNotFoundException) {
                // The policy cannot resolve the project of an unknown task: report this interval only
                return false;
            }
        };

        $tasksScreenshotsState = Task::with('project:id,screenshots_state')
            ->whereIn('id', collect($intervals)->pluck('task_id'))
            ->select(['id', 'project_id'])
            ->get()
            ->mapWithKeys(fn($item)=>[$item->id => $item->project->screenshots_state])
            ->toArray();
        $globalScreenshotsState = ScreenshotsState::withGlobalOverrides(null) ?? ScreenshotsState::OPTIONAL;

        foreach ($intervals as $interval) {
            $interval['user'] = $user;

            if ($canCreate($interval) === false) {
                $creationResult[] = [
                    'interval' => $interval,
                    'message' => __('validation.offline-sync.cannot_create_interval'),
                    'success' => false
                ];
                continue;
            }

            $screenshotIdValidationRule = $interval['has_screenshot'] ? ['screenshot_id' => 'required|uuid'] : [];

            if ($interval['has_screenshot']) {
                $mustNotCapture = $globalScreenshotsState === ScreenshotsState::FORBIDDEN;
                $optionalCapture = $globalScreenshotsState === ScreenshotsState::OPTIONAL
                    && isset($tasksScreenshotsState[$interval['task_id']]);

                if ($optionalCapture && $tasksScreenshotsState[$interval['task_id']] === ScreenshotsState::FORBIDDEN) {
                    $mustNotCapture = true;
                } elseif ($optionalCapture
                    && $tasksScreenshotsState[$interval['task_id']] === ScreenshotsState::OPTIONAL) {
                    $mustNotCapture = $user->screenshots_state === ScreenshotsState::FORBIDDEN;
                }
                if ($mustNotCapture) {
                    $screenshotIdValidationRule = [];
                }
            }

            $intervalValidator = Validator::make(
                $interval,
                array_merge(
                    $validatorClass->getRules($interval['user_id'], $interval['start_at'], $interval['end_at']),
                    $screenshotIdValidationRule
                )
            );

            if ($intervalValidator->fails()) {
                $creationResult[] = [
                    'interval' => $interval,
                    'message' => $intervalValidator->errors(),
                    'success' => false
                ];
                continue;
            }

            $requestData = $intervalValidator->validated();
            $requestData['start_at'] = Carbon::parse($requestData['start_at'])->setTimezone($timezone);
            $requestData['end_at'] = Carbon::parse($requestData['end_at'])->setTimezone($timezone);

            TimeInterval::create($requestData);
            $creationResult[] = [
                'interval' => $interval,
                'message' => __('validation.offline-sync.time_interval_added'),
                'success' => true
            ];
        }


        return responder()->success($creationResult)->respond();
    }

    /**
     * Upload offline screenshots
     *
     * Queues the screenshots recorded while the desktop application was offline for attaching to their intervals. The
     * `file` field (`multipart/form-data`) is a ZIP archive named `*.cattr` with a flat list of files named
     * `<user_id>_<screenshot_id>` (the screenshot ID is a UUID). An archive that exceeds the configured number of
     * files or unpacked size, contains directories or duplicate names, or unpacks to more than the unpacked size, is
     * rejected with 422; an archive that cannot be read, or a file with another name, with 400.
     *
     * A valid archive is stored on the default disk and processed by a queue worker: the response is 202 with the
     * upload to poll with `GET /offline-sync/uploads/{id}` (a `private-offline-sync.{user_id}` broadcast event
     * `offline-sync.upload` reports its progress as well). Each file is attached to the interval of that user with
     * that `screenshot_id`, if the current user may create such an interval.
     *
     * Any authenticated user may call it.
     *
     * @response 202 array{status: int, success: true, data: array{
     *     id: int,
     *     status: 'pending'|'processing'|'completed'|'failed',
     *     total: int,
     *     processed: int,
     *     results: list<array{}>,
     * }}
     */
    public function uploadOfflineScreenshots(UploadOfflineScreenshotsRequest $request): JsonResponse
    {
        /**
         * @var UploadedFile $file
         */
        $file = $request->validated()['file'];

        // Everything that can be checked without the interval data is checked before the archive is stored: the
        // names and sizes in the headers, then the bytes the entries actually unpack to.
        $archive = OfflineScreenshotsArchive::open($file->path());
        try {
            $archive->validate();
            $archive->scan();
            $total = $archive->count();
        } finally {
            $archive->close();
        }

        // The default disk is shared with the queue workers: the PVC or S3, unlike storage/app/tmp of this container
        $diskName = config('filesystems.default');
        $path = Storage::disk($diskName)->putFileAs(OfflineScreenshotUpload::ARCHIVE_DIRECTORY, $file, Str::uuid() . '.cattr');
        if ($path === false) {
            throw new RuntimeException('Unable to store the offline screenshots archive');
        }

        try {
            $upload = OfflineScreenshotUpload::query()->create([
                'user_id' => $request->user()->id,
                'status' => OfflineScreenshotUploadStatus::PENDING,
                'disk' => $diskName,
                'path' => $path,
                'total' => $total,
            ]);

            ProcessOfflineScreenshotsUpload::dispatch($upload);
        } catch (Throwable $e) {
            Storage::disk($diskName)->delete($path);
            OfflineScreenshotUpload::query()->where('disk', $diskName)->where('path', $path)->delete();

            throw $e;
        }

        return responder()->success($this->offlineScreenshotsUploadData($upload, collect()))
            ->respond(Response::HTTP_ACCEPTED);
    }

    /**
     * Offline screenshots upload
     *
     * The status, progress and per-screenshot results of an upload from `POST /offline-sync/upload-screenshots`. The
     * `results` are the files processed so far: `interval` is `null` if no suitable interval was found.
     *
     * Only the user who uploaded the archive may read it, for anyone else the upload does not exist (404).
     *
     * @urlParam id int required The upload ID. Example: 1
     *
     * @response array{status: int, success: true, data: array{
     *     id: int,
     *     status: 'pending'|'processing'|'completed'|'failed',
     *     total: int,
     *     processed: int,
     *     results: list<array{
     *         interval: \App\Models\TimeInterval|null,
     *         user_id: int,
     *         screenshot_id: string,
     *         message: string,
     *         success: bool,
     *     }>,
     * }}
     */
    public function showOfflineScreenshotsUpload(ShowOfflineScreenshotsUploadRequest $request, int $id): JsonResponse
    {
        // Not found for anyone but the uploader, like a missing upload
        $upload = OfflineScreenshotUpload::query()->where('user_id', $request->user()->id)->findOrFail($id);

        $items = $upload->items()->with('interval')->orderBy('id')->get();

        return responder()->success($this->offlineScreenshotsUploadData($upload, $items))->respond();
    }

    /**
     * @param Collection<int, OfflineScreenshotUploadItem> $items
     */
    private function offlineScreenshotsUploadData(OfflineScreenshotUpload $upload, Collection $items): array
    {
        return [
            'id' => $upload->id,
            'status' => $upload->status->value,
            'total' => $upload->total,
            'processed' => $items->count(),
            'results' => $items->map(static fn (OfflineScreenshotUploadItem $item) => [
                'interval' => $item->interval,
                'user_id' => $item->user_id,
                'screenshot_id' => $item->screenshot_id,
                'message' => __($item->message),
                'success' => $item->success,
            ])->all(),
        ];
    }

    /**
     * Calculate total time interval
     *
     * Calculate the total time spent within the specified start and end intervals for a given user.
     */

    public function total(IntervalTotalRequest $request): JsonResponse
    {
        $requestData = Filter::process(Filter::getRequestFilterName(), $request->validated());

        $timezone = Settings::scope('core')->get('timezone', 'UTC');

        $start_at = Carbon::parse($requestData['start_at'])->setTimezone($timezone);
        $end_at = Carbon::parse($requestData['end_at'])->setTimezone($timezone);

        $filters = [
            'where' => [
                'start_at' => ['>=', $start_at],
                'end_at' => ['<=', $end_at],
                'user_id' => ['=', $requestData['user_id']],
            ],
        ];

        $itemsQuery = $this->getQuery($filters);

        CatEvent::dispatch(Filter::getBeforeActionEventName(), $filters);

        $timeIntervals = Filter::process(Filter::getActionFilterName(), $itemsQuery->get());

        CatEvent::dispatch(Filter::getAfterActionEventName(), [$timeIntervals, $filters]);

        $totalTime = $timeIntervals->sum(static fn($el) => (int) Carbon::parse($el->end_at)->diffInSeconds($el->start_at, true));

        return responder()->success([
            'time' => $totalTime,
            'start' => $timeIntervals->min('start_at'),
            'end' => $timeIntervals->max('end_at'),
        ])->respond();
    }

    /**
     * Calculate total time interval
     *
     * Calculate the total time spent within the specified start and end intervals for a given user.
     */

    public function tasks(IntervalTasksRequest $request): JsonResponse
    {
        $requestData = Filter::process(Filter::getRequestFilterName(), $request->validated());

        $timezone = Settings::scope('core')->get('timezone', 'UTC');

        $filters = [];

        if (isset($requestData['start_at'])) {
            $filters['start_at'] = ['>=', Carbon::parse($requestData['start_at'])->setTimezone($timezone)];
        }

        if (isset($requestData['end_at'])) {
            $filters['end_at'] = ['<=', Carbon::parse($requestData['end_at'])->setTimezone($timezone)];
        }

        if (isset($requestData['project_id'])) {
            $filters['task.project_id'] = $requestData['project_id'];
        }

        if (isset($requestData['task_id'])) {
            $filters['task_id'] = ['in', $requestData['task_id']];
        }

        if (isset($requestData['user_id'])) {
            $filters['user_id'] = $requestData['user_id'];
        }

        $itemsQuery = $this->getQuery($filters ? ['where' => $filters] : []);

        $tasks = $itemsQuery
            ->with('task')
            ->get()
            ->groupBy(['task_id', 'user_id'])
            ->map(static function ($taskIntervals, $taskId) use (&$totalTime) {
                $task = [];

                foreach ($taskIntervals as $userId => $userIntervals) {
                    $taskTime = 0;
                    foreach ($userIntervals as $interval) {
                        $taskTime += (int) Carbon::parse($interval->end_at)->diffInSeconds($interval->start_at, true);
                    }

                    $firstUserInterval = $userIntervals->first();
                    $lastUserInterval = $userIntervals->last();

                    $task = [
                        'id' => $taskId,
                        'user_id' => $userId,
                        'project_id' => $userIntervals[0]['task']['project_id'],
                        'time' => $taskTime,
                        'start' => Carbon::parse($firstUserInterval->start_at)->toISOString(),
                        'end' => Carbon::parse($lastUserInterval->end_at)->toISOString()
                    ];

                    $totalTime += $taskTime;
                }
                return $task;
            })
            ->values();

        $first = $itemsQuery->get()->first();
        $last = $itemsQuery->get()->last();

        return responder()->success([
            'tasks' => $tasks,
            'total' => [
                'time' => $totalTime,
                'start' => $first ? Carbon::parse($first->start_at)->toISOString() : null,
                'end' => $last ? Carbon::parse($last->end_at)->toISOString() : null,
            ]
        ])->respond();
    }
}
