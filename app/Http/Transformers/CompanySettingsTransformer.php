<?php

namespace App\Http\Transformers;

use App\Enums\ScreenshotsState;
use Filter;
use Flugg\Responder\Transformers\Transformer;

class CompanySettingsTransformer extends Transformer
{
    public function transform(array $input): array
    {
        $env_screenshots_state = in_array(env("SCREENSHOTS_STATE"), ScreenshotsState::valuesAsString(), true);

        return Filter::process('transformation.item.list.settings', [
            'timezone' => $input['timezone'] ?? null,
            'language' => $input['language'] ?? null,
            'work_time' => (int)($input['work_time'] ?? 0),
            'color' => $input['color'] ?? [],
            'internal_priorities' => $input['internal_priorities'] ?? [],
            'heartbeat_period' => config('app.user_activity.online_status_time'),
            'auto_thinning' => (bool)($input['auto_thinning'] ?? false),
            'screenshots_state' => ScreenshotsState::tryFrom($input['screenshots_state'])?->value ?? ScreenshotsState::REQUIRED->value,
            'screenshots_state_inherit' => ScreenshotsState::tryFrom($input['screenshots_state'])->inherit(),
            'env_screenshots_state' => $env_screenshots_state ? ScreenshotsState::tryFrom(env("SCREENSHOTS_STATE"))->value : '',
            'default_priority_id' => (int)($input['default_priority_id'] ?? 2),
        ]);
    }
}
