<?php

namespace Tests\Unit;

use App\Jobs\VerifyAttachmentHash;
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * A worker hands a reserved job to another worker after `retry_after` seconds, so it must exceed
 * the longest job `$timeout`: otherwise a job that is still running is started a second time.
 * VerifyAttachmentHash downloads and hashes whole S3 objects and needs far more than the
 * 60 s worker default.
 */
class QueueRetryAfterTest extends TestCase
{
    private const ENV = 'QUEUE_RETRY_AFTER';

    private const CONNECTIONS = ['database', 'redis', 'beanstalkd'];

    /**
     * Evaluate config/queue.php with QUEUE_RETRY_AFTER set to $value (null = unset).
     */
    private function queueConfig(?string $value): array
    {
        $saved = [$_ENV[self::ENV] ?? null, $_SERVER[self::ENV] ?? null, getenv(self::ENV)];

        try {
            unset($_ENV[self::ENV], $_SERVER[self::ENV]);
            putenv(self::ENV);
            if ($value !== null) {
                $_ENV[self::ENV] = $_SERVER[self::ENV] = $value;
                putenv(self::ENV . "=$value");
            }

            return require config_path('queue.php');
        } finally {
            unset($_ENV[self::ENV], $_SERVER[self::ENV]);
            putenv(self::ENV);
            [$env, $server, $getenv] = $saved;
            if ($env !== null) {
                $_ENV[self::ENV] = $env;
            }
            if ($server !== null) {
                $_SERVER[self::ENV] = $server;
            }
            if ($getenv !== false) {
                putenv(self::ENV . "=$getenv");
            }
        }
    }

    /**
     * Default $timeout of every queued job in app/Jobs that declares one.
     *
     * @return array<class-string, int>
     */
    private function jobTimeouts(): array
    {
        $timeouts = [];
        foreach (Finder::create()->files()->in(app_path('Jobs'))->name('*.php') as $file) {
            $class = 'App\\Jobs\\' . Str::of($file->getRelativePathname())->beforeLast('.php')->replace('/', '\\');
            $timeout = (new ReflectionClass($class))->getDefaultProperties()['timeout'] ?? null;
            if ($timeout !== null) {
                $timeouts[$class] = $timeout;
            }
        }

        return $timeouts;
    }

    public function test_verify_attachment_hash_timeout(): void
    {
        $job = (new ReflectionClass(VerifyAttachmentHash::class))->newInstanceWithoutConstructor();

        $this->assertSame(900, $job->timeout);
        $this->assertLessThan($job->uniqueFor, $job->timeout);
        $this->assertLessThan(config('queue.connections.database.retry_after'), $job->timeout);
    }

    public function test_retry_after_exceeds_every_job_timeout(): void
    {
        $timeouts = $this->jobTimeouts();
        $this->assertArrayHasKey(VerifyAttachmentHash::class, $timeouts);

        foreach (self::CONNECTIONS as $connection) {
            $retryAfter = config("queue.connections.$connection.retry_after");
            foreach ($timeouts as $class => $timeout) {
                $this->assertLessThan($retryAfter, $timeout, "$class timeout vs $connection retry_after");
            }
        }
    }

    public function test_retry_after_defaults_to_960(): void
    {
        $config = $this->queueConfig(null);

        foreach (self::CONNECTIONS as $connection) {
            $this->assertSame(960, $config['connections'][$connection]['retry_after'], $connection);
        }
    }

    public function test_retry_after_reads_env(): void
    {
        $config = $this->queueConfig('1800');

        foreach (self::CONNECTIONS as $connection) {
            $this->assertSame(1800, $config['connections'][$connection]['retry_after'], $connection);
        }
    }
}
