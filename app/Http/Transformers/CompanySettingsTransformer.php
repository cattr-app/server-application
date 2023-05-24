<?php

namespace App\Http\Transformers;

use App\Enums\ScreenshotsState;
use Filter;
use Flugg\Responder\Transformers\Transformer;

class CompanySettingsTransformer extends Transformer
{
    public function transform(array $input): array
    {
        return Filter::process('transformation.item.list.settings', [
            'timezone' => $input['timezone'] ?? null,
            'language' => $input['language'] ?? null,
            'work_time' => (int)($input['work_time'] ?? 0),
            'color' => $input['color'] ?? [],
            'internal_priorities' => $input['internal_priorities'] ?? [],
            'heartbeat_period' => config('app.user_activity.online_status_time'),
            'auto_thinning' => (bool)($input['auto_thinning'] ?? false),
            'screenshots_state' => ScreenshotsState::tryFrom($input['screenshots_state'])?->value ?? ScreenshotsState::REQUIRED->value,
            'screenshots_state_inherit' => ScreenshotsState::tryFrom($input['screenshots_state'])->inherited(),
            'env_screenshots_state' => ScreenshotsState::tryFromString(config('app.screenshots_state'))->value,
            'default_priority_id' => (int)($input['default_priority_id'] ?? 2),
        ]);
    }
}
