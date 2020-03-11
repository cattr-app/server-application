<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/** @codeCoverageIgnore  */
class Registration extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $url;

    /**
     * Create a new message instance.
     *
     * @param $key
     */
    public function __construct($key)
    {
        $this->url = URL::to("auth/register/$key");
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        return $this->view('emails.registration');
    }
}
