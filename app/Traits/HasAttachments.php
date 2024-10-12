<?php

namespace App\Traits;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasAttachments
{

    /**
     * @return Collection
     */
    public function attachments()
    {
        return $this->attachmentsRelation;
    }

    /**
     * It's important to name the relationship the same as the method because otherwise
     * eager loading of the polymorphic relationship will fail on queued jobs.
     *
     * @see https://github.com/laravelio/laravel.io/issues/350
     */
    public function attachmentsRelation(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachmentsRelation', 'attachmentable_type', 'attachmentable_id');
    }
}
