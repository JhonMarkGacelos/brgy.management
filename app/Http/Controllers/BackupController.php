<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use App\Services\BackupService;
use App\Services\CloudinaryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

/** Admin-only: take, download and delete database backups from Settings. */
class BackupController extends Controller
{
    public function store(BackupService $backups)
    {
        $backup = $backups->run('manual', Auth::user());

        if (!$backup->isSuccessful()) {
            return redirect()->route('settings.index')->with('error', 'Backup failed: ' . $backup->error);
        }

        activity('backup')->causedBy(Auth::user())->log("Database backup created ({$backup->filename})");

        return redirect()->route('settings.index')
            ->with('success', "Backup saved ({$backup->rows} records, {$backup->human_size})." . ($backup->emailed_to ? " A copy was emailed to {$backup->emailed_to}." : ''));
    }

    public function download(Backup $backup, CloudinaryService $cloudinary)
    {
        abort_unless($backup->isSuccessful() && $backup->public_id, 404);

        // Fetched server-side (backups are small) so the file keeps its name and the signed link never reaches the browser.
        $file = Http::timeout(60)->get($cloudinary->privateFileUrl($backup->public_id));
        if ($file->failed()) {
            return redirect()->route('settings.index')->with('error', 'Could not download the backup from Cloudinary. Please try again.');
        }

        activity('backup')->causedBy(Auth::user())->log("Database backup downloaded ({$backup->filename})");

        return response($file->body(), 200, [
            'Content-Type'        => 'application/gzip',
            'Content-Disposition' => 'attachment; filename="' . $backup->filename . '"',
            'Cache-Control'       => 'no-store, private',
        ]);
    }

    public function destroy(Backup $backup, BackupService $backups)
    {
        $backups->delete($backup);

        activity('backup')->causedBy(Auth::user())->log("Database backup deleted ({$backup->filename})");

        return redirect()->route('settings.index')->with('success', 'Backup deleted.');
    }
}
