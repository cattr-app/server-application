<?php

namespace App\Http\Transformers;

use App\Enums\ScreenshotsState;
use App\Enums\WebcamState;
use Filter;
use Flugg\Responder\Transformers\Transformer;

class CompanySettingsTransformer extends Transformer
{
    protected const DEFAULT_SCREENSHOTS_STATE = ScreenshotsState::REQUIRED;
    protected const DEFAULT_WEBCAM_STATE = WebcamState::OPTIONAL;

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
            'screenshots_state' => (isset($input['screenshots_state'])
                ? ScreenshotsState::tryFrom($input['screenshots_state']) ?? static::DEFAULT_SCREENSHOTS_STATE
                : static::DEFAULT_SCREENSHOTS_STATE
            )->value,
            'env_screenshots_state' => (ScreenshotsState::tryFromString(config('app.screenshots_state')) ?? ScreenshotsState::ANY)->value,
            'default_priority_id' => (int)($input['default_priority_id'] ?? 2),
            'webcam_state' => (isset($input['webcam_state'])
                ? WebcamState::tryFrom($input['webcam_state']) ?? static::DEFAULT_WEBCAM_STATE
                : static::DEFAULT_WEBCAM_STATE
            )->value,
            'env_webcam_state' => (WebcamState::tryFromString(config('app.webcam_state')) ?? WebcamState::ANY)->value,
        ]);
    }
}
