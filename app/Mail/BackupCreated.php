<?php

namespace App\Mail;

use App\Models\Backup;
use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Sent synchronously: the attached file is a temp file deleted right after the backup finishes. */
class BackupCreated extends Mailable
{
    public function __construct(public Backup $backup, public string $path) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Database backup — ' . $this->backup->created_at->format('M d, Y g:i A'));
    }

    public function content(): Content
    {
        $brgy = e(Setting::get('brgy_name', 'Caranas'));
        $when = e($this->backup->created_at->format('F d, Y g:i A'));
        $kind = $this->backup->trigger === 'scheduled' ? 'scheduled' : 'manual';

        return new Content(htmlString: <<<HTML
            <p>Attached is the {$kind} database backup of the Barangay {$brgy} Management System, taken {$when}.</p>
            <p>{$this->backup->tables} tables, {$this->backup->rows} records ({$this->backup->human_size}).</p>
            <p>Keep this file private: it contains residents' personal information.</p>
            HTML);
    }

    public function attachments(): array
    {
        return [Attachment::fromPath($this->path)->as($this->backup->filename)->withMime('application/gzip')];
    }
}
