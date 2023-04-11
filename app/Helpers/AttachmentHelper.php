<?php

namespace App\Helpers;

use App\Models\Task;
use App\Models\TaskComment;

class AttachmentHelper
{
    protected const TYPE_PLACEHOLDER = '__ABLE_TYPE__';

//  TODO: move it to AppServiceProvider and populate this array at HasAttachments trait initialization?
    protected const ABLE_BY = [
        Task::TABLE => Task::class,
        TaskComment::TABLE => TaskComment::class
    ];

    public static function isAble(string $able_type): bool
    {
        return array_key_exists($able_type, self::ABLE_BY);
    }

    public static function getAbles(): array
    {
        return array_keys(self::ABLE_BY);
    }

    public static function getEvents($created, $updated, $deleted): array
    {
        return self::getMappedEvents([
            'event.after.action.' . self::TYPE_PLACEHOLDER . '.create'  => $created,
            'event.after.action.' . self::TYPE_PLACEHOLDER . '.edit'    => $updated,
            'event.after.action.' . self::TYPE_PLACEHOLDER . '.destroy' => $deleted,
        ]);
    }

    public static function getFilters(array $requestRules): array
    {
        return self::getMappedEvents([
            'filter.validation.' . self::TYPE_PLACEHOLDER . '.edit'     => $requestRules,
            'filter.validation.' . self::TYPE_PLACEHOLDER . '.create'   => $requestRules,
        ]);
    }

    private static function getMappedEvents(array $events): array
    {
        return array_merge(...array_map(
            static fn($event, $function) => self::eventMapper($event, $function),
            array_keys($events),
            array_values($events)
        ));
    }

    private static function eventMapper($event, $function): array
    {
        return array_reduce(self::getAbles(), static function ($carry, $class) use ($function, $event) {
            $carry[str_replace(self::TYPE_PLACEHOLDER, $class, $event)] = $function;
            return $carry;
        }, []);
    }
}
