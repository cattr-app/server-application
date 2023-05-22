<?php

namespace App\Enums;

enum ScreenshotsState: int
{
    case REQUIRED = 1;
    case OPTIONAL = 2;
    case FORBIDDEN = 0;

    public function title(): string
    {
        return match ($this) {
            self::FORBIDDEN => 'forbidden',
            self::REQUIRED => 'required',
            self::OPTIONAL => 'optional',
        };
    }

    public function inherit(): bool
    {
        return match ($this) {
            self::FORBIDDEN => true,
            self::REQUIRED => true,
            self::OPTIONAL => false,
        };
    }

    public static function valuesAsString(): array
    {
        return array_map(fn($i) => (string)$i->value, self::cases());
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'name' => $this->title(),
        ];
    }
}