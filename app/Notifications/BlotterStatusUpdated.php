<?php

namespace App\Notifications;

use App\Models\BlotterRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BlotterStatusUpdated extends Notification
{
    use Queueable;

    public function __construct(public BlotterRecord $record, public string $role = 'complainant') {}

    public function via(object $notifiable): array
    {
        return $notifiable instanceof \App\Models\User ? ['mail', 'database'] : ['mail'];
    }

    public function toArray(object $notifiable): array
    {
        $url = match($notifiable->role ?? null) {
            'admin'    => route('blotter.show', $this->record->id),
            'staff'    => route('staff.blotter.show', $this->record->id),
            default    => route('resident.dashboard'),
        };

        return [
            'icon'  => 'fa-shield-halved',
            'color' => 'amber',
            'title' => 'Blotter Case Update — ' . $this->record->status,
            'body'  => 'Case ' . $this->record->case_number . ' (' . ucfirst($this->role) . ')',
            'url'   => $url,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->record->status;
        $name   = $this->role === 'respondent'
            ? $this->record->respondent_name
            : $this->record->complainant_name;

        $subject = match($status) {
            'Settled'             => 'Blotter Case Settled — ' . $this->record->case_number,
            'Referred'            => 'Blotter Case Referred — ' . $this->record->case_number,
            'Under Mediation'     => 'Blotter Case Under Mediation — ' . $this->record->case_number,
            'Returned w/ Remarks' => 'Update on Blotter Case — ' . $this->record->case_number,
            default               => 'Blotter Case Status Update — ' . $this->record->case_number,
        };

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('Hello, ' . $name . '!')
            ->line('There is an update on blotter case **' . $this->record->case_number . '** filed at Barangay Caranas.')
            ->line('**Incident Type:** ' . $this->record->incident_type)
            ->line('**Your Role:** ' . ucfirst($this->role))
            ->line('**New Status:** ' . $status);

        if ($status === 'Settled') {
            $message->line('This case has been successfully settled. Thank you for cooperating with the barangay.');
        } elseif ($status === 'Referred') {
            $message->line('This case has been referred to a higher authority for further action.')
                    ->line('**Remarks:** ' . ($this->record->remarks ?? 'Please visit the Barangay Hall for more details.'));
        } elseif ($status === 'Under Mediation') {
            $message->line('This case is currently under mediation. The barangay will contact you for the schedule.')
                    ->line('Please keep your contact number available.');
        } elseif ($this->record->remarks) {
            $message->line('**Remarks:** ' . $this->record->remarks);
        }

        return $message
            ->line('For questions, please contact the Barangay Hall directly.')
            ->salutation('Barangay Caranas — Motiong, Samar');
    }
}
