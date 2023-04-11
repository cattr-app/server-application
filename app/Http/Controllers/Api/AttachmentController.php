<?php

namespace App\Http\Controllers\Api;

use App\Enums\AttachmentStatus;
use App\Helpers\AttachmentHelper;
use App\Http\Requests\Attachment\CreateAttachmentRequest;
use App\Models\Attachment;
use Event;
use Filter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Str;
use Throwable;

class AttachmentController extends ItemController
{
    protected const MODEL = Attachment::class;

    /**
     * @param CreateAttachmentRequest $request
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function create(CreateAttachmentRequest $request): JsonResponse
    {
        Filter::listen(Filter::getRequestFilterName(), static function ($request) {
            /**
             * @var $file UploadedFile
             */
            $file = $request['attachment'];
            $request['user_id'] = auth()->user()->id;
            $request['status'] = AttachmentStatus::NOT_ATTACHED;

            $fileName = $file->getClientOriginalName();
            $fileExt = ".{$file->extension()}";
            $maxNameLength = (255 - Str::length($fileExt));
            if (Str::length($fileName) > $maxNameLength || Str::endsWith($fileName, $fileExt) === false){
                $fileName = Str::substr($fileName, 0, $maxNameLength) . $fileExt;
            }

            $request['original_name'] = $fileName;
            $request['mime_type'] = $file->getClientMimeType();
            $request['extension'] = ltrim($fileExt, '.');
            $request['size'] = $file->getSize();

            return $request;
        });

        Event::listen(Filter::getAfterActionEventName(), static function (Attachment $attachment, array $requestData) {
            /**
             * @var $file UploadedFile
             */
            $file = $requestData['attachment'];
            $file->storeAs(
                "user/{$attachment->user_id}", "{$attachment->id}.{$attachment->extension}"
            );

            dump([
                'attachment' => $attachment,
                'requestData' => $requestData
            ]);
        });

        return $this->_create($request);
    }
}
