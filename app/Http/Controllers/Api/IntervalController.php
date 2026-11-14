<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ScreenshotService;
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
use App\Http\Requests\Interval\ShowIntervalRequest;
use App\Http\Requests\Interval\TrackAppRequest;
use App\Http\Requests\Interval\UploadOfflineIntervalsRequest;
use App\Http\Requests\Interval\UploadOfflineScreenshotsRequest;
use App\Jobs\AssignAppsToTimeInterval;
use App\Models\Task;
use App\Models\TrackedApplication;
use App\Models\User;
use CatEvent;
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
use Illuminate\Support\Number;
use Illuminate\Validation\ValidationException;
use MessagePack\MessagePack;
use phpseclib4\Crypt\RSA;
use phpseclib4\Exception\BadDecryptionException;
use phpseclib4\Exception\KeyConstraintException;
use Settings;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Storage;
use Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use RuntimeException;
use Throwable;
use Validator;
use ZipArchive;

class IntervalController extends ItemController
{
    protected const MODEL = TimeInterval::class;

    private const OFFLINE_SCREENSHOTS_EXTRACTION_CHUNK_SIZE = 64 * 1024;

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
                    AssignAppsToTimeInterval::dispatch($data);
                }
            }
        );

        return $this->_create($request);
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
     * Attaches the screenshots recorded while the desktop application was offline to their intervals. The `file`
     * field (`multipart/form-data`) is a ZIP archive named `*.cattr` with a flat list of files named
     * `<user_id>_<screenshot_id>` (the screenshot ID is a UUID). An archive that exceeds the configured number of
     * files or unpacked size, or contains directories, is rejected with 422; an archive that cannot be read, or
     * a file with another name, with 400.
     *
     * Each file is attached to the interval of that user with that `screenshot_id`, if the current user may create such
     * an interval. The response is 200 and reports each file separately: `interval` is `null` if no suitable interval
     * was found.
     *
     * Any authenticated user may call it.
     *
     * @response array{status: int, success: true, data: list<array{
     *     interval: \App\Models\TimeInterval|null,
     *     user_id: int,
     *     screenshot_id: string,
     *     message: string,
     *     success: bool,
     * }>}
     */
    public function uploadOfflineScreenshots(UploadOfflineScreenshotsRequest $request): JsonResponse
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

        // Before the temporary directory is created: a rejected archive leaves nothing behind.
        $this->validateOfflineScreenshotsArchive($zip);

        // The archive is extracted to the local disk of this web container and processed within the request,
        // so the queue worker does not need access to these files. The directory is removed when the request
        // ends, also on failure; leftovers of interrupted requests are removed by cattr:tmp:clean.
        $temporaryDirectory = (new TemporaryDirectory())
            ->location(Storage::disk('local')->path('tmp'))->force()->create();

        try {
            $this->extractOfflineScreenshotsArchive($zip, $temporaryDirectory->path());
            $zip->close();

            $creationResult = $this->attachOfflineScreenshots(
                'tmp/' . basename($temporaryDirectory->path()),
                $request->user(),
            );
        } finally {
            $temporaryDirectory->delete();
        }

        return responder()->success($creationResult)->respond();
    }

    /**
     * Rejects the archive before anything is extracted or attached if it cannot be processed within the request
     * (too many entries or too large when unpacked) or if an entry is not a plain file name.
     *
     * The desktop application exports a flat list of `<user_id>_<screenshot_id>` entries without directories.
     *
     * @throws ValidationException
     */
    private function validateOfflineScreenshotsArchive(ZipArchive $zip): void
    {
        $maxFiles = (int)config('app.offline_screenshots.max_files');
        $maxSize = (int)config('app.offline_screenshots.max_size');

        $fileCount = $zip->count();
        if ($fileCount > $maxFiles) {
            throw ValidationException::withMessages([
                'file' => __('validation.offline-sync.too_many_files', ['count' => $fileCount, 'max' => $maxFiles]),
            ]);
        }

        $totalSize = 0;
        for ($index = 0; $index < $fileCount; $index++) {
            $name = $zip->getNameIndex($index);
            $stat = $zip->statIndex($index);

            if (!is_string($name) || $stat === false || !self::isPlainArchiveEntryName($name)) {
                throw ValidationException::withMessages([
                    'file' => __('validation.offline-sync.wrong_file_name'),
                ]);
            }

            $totalSize += $stat['size'];
            if ($totalSize > $maxSize) {
                throw self::offlineScreenshotsArchiveTooLarge($maxSize);
            }
        }
    }

    /**
     * Extracts the validated archive into the directory, counting the bytes that are actually written.
     *
     * The sizes checked by validateOfflineScreenshotsArchive() come from the zip headers and can be forged, while
     * ZipArchive::extractTo() writes whatever the entries decompress to. Here no more than the configured total
     * size is ever written: each entry is copied in chunks of at most the remaining budget plus one byte, and the
     * archive is rejected as soon as that extra byte is read.
     *
     * @throws ValidationException
     */
    private function extractOfflineScreenshotsArchive(ZipArchive $zip, string $directory): void
    {
        $maxSize = (int)config('app.offline_screenshots.max_size');
        $remaining = $maxSize;

        $fileCount = $zip->count();
        for ($index = 0; $index < $fileCount; $index++) {
            // The names were checked to be plain file names by validateOfflineScreenshotsArchive()
            $name = $zip->getNameIndex($index);
            $stat = $zip->statIndex($index);
            $source = $name === false || $stat === false ? false : $zip->getStreamIndex($index);
            abort_if($source === false, 400, __('Cannot open file.'));
            $written = 0;

            try {
                $target = fopen($directory . DIRECTORY_SEPARATOR . $name, 'wb');
                if ($target === false) {
                    throw new RuntimeException("Unable to create the extracted file for archive entry $index");
                }
                try {
                    while (!feof($source)) {
                        // Undecodable compressed data is reported as a warning and a failed read
                        $chunk = @fread($source, min(self::OFFLINE_SCREENSHOTS_EXTRACTION_CHUNK_SIZE, $remaining + 1));
                        abort_if($chunk === false, 400, __('Cannot open file.'));

                        if (strlen($chunk) > $remaining) {
                            throw self::offlineScreenshotsArchiveTooLarge($maxSize);
                        }

                        // A short write (e.g. a full disk) must not leave a truncated screenshot behind
                        if (fwrite($target, $chunk) !== strlen($chunk)) {
                            throw new RuntimeException("Unable to write the extracted file for archive entry $index");
                        }
                        $remaining -= strlen($chunk);
                        $written += strlen($chunk);
                    }
                } finally {
                    fclose($target);
                }

                // A damaged entry can end early without a read error; never pass on a truncated screenshot
                abort_if($written !== $stat['size'], 400, __('Cannot open file.'));
            } finally {
                fclose($source);
            }
        }
    }

    private static function offlineScreenshotsArchiveTooLarge(int $maxSize): ValidationException
    {
        return ValidationException::withMessages([
            'file' => __('validation.offline-sync.too_large', [
                'max' => Number::fileSize($maxSize, maxPrecision: 2),
            ]),
        ]);
    }

    /**
     * A plain file name: not `.`, no directories, no parent references, no absolute or drive paths and no NUL bytes.
     */
    private static function isPlainArchiveEntryName(string $name): bool
    {
        return $name !== ''
            && $name !== '.'
            && strpbrk($name, "/\\\0") === false
            && !str_contains($name, '..')
            && preg_match('/^[A-Za-z]:/', $name) !== 1;
    }

    /**
     * Saves the extracted offline screenshots synchronously, so that failures are reported in the result.
     *
     * @param string $dirPath Directory with the extracted screenshots, relative to the local disk
     * @param User $uploader Screenshots are attached only to intervals this user may create
     * @return array
     */
    private function attachOfflineScreenshots(string $dirPath, User $uploader): array
    {
        $allScreenshots = Storage::disk('local')->files($dirPath);

        $creationResult = [];

        // Same rule as for the offline intervals upload: whoever may create the interval may attach its screenshot.
        $canAttach = static fn(TimeInterval $interval) => $uploader->can(
            'create',
            [
                TimeInterval::class,
                $interval->user_id,
                $interval->task_id,
                false,
            ],
        );

        foreach ($allScreenshots as $screenshotPath) {
            // File names are <user_id>_<screenshot_uuid>
            abort_unless(
                preg_match('/^(\d+)_(.+)\z/', basename($screenshotPath), $matches) === 1 && Str::isUuid($matches[2]),
                400,
                __('Wrong screenshot file name')
            );

            $userId = (int)$matches[1];
            $screenshotId = $matches[2];

            $interval = TimeInterval::where('user_id', $userId)->where('screenshot_id', $screenshotId)->first();
            // An interval of someone the uploader may not track time for is reported exactly like a missing one
            if ($interval === null || !$canAttach($interval)) {
                $creationResult[] = [
                    'interval' => null,
                    'user_id' => $userId,
                    'screenshot_id' => $screenshotId,
                    'message' => __('validation.offline-sync.cannot_find_interval'),
                    'success' => false
                ];
                continue;
            }
            try {
                $this->screenshotService->saveScreenshot(Storage::disk('local')->path($screenshotPath), $interval);
                $interval->screenshot_id = null;
                $interval->save();

                $creationResult[] = [
                    'interval' => $interval,
                    'user_id' => $userId,
                    'screenshot_id' => $screenshotId,
                    'message' => __('validation.offline-sync.screenshot_attached'),
                    'success' => true
                ];
            } catch (\Exception $e) {
                $creationResult[] = [
                    'interval' => $interval,
                    'user_id' => $userId,
                    'screenshot_id' => $screenshotId,
                    'message' => __('validation.offline-sync.screenshot_not_attached'),
                    'success' => false
                ];
                \Log::error($e);
            }
        }

        return $creationResult;
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
