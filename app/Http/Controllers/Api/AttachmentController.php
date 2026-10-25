<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AttachmentService;
use App\Enums\AttachmentStatus;
use App\Http\Requests\Attachment\CreateAttachmentRequest;
use App\Http\Requests\Attachment\DownloadAttachmentRequest;
use App\Models\Attachment;
use Filter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use URL;

class AttachmentController extends ItemController
{
    protected const MODEL = Attachment::class;

    public function __construct(protected AttachmentService $attachmentService)
    {
    }

    /**
     * @param CreateAttachmentRequest $request
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function create(CreateAttachmentRequest $request): JsonResponse
    {
        Filter::listen(Filter::getActionFilterName(), static function (Attachment $attachment) {
            $attachment->load('user');
            return $attachment;
        });
        return $this->_create($request);
    }

    public function tmpDownload(Request $request, Attachment $attachment): ?StreamedResponse
    {
        if (! $request->hasValidSignature()) {
            abort(401);
        }

        return $this->streamDownloadLogic($attachment);
    }

    public function createTemporaryUrl(DownloadAttachmentRequest $request, Attachment $attachment): string
    {
        // Only verified files are downloadable, on every disk (streamDownloadLogic() checks the same for the
        // local route; a presigned S3 URL bypasses the app, so the check has to happen before issuing it)
        if ($attachment->status !== AttachmentStatus::GOOD) {
            abort(404);
        }

        // query string values arrive as strings, Carbon 3 rejects them
        $expiresAt = now()->addSeconds((int)($request->validated('seconds') ?? 3600));

        // Object storage (S3): a presigned URL of the object itself, absolute
        if ($presignedUrl = $this->attachmentService->getTemporaryUrl($attachment, $expiresAt)) {
            return $presignedUrl;
        }

        // we do this because signature breaks if we use built in method for creating relative path
        // also cannot determine request scheme when ran inside docker
        $url = URL::temporarySignedRoute(
            'attachment.temporary-download',
            $expiresAt,
            $attachment
        );
        $parsedUrl = parse_url($url);

        // Combine the path and query string
        return $parsedUrl['path'] . (isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '');
    }

    /**
     * @param Attachment $attachment
     * @return StreamedResponse|void
     */
    protected function streamDownloadLogic(Attachment $attachment)
    {
        if ($attachment->status === AttachmentStatus::GOOD && $this->attachmentService->fileExists($attachment)) {
            $headers = [];
            if ($attachment->mime_type !== '') {
                $headers['Content-Type'] = $attachment->mime_type;
            }

            return response()->streamDownload(
                function () use ($attachment) {
                    $stream = $this->attachmentService->readStream($attachment);

                    while (!feof($stream)) {
                        echo fread($stream, 2048);
                    }

                    fclose($stream);
                },
                $attachment->original_name,
                $headers
            );
        }
        abort(404);
    }
}
