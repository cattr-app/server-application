<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Lang;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPassword extends ResetPasswordNotification
{
    /**
     * User email.
     *
     * @var string
     */
    public $email;

    public function __construct($email, $token)
    {
        parent::__construct($token);
        $this->email = $email;
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $this->token);
        }

        $resetUrl = config('app.password_reset_url')
            . "?email=$this->email&token=$this->token";

        $locale = User::where('email', '=', $this->email)->first()->getAttribute('user_language');
        Lang::setLocale($locale);

        return (new MailMessage)
            ->subject(Lang::get('emails.reset_password.subject'))
            ->line(Lang::get('emails.reset_password.intro'))
            ->action(Lang::get('emails.reset_password.action'), $resetUrl)
            ->line(Lang::get('emails.reset_password.outro'));
    }
}
