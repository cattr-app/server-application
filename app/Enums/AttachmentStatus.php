<?php

namespace App\Enums;

enum AttachmentStatus: string
{
    /**
     * file hash NOT matched (on cron check)
     */
    case BAD = 'bad';

    /**
     * file hash matched (on cron check and initial upload)
     */
    case GOOD = 'good';

    /**
     * moving file to correct project folder and then calculating hash
     */
    case PROCESSING = 'processing';

    /**
     * file just uploaded, attachmentable not set yet, not calculating hash
     */
    case NOT_ATTACHED = 'not_attached';
}
