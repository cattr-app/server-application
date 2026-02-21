<?php

namespace App\Helpers;

/**
 * Resolves deprecated IANA timezone identifiers to their canonical equivalents
 * that are accepted by PHP's timezone_identifiers_list() / Laravel's 'timezone' rule.
 *
 * Two-stage resolution:
 *  1. IntlTimeZone::getCanonicalID() – handles ~109 deprecated names automatically.
 *  2. A static fallback map for the remaining ~70 names that IntlTimeZone does not
 *     resolve (e.g. Asia/Calcutta → Asia/Kolkata, Europe/Kiev → Europe/Kyiv).
 */
class TimezoneHelper
{
    /**
     * Deprecated IANA names not resolved by IntlTimeZone::getCanonicalID(),
     * mapped to their current PHP-valid equivalents.
     */
    private const FALLBACK_MAP = [
        'Africa/Asmera'                    => 'Africa/Asmara',
        'America/Argentina/ComodRivadavia' => 'America/Argentina/Catamarca',
        'America/Buenos_Aires'             => 'America/Argentina/Buenos_Aires',
        'America/Catamarca'                => 'America/Argentina/Catamarca',
        'America/Coral_Harbour'            => 'America/Atikokan',
        'America/Cordoba'                  => 'America/Argentina/Cordoba',
        'America/Fort_Wayne'               => 'America/Indiana/Indianapolis',
        'America/Godthab'                  => 'America/Nuuk',
        'America/Indianapolis'             => 'America/Indiana/Indianapolis',
        'America/Jujuy'                    => 'America/Argentina/Jujuy',
        'America/Louisville'               => 'America/Kentucky/Louisville',
        'America/Mendoza'                  => 'America/Argentina/Mendoza',
        'America/Rosario'                  => 'America/Argentina/Cordoba',
        'Asia/Calcutta'                    => 'Asia/Kolkata',
        'Asia/Katmandu'                    => 'Asia/Kathmandu',
        'Asia/Rangoon'                     => 'Asia/Yangon',
        'Asia/Saigon'                      => 'Asia/Ho_Chi_Minh',
        'Atlantic/Faeroe'                  => 'Atlantic/Faroe',
        'Europe/Kiev'                      => 'Europe/Kyiv',
        'Europe/Uzhgorod'                  => 'Europe/Kyiv',
        'Europe/Zaporozhye'                => 'Europe/Kyiv',
        'Pacific/Enderbury'                => 'Pacific/Kanton',
        'Pacific/Ponape'                   => 'Pacific/Pohnpei',
        'Pacific/Truk'                     => 'Pacific/Chuuk',
        'Pacific/Yap'                      => 'Pacific/Chuuk',
        'US/East-Indiana'                  => 'America/Indiana/Indianapolis',
    ];

    /**
     * Normalize a timezone string to a PHP-valid IANA identifier.
     *
     * Returns the original value unchanged if:
     *  - it is already valid,
     *  - it is null / empty,
     *  - no canonical form can be determined.
     */
    public static function normalize(?string $timezone): ?string
    {
        if (empty($timezone)) {
            return $timezone;
        }

        // Already valid – nothing to do.
        if (in_array($timezone, timezone_identifiers_list(), true)) {
            return $timezone;
        }

        // Stage 1: IntlTimeZone (available when the intl extension is loaded).
        if (class_exists(\IntlTimeZone::class)) {
            $canonical = \IntlTimeZone::getCanonicalID($timezone);
            if ($canonical && $canonical !== $timezone && in_array($canonical, timezone_identifiers_list(), true)) {
                return $canonical;
            }
        }

        // Stage 2: Static fallback map.
        return self::FALLBACK_MAP[$timezone] ?? $timezone;
    }
}
