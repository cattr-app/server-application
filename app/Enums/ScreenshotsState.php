<?php

namespace App\Enums;

use Settings;

enum ScreenshotsState: int
{
    case ANY = -1;
    case FORBIDDEN = 0;
    case REQUIRED = 1;
    case OPTIONAL = 2;

    public function title(): string
    {
        return strtolower($this->name);
    }

    public function mustBeInherited(): bool
    {
        return $this === self::FORBIDDEN || $this === self::REQUIRED;
    }

    public static function tryFromString(string $value): ?ScreenshotsState
    {
        try {
            return constant(__CLASS__ . "::" . strtoupper($value));
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'name' => $this->title(),
        ];
    }

    public static function states(): array
    {
        return array_map(fn ($case) => $case->toArray(), self::cases());
    }

    public static function createFrom(null|int|string|ScreenshotsState $value): ?ScreenshotsState
    {
        return match (true) {
            !isset($value) => null,
            is_numeric($value) => static::tryFrom((int)$value),
            is_string($value) => static::tryFromString($value),
            $value instanceof ScreenshotsState => static::tryFrom($value->value),
            default => static::tryFrom($value),
        };
    }

    public static function withGlobalOverrides(null|int|string|ScreenshotsState $value): ?ScreenshotsState
    {
        foreach ([
            ScreenshotsState::createFrom(config('app.screenshots_state')),
            ScreenshotsState::createFrom(Settings::scope('core')->get('screenshots_state')),
        ] as $globalOverride) {
            if (isset($globalOverride) && $globalOverride->mustBeInherited()) {
                return $globalOverride;
            }
        }

        return static::createFrom($value);
    }

    public static function getNormalizedValue(null|int|string|ScreenshotsState $value): ?int
    {
        return static::createFrom($value)?->value;
    }
}
