<?php

namespace App\Helpers;

use TypeError;

class Version
{
    private const MAJOR = 'major';
    private const MINOR = 'minor';
    private const PATCH = 'patch';

    public const TYPES = [
        self::MAJOR,
        self::MINOR,
        self::PATCH
    ];

    private static function readComposerJson(): array
    {
        return json_decode(
            file_get_contents(base_path('composer.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    private static function writeComposerJson(array $content): void
    {
        file_put_contents(
            base_path('composer.json'),
            json_encode(
                $content,
                JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
                512
            )
        );
    }

    private static function set(string $version): string
    {
        $content = self::readComposerJson();
        $content['version'] = $version;
        self::writeComposerJson($content);
        return $version;
    }

    private static function explodeVersion(string $version): array
    {
        $explodedVersion = explode('.', $version);

        return [
            self::MAJOR => (int)$explodedVersion[0],
            self::MINOR => (int)$explodedVersion[1],
            self::PATCH => (int)$explodedVersion[2]
        ];
    }

    /**
     * @throws TypeError
     */
    private static function validateType(string $type): void
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new TypeError(
                'Invalid version type. Available types: ' . implode(', ', self::TYPES)
            );
        }
    }

    public static function increment(string $type): string
    {
        self::validateType($type);

        $version = self::explodeVersion(self::get());
        ++$version[$type];

        return self::set(implode('.', $version));
    }

    public static function decrement(string $type): string
    {
        self::validateType($type);

        $version = self::explodeVersion(self::get());
        if ($version[$type] !== 0) {
            --$version[$type];
        }

        return self::set(implode('.', $version));
    }

    public static function incrementMajor(): string
    {
        return self::increment(self::MAJOR);
    }

    public static function incrementMinor(): string
    {
        return self::increment(self::MINOR);
    }

    public static function incrementPatch(): string
    {
        return self::increment(self::PATCH);
    }

    public static function decrementMajor(): string
    {
        return self::decrement(self::MAJOR);
    }

    public static function decrementMinor(): string
    {
        return self::decrement(self::MINOR);
    }

    public static function decrementPatch(): string
    {
        return self::decrement(self::PATCH);
    }

    public static function get(): string
    {
        return self::readComposerJson()['version'];
    }
}
