<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\Adapter;
use Prometheus\Storage\APC;
use Prometheus\Storage\APCng;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;

/**
 * Wires promphp/prometheus_client_php into the container.
 *
 * Configuration lives in config/prometheus.php and keeps the PROMETHEUS_* environment
 * variables that were used with the previous exporter package.
 */
class PrometheusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Adapter::class, static function (Application $app): Adapter {
            $driver = (string) $app['config']->get('prometheus.storage_adapter', 'memory');
            $config = (array) $app['config']->get("prometheus.storage_adapters.$driver", []);

            return match ($driver) {
                'memory' => new InMemory(),
                'apc' => new APC(),
                'apcu', 'apcng' => new APCng(),
                'redis' => self::makeRedisAdapter($config),
                default => throw new InvalidArgumentException("Unsupported prometheus storage adapter [$driver]"),
            };
        });

        $this->app->singleton(
            CollectorRegistry::class,
            static fn(Application $app): CollectorRegistry => new CollectorRegistry(
                $app->make(Adapter::class),
                (bool) $app['config']->get('prometheus.default_metrics', true),
            ),
        );
    }

    private static function makeRedisAdapter(array $config): Redis
    {
        $prefix = $config['prefix'] ?? null;
        unset($config['prefix']);

        if ($prefix !== null) {
            Redis::setPrefix((string) $prefix);
        }

        return new Redis($config);
    }
}
