<?php

namespace App\Enums;

enum AttachmentStatus: int
{
    /**
     * file just uploaded, attachmentable not set yet, not calculating hash
     */
    case NOT_ATTACHED = 0;

    /**
     * moving file to correct project folder and then calculating hash
     */
    case PROCESSING = 1;

    /**
     * file hash matched (on cron check and initial upload)
     */
    case GOOD = 2;

    /**
     * file hash NOT matched (on cron check)
     */
    case BAD = 3;
}
