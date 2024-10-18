<?php

namespace App\Contracts;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface AttachmentAble
{
    /**
     * @return Attachment[]
     */
    public function attachments();
    public function attachmentsRelation(): MorphMany;

    public function getProjectId(): int;
}
