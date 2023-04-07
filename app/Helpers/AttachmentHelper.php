<?php

namespace App\Helpers;

use App\Http\Requests\Attachment\ConnectAttachmentRequest;
use App\Models\Task;
use App\Models\TaskComment;

class AttachmentHelper
{
    protected const TYPE_PLACEHOLDER = '__ABLE_TYPE__';

    protected const ABLE_BY = [
        Task::TABLE => Task::class,
        TaskComment::TABLE => TaskComment::class
    ];

    public static function getProjectId(ConnectAttachmentRequest $request): int
    {
        $able_type = $request->get('attachmentable_type');
        $able_id = $request->get('attachmentable_id');
        /**
         * @var $model Task|TaskComment
        */
        $model = self::ABLE_BY[$able_type];

        return $model::firstWhere('id', '=', $able_id)->getProjectId();
    }

    public static function isAble(string $able_type): bool
    {
        return array_key_exists($able_type, self::ABLE_BY);
    }

    public static function getAbles(): array
    {
        return array_keys(self::ABLE_BY);
    }

    public static function getEventsAndFilters($created, $updated, $deleted, $requestRules): array
    {
        $events = [
            'event.after.action.' . self::TYPE_PLACEHOLDER . '.create'  => $created,
            'event.after.action.' . self::TYPE_PLACEHOLDER . '.edit'    => $updated,
            'event.after.action.' . self::TYPE_PLACEHOLDER . '.destroy' => $deleted,
            'filter.validation.' . self::TYPE_PLACEHOLDER . '.edit'     => $requestRules,
            'filter.validation.' . self::TYPE_PLACEHOLDER . '.create'   => $requestRules,
//            'eloquent.created: ' => $created,
//            'eloquent.updated: ' => $updated,
//            'eloquent.deleted: ' => $deleted,
        ];

        return array_merge(...array_map(
            static fn($event, $function) => self::eventMapper($event, $function),
            array_keys($events),
            array_values($events)
        ));
    }

    private static function eventMapper($event, $function): array
    {
        return array_reduce(array_keys(self::ABLE_BY), static function ($carry, $class) use ($function, $event) {
            $carry[str_replace(self::TYPE_PLACEHOLDER, $class, $event)] = $function;
            return $carry;
        }, []);
    }
}
