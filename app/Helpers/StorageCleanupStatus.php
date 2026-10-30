<?php

namespace App\Helpers;

use Cache;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Status of the screenshot cleanup, shared by the web container that queues it and the queue worker
 * (or scheduler) that runs it.
 *
 * Kept in the default cache store, not in the per-process octane store: the default store is shared by the
 * containers (file cache on the shared PVC in pvc mode, Redis in s3 mode), and also provides the locks.
 */
class StorageCleanupStatus
{
    public const IDLE = 'idle';
    public const QUEUED = 'queued';
    public const RUNNING = 'running';
    public const COMPLETED = 'completed';
    public const SKIPPED = 'skipped';
    public const FAILED = 'failed';

    public const ACTIVE = [self::QUEUED, self::RUNNING];

    private const STATUS_KEY = 'storage_cleanup:status';
    private const LAST_KEY = 'storage_cleanup:last';
    private const STATE_LOCK = 'storage_cleanup:state';
    private const RUN_LOCK = 'storage_cleanup:run';

    /**
     * Lifetime of the run lock: released by the run itself or by the failed job,
     * the TTL only frees it after the whole container is gone.
     */
    private const RUN_LOCK_TTL = 3600;

    /**
     * Current status with stale states resolved: a request that no worker picked up within
     * cleaner.queued_timeout, or a run without a heartbeat within cleaner.running_timeout, is reported as failed.
     *
     * @return array{status: string, requested_at: ?string, started_at: ?string, finished_at: ?string}
     */
    public static function get(): array
    {
        $state = self::stored();

        if (self::isStale($state)) {
            $state['status'] = self::FAILED;
        }

        return [
            'status' => $state['status'],
            'requested_at' => $state['requested_at'],
            'started_at' => $state['started_at'],
            'finished_at' => $state['finished_at'],
        ];
    }

    /**
     * Time of the last successful cleanup.
     */
    public static function last(): ?string
    {
        return Cache::get(self::LAST_KEY);
    }

    /**
     * Whether a cleanup is running now, stale runs excluded.
     */
    public static function isRunning(): bool
    {
        return self::get()['status'] === self::RUNNING;
    }

    /**
     * Marks a new run as queued unless one is already queued or running.
     *
     * @return string|null id of the new run, null if a cleanup is already queued or running
     */
    public static function queue(): ?string
    {
        return Cache::lock(self::STATE_LOCK, 10)->block(5, static function () {
            $state = self::stored();

            // A stale run is replaced, but its run lock is left alone: a run without a heartbeat is not
            // necessarily dead, and the new run waits for the lock instead of running next to it
            if (in_array($state['status'], self::ACTIVE, true) && !self::isStale($state)) {
                return null;
            }

            $runId = (string)Str::uuid();

            self::put(self::QUEUED, [
                'run_id' => $runId,
                'requested_at' => now()->toIso8601String(),
                'started_at' => null,
                'finished_at' => null,
            ]);

            return $runId;
        });
    }

    /**
     * Whether the requested run is the current one and has not started yet.
     */
    public static function isCurrent(string $runId): bool
    {
        $state = self::stored();

        return $state['run_id'] === $runId && $state['status'] === self::QUEUED;
    }

    /**
     * Lock held for the whole run, owned by the run: only that run can release it.
     */
    public static function runLock(string $runId): Lock
    {
        return Cache::lock(self::RUN_LOCK, self::RUN_LOCK_TTL, $runId);
    }

    /**
     * Marks the run as running, called by the run holding the run lock.
     *
     * A requested run is started only while it is the current one: a request no worker picked up in time
     * can be replaced by a new one, and its job, picked up late, must not take over the new request.
     *
     * @return bool false if the requested run has been replaced and must not start
     */
    public static function markRunning(string $runId, bool $requested = false): bool
    {
        return Cache::lock(self::STATE_LOCK, 10)->block(5, static function () use ($runId, $requested) {
            $state = self::stored();
            $current = $state['run_id'] === $runId;

            if ($requested && !$current) {
                return false;
            }

            self::put(self::RUNNING, [
                'run_id' => $runId,
                // A scheduled run was not requested: the request time of a previous run must not leak into it
                'requested_at' => $current ? $state['requested_at'] : null,
                'started_at' => now()->toIso8601String(),
                'finished_at' => null,
            ]);

            return true;
        });
    }

    /**
     * Heartbeat of a running cleanup: proves the run is still alive.
     */
    public static function touch(string $runId): void
    {
        self::putOwned($runId, self::RUNNING);
    }

    /**
     * The run has freed the space; the time of the last cleanup is updated even if the run has been replaced.
     */
    public static function markCompleted(string $runId): void
    {
        $now = now()->toIso8601String();

        Cache::forever(self::LAST_KEY, $now);
        self::putOwned($runId, self::COMPLETED, ['finished_at' => $now]);
    }

    /**
     * The run was not needed: thinning threshold not reached or the storage does not support it.
     */
    public static function markSkipped(string $runId): void
    {
        self::putOwned($runId, self::SKIPPED, ['finished_at' => now()->toIso8601String()]);
    }

    public static function markFailed(string $runId): void
    {
        self::putOwned($runId, self::FAILED, ['finished_at' => now()->toIso8601String()]);
    }

    /**
     * Fails a run that could not clean up after itself (killed on timeout, never started, not dispatched).
     *
     * Only touches the status and the lock of that run: a job of a lost run can fail long after a new run
     * has been requested in its place (a crashed worker's reservation becomes available after retry_after).
     */
    public static function failRun(string $runId): void
    {
        Cache::lock(self::STATE_LOCK, 10)->block(5, static function () use ($runId) {
            $state = self::stored();

            if ($state['run_id'] === $runId && in_array($state['status'], self::ACTIVE, true)) {
                self::put(self::FAILED, ['finished_at' => now()->toIso8601String()]);
            }
        });

        Cache::restoreLock(self::RUN_LOCK, $runId)->release();
    }

    /**
     * Status as stored, stale states not resolved.
     */
    private static function stored(): array
    {
        return array_merge([
            'status' => self::IDLE,
            'run_id' => null,
            'requested_at' => null,
            'started_at' => null,
            'finished_at' => null,
            'updated_at' => null,
        ], (array)Cache::get(self::STATUS_KEY, []));
    }

    /**
     * Writes the status, keeping the stored attributes not given; updated_at is the heartbeat for stale detection.
     */
    private static function put(string $status, array $attributes = []): void
    {
        Cache::forever(self::STATUS_KEY, array_merge(self::stored(), $attributes, [
            'status' => $status,
            'updated_at' => now()->toIso8601String(),
        ]));
    }

    /**
     * Writes the status only while it belongs to the run: a replaced run that is still alive must not overwrite
     * the status of the run that replaced it.
     */
    private static function putOwned(string $runId, string $status, array $attributes = []): void
    {
        Cache::lock(self::STATE_LOCK, 10)->block(5, static function () use ($runId, $status, $attributes) {
            if (self::stored()['run_id'] === $runId) {
                self::put($status, $attributes);
            }
        });
    }

    /**
     * Whether a queued or running state has outlived its timeout without an update.
     */
    private static function isStale(array $state): bool
    {
        $timeout = match ($state['status']) {
            self::QUEUED => config('cleaner.queued_timeout'),
            self::RUNNING => config('cleaner.running_timeout'),
            default => null,
        };

        return $timeout !== null
            && ($state['updated_at'] === null
                || Carbon::parse($state['updated_at'])->addSeconds((int)$timeout)->isPast());
    }
}
