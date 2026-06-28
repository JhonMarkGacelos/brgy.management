<?php

namespace App\Notifications;

use App\Models\DocumentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentStatusUpdated extends Notification
{
    use Queueable;

    public function __construct(public DocumentRequest $document) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status  = $this->document->status;
        $subject = match($status) {
            'Issued'   => 'Your Document is Ready — ' . $this->document->document_type,
            'Rejected' => 'Document Request Update — ' . $this->document->document_type,
            default    => 'Document Status Update — ' . $this->document->document_type,
        };

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('Hello, ' . ($this->document->resident?->first_name ?? 'Resident') . '!');

        if ($status === 'Issued') {
            $message
                ->line('Good news! Your **' . $this->document->document_type . '** has been issued and is ready for pickup.')
                ->line('**Tracking No.:** ' . $this->document->tracking_number)
                ->line('**OR No.:** ' . ($this->document->or_number ?? '—'))
                ->line('**Date Issued:** ' . ($this->document->issued_at?->format('F j, Y') ?? now()->format('F j, Y')))
                ->line('Please visit the Barangay Hall to claim your document.')
                ->line('Thank you for using the Barangay Caranas online service.');
        } elseif ($status === 'Rejected') {
            $message
                ->line('We regret to inform you that your **' . $this->document->document_type . '** request has been rejected.')
                ->line('**Tracking No.:** ' . $this->document->tracking_number)
                ->line('**Reason:** ' . ($this->document->remarks ?? 'Please contact the barangay office for more information.'))
                ->line('You may visit the Barangay Hall for further assistance.');
        } else {
            $message
                ->line('Your **' . $this->document->document_type . '** request status has been updated to **' . $status . '**.')
                ->line('**Tracking No.:** ' . $this->document->tracking_number);
        }

        return $message->salutation('Barangay Caranas — Motiong, Samar');
    }
}
