<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** @codeCoverageIgnore  */
class UserInvited extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public string $url;

    /**
     * Create a new message instance.
     *
     * @param $key
     */
    public function __construct($key)
    {
        $this->url = config('app.frontend_url') . "/auth/register?token={$key}";
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        return $this->markdown('emails.registration');
    }
}
