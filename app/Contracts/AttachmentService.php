<?php

namespace App\Contracts;

use App\Models\Attachment;
use Illuminate\Http\UploadedFile;

interface AttachmentService
{
    public function storeFile(UploadedFile $file, Attachment $attachment): string|false;

    public function attach(AttachmentAble $parent, array $idsToAttach);

    public function getPath(Attachment $attachment): string;

    public function getHashAlgo(): string;

    public function getHashSum(Attachment $attachment): false|string;
    
    public function verifyHash(Attachment $attachment);
}
