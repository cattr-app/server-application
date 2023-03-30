<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Attachment\CreateAttachmentRequest;
use App\Models\Attachment;
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
        return $this->_create($request);
    }
}
