<?php

use App\Models\Household;
use App\Models\Resident;
use App\Services\ClassificationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('residents:sync-seniors', function () {
    $cutoff = now()->subYears(Resident::SENIOR_AGE)->toDateString();

    $stale = Resident::whereNotNull('date_of_birth')
        ->where(fn ($q) => $q
            ->where(fn ($q) => $q->where('is_senior_citizen', false)->whereDate('date_of_birth', '<=', $cutoff))
            ->orWhere(fn ($q) => $q->where('is_senior_citizen', true)->whereDate('date_of_birth', '>', $cutoff)))
        ->get();

    // The Resident saving hook recomputes age and is_senior_citizen from date_of_birth.
    $stale->each->save();

    $householdIds = $stale->pluck('household_id')->filter()->unique();
    Household::whereIn('id', $householdIds)->get()->each(fn ($h) => ClassificationService::refresh($h));

    $this->info("Updated {$stale->count()} resident(s) across {$householdIds->count()} household(s).");
})->purpose('Sync Senior Citizen status (60+) from date of birth and refresh household classifications');

Artisan::command('households:reclassify', function () {
    $count = 0;
    Household::query()->chunkById(100, function ($households) use (&$count) {
        foreach ($households as $household) {
            ClassificationService::refresh($household);
            $count++;
        }
    });

    $this->info("Reclassified {$count} household(s).");
})->purpose('Recompute per capita income, welfare score and classification for every household');

Artisan::command('cloudinary:secure-ids {--dry-run : List what would change without touching Cloudinary}', function () {
    // Make ID photos and payment receipts uploaded before the privacy change private on Cloudinary.
    $sources = [
        [Resident::class, ['pwd', 'solo_parent', 'fourps', 'senior'], fn ($k) => ["{$k}_id_url", "{$k}_id_public_id"]],
        [\App\Models\DocumentRequest::class, ['id_photo', 'payment_receipt'], fn ($k) => ["{$k}_url", "{$k}_public_id"]],
    ];
    $service = app(\App\Services\CloudinaryService::class);
    $done = $skipped = $failed = 0;

    foreach ($sources as [$model, $kinds, $columns]) {
        foreach ($kinds as $kind) {
            [$urlCol, $idCol] = $columns($kind);
            $model::whereNotNull($idCol)->whereNotNull($urlCol)->orderBy('id')->each(
                function ($record) use ($service, $urlCol, $idCol, &$done, &$skipped, &$failed) {
                    if (\App\Services\CloudinaryService::isPrivateUrl($record->$urlCol)) {
                        $skipped++;
                        return;
                    }
                    if ($this->option('dry-run')) {
                        $this->line('Would make private: ' . $record->$idCol);
                        $done++;
                        return;
                    }
                    try {
                        $record->forceFill([$urlCol => $service->makePrivate($record->$idCol)])->saveQuietly();
                        $done++;
                    } catch (\Throwable $e) {
                        $failed++;
                        $this->error("Failed {$record->$idCol}: {$e->getMessage()}");
                    }
                }
            );
        }
    }

    $this->info(($this->option('dry-run') ? 'Would make private' : 'Made private') . ": {$done}, already private: {$skipped}, failed: {$failed}.");
})->purpose('Make previously uploaded ID photos and payment receipts private on Cloudinary');

Schedule::command('residents:sync-seniors')->daily();
