<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

final class MetricsController
{
    public function __invoke(CollectorRegistry $registry): Response
    {
        $body = (new RenderTextFormat())->render($registry->getMetricFamilySamples());

        return new Response($body, 200, ['Content-Type' => RenderTextFormat::MIME_TYPE]);
    }
}
