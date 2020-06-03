<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\SerializesModels;
use Lang;

/** @codeCoverageIgnore  */
class ResetPassword extends ResetPasswordNotification implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * User email.
     *
     * @var string
     */
    public string $email;

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
     * @codeCoverageIgnore
     */
    public function toMail($notifiable): MailMessage
    {
        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $this->token);
        }

        $resetUrl = config('app.frontend_url') . "/auth/password/reset?email={$this->email}&token={$this->token}";

        $locale = User::where('email', '=', $this->email)->first()->getAttribute('user_language');
        Lang::setLocale($locale);

        return (new MailMessage())
            ->subject(Lang::get('emails.reset_password.subject'))
            ->line(Lang::get('emails.reset_password.intro'))
            ->action(Lang::get('emails.reset_password.action'), $resetUrl)
            ->line(Lang::get('emails.reset_password.outro'));
    }
}
