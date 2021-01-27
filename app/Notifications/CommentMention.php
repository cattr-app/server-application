<?php

namespace App\Notifications;

use App\Models\TaskComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommentMention extends Notification
{
    use Queueable;

    protected TaskComment $comment;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(TaskComment $comment)
    {
        $this->comment = $comment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = url(config('app.frontend_url') . "/projects/" . $this->comment->task->project_id . "/tasks/kanban?task=" . $this->comment->task_id);

        return (new MailMessage)
            ->line(__('You were mentioned in a comment.', [], $this->locale))
            ->action(__('Show Comment', [], $this->locale), $url);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
