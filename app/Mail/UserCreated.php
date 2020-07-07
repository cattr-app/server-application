<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** @codeCoverageIgnore  */
class UserCreated extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public string $login;
    public string $password;
    public $url;

    /**
     * Create a new message instance.
     *
     * @param $login
     * @param $password
     */
    public function __construct($login, $password)
    {
        $this->login = $login;
        $this->password = $password;
        $this->url = config('app.frontend_url');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        return $this->markdown('emails.invite');
    }
}
