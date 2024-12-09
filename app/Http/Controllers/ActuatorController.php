<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

final class ActuatorController
{
    public function __invoke(): JsonResponse
    {
        return responder()->success()->respond(204);
    }
}
