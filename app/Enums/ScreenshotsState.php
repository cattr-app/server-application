<?php

namespace App\Enums;

enum ScreenshotsState: int
{
    case ANY = -1;
    case FORBIDDEN = 0;
    case REQUIRED = 1;
    case OPTIONAL = 2;

    public function title(): string
    {
        return match ($this) {
            self::ANY,
            self::FORBIDDEN,
            self::REQUIRED,
            self::OPTIONAL => strtolower($this->name),
        };
    }

    public function inherited(): bool
    {
        return match ($this) {
            self::FORBIDDEN,
            self::REQUIRED => true,
            self::ANY,
            self::OPTIONAL => false,
        };
    }
    
    public static function tryFromString(string $value): ScreenshotsState
    {
        if (is_null($value)) {
            return self::ANY;
        }
        
        foreach (self::cases() as $state) {
            if ($state->title() === strtolower($value)) {
                return $state;
            }
        }

        return self::ANY;
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'name' => $this->title(),
        ];
    }
}