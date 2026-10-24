<?php

namespace Tests\Feature\Actuator;

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Tests\TestCase;

class MetricsTest extends TestCase
{
    public function test_metrics_endpoint_renders_prometheus_text_format(): void
    {
        $registry = $this->app->make(CollectorRegistry::class);
        $registry->getOrRegisterCounter('cattr_test', 'requests_total', 'Test counter', ['kind'])->inc(['feature']);

        $response = $this->get(url('/actuator/prometheus'));

        $response->assertOk();
        $this->assertStringStartsWith(RenderTextFormat::MIME_TYPE, (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('cattr_test_requests_total{kind="feature"} 1', $response->getContent());
    }

    public function test_health_endpoints(): void
    {
        $this->get(url('/actuator/health/liveness'))->assertNoContent();
        $this->get(url('/actuator/health/readiness'))->assertNoContent();
    }
}
