<?php

namespace App\Notifications;

use App\Models\DocumentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentRequestSubmitted extends Notification
{
    use Queueable;

    public function __construct(public DocumentRequest $document) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toArray(object $notifiable): array
    {
        $resident = $this->document->resident;

        return [
            'icon'  => 'fa-file-circle-plus',
            'color' => 'blue',
            'title' => 'New Document Request',
            'body'  => $this->document->document_type . ' — ' . ($resident ? $resident->full_name : 'Walk-in'),
            'url'   => route('documents.show', $this->document->id),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resident = $this->document->resident;
        $name     = $resident ? $resident->full_name : 'Walk-in';

        return (new MailMessage)
            ->subject('New Document Request — ' . $this->document->document_type)
            ->greeting('Hello,')
            ->line('A new document request has been submitted.')
            ->line('**Resident:** ' . $name)
            ->line('**Document Type:** ' . $this->document->document_type)
            ->line('**Purpose:** ' . $this->document->purpose)
            ->line('**Tracking No.:** ' . $this->document->tracking_number)
            ->line('Please log in to the system to process this request.')
            ->salutation('Barangay Caranas Management System');
    }
}
