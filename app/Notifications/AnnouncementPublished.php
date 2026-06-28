<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnnouncementPublished extends Notification
{
    use Queueable;

    public function __construct(public Announcement $announcement) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $categoryColors = [
            'Emergency'     => '🚨',
            'Health'        => '🏥',
            'Events'        => '📅',
            'Peace & Order' => '🛡️',
        ];
        $icon = $categoryColors[$this->announcement->category] ?? '📢';

        return (new MailMessage)
            ->subject($icon . ' ' . $this->announcement->title . ' — Barangay Caranas')
            ->greeting('Hello, Residents of Barangay Caranas!')
            ->line('A new announcement has been posted:')
            ->line('**' . $this->announcement->title . '**')
            ->line($this->announcement->content)
            ->line('---')
            ->line('*Category: ' . $this->announcement->category . ' · Posted: ' . $this->announcement->created_at->format('F j, Y') . '*')
            ->salutation('Barangay Caranas — Motiong, Samar');
    }
}
