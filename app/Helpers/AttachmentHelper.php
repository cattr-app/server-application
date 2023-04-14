<?php

namespace App\Helpers;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\UploadedFile;
use Str;

class AttachmentHelper
{
    protected const TYPE_PLACEHOLDER = '__ABLE_TYPE__';

    protected const MAX_FILE_NAME_LENGTH = 255; // name.extension

//  TODO: move it to AttachmentServiceProvider
    protected static array $ABLE_BY = [
        Task::TYPE => Task::class,
        TaskComment::TYPE => TaskComment::class
    ];

    public static function isAble(string $able_type): bool
    {
        return array_key_exists($able_type, self::$ABLE_BY);
    }

    public static function getAbles(): array
    {
        return array_keys(self::$ABLE_BY);
    }

    public static function getFileName(UploadedFile $file): string
    {
        $fileName = $file->getClientOriginalName();
        $extension = ".{$file->extension()}";
        $maxNameLength = (self::MAX_FILE_NAME_LENGTH - Str::length($extension));
        if (Str::length($fileName) > $maxNameLength || Str::endsWith($fileName, $extension) === false){
            $fileName = Str::substr($fileName, 0, $maxNameLength) . $extension;
        }

        return $fileName;
    }

    public static function getEvents($parentCreated, $parentUpdated, $parentDeleted): array
    {
        return self::getMappedEvents([
            'event.after.action.' . self::TYPE_PLACEHOLDER . '.create'  => $parentCreated,
            'event.after.action.' . self::TYPE_PLACEHOLDER . '.edit'    => $parentUpdated,
            'event.after.action.' . self::TYPE_PLACEHOLDER . '.destroy' => $parentDeleted,
        ]);
    }

    public static function getFilters(array $addRequestRulesForParent): array
    {
        return self::getMappedEvents([
            'filter.validation.' . self::TYPE_PLACEHOLDER . '.edit'     => $addRequestRulesForParent,
            'filter.validation.' . self::TYPE_PLACEHOLDER . '.create'   => $addRequestRulesForParent,
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
