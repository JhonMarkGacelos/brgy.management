<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginOtpCode extends Notification
{
    use Queueable;

    public function __construct(public string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Login Verification Code — Barangay Caranas')
            ->greeting('Hello, ' . ($notifiable->name ?? 'there') . '!')
            ->line('Use the code below to finish signing in to your Barangay Caranas account.')
            ->line('# ' . $this->code)
            ->line('This code expires in 10 minutes.')
            ->line('If you did not attempt to log in, you can safely ignore this email or contact the Barangay Hall.')
            ->salutation('Barangay Caranas — Motiong, Samar');
    }
}
