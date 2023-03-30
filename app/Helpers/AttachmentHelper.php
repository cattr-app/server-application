<?php

namespace App\Helpers;

use App\Http\Requests\Attachment\CreateAttachmentRequest;
use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Validation\Rule;

class AttachmentHelper
{
    protected const ABLE_BY = [
        Task::TABLE => Task::class,
        TaskComment::TABLE => TaskComment::class
    ];

    public static function getProjectId(CreateAttachmentRequest $request): int
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

    public static function getCreateRequestValidationRules(CreateAttachmentRequest $request): array
    {
        $able_type = $request->get('attachmentable_type');

        $ruleForAbleId = self::isAble($able_type) ? Rule::exists($able_type, 'id')
            : Rule::in(['DUMMY_VALUE']); // TODO: write something better to make validation fail

        return [
            'attachment' => 'file|required|max:2000', // TODO: take max file size from php config?
            'attachmentable_id' => ['required', 'integer', $ruleForAbleId],
            'attachmentable_type' => ['required', Rule::in(self::getAbles())],
        ];
    }
}
