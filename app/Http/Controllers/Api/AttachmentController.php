<?php

namespace App\Http\Controllers\Api;

use App\Helpers\AttachmentHelper;
use App\Http\Requests\Attachment\CreateAttachmentRequest;
use App\Models\Attachment;
use Filter;
use Illuminate\Http\JsonResponse;
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
        dump(['request' => $request]);
        Filter::listen(Filter::getRequestFilterName(), static function ($attachment) use ($request) {
            $attachment['user_id'] = auth()->user()->id;
            $attachment['project_id'] = AttachmentHelper::getProjectId($request);

            dump($attachment);
            return $attachment;
        });

//        TODO: when record added successfully add file using record uuid as its name /project_id/uuid
        return $this->_create($request);
    }
}
