<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AttachmentService;
use App\Enums\AttachmentStatus;
use App\Http\Requests\Attachment\CreateAttachmentRequest;
use App\Http\Requests\Attachment\DownloadAttachmentRequest;
use App\Models\Attachment;
use Filter;
use Illuminate\Http\JsonResponse;
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
     * Upload an attachment
     *
     * Uploads the file from the `attachment` field (`multipart/form-data`) on behalf of the current user and
     * returns its record. The file stays unattached (`status` is `0`) until the returned `id` is passed in
     * `attachmentsRelation` when a task or a task comment is created or edited. The maximum file size is
     * set in the server configuration.
     *
     * Any authenticated user may upload files.
     *
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

    /**
     * Download an attachment by a temporary link
     *
     * Streams the attachment file as a download (`Content-Disposition: attachment`) with the MIME type of the
     * attachment. Needs no token: access is granted by the signature of the link issued by
     * `GET /attachment/{attachment}/temporary-url`. A missing, tampered or expired signature gives 403.
     *
     * Only verified attachments (`status` is `2`) with an existing file can be downloaded, otherwise 404.
     */
    public function tmpDownload(Attachment $attachment): ?StreamedResponse
    {
        return $this->streamDownloadLogic($attachment);
    }

    /**
     * Get a temporary download URL of an attachment
     *
     * Returns a link for downloading the attachment without a token. `seconds` sets the lifetime of the link
     * (3600 by default, 7 days at most). Only verified attachments (`status` is `2`) are available, otherwise 404.
     *
     * With the local disk the link is a path relative to the host (`/api/tmp-attachment-link/{id}?expires=...&signature=...`),
     * with S3 it is an absolute presigned URL.
     *
     * Allowed to the users who can view the project of the attachment. The body of the response is the bare URL
     * string, not the JSON envelope.
     */
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
