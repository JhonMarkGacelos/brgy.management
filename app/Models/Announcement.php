<?php

namespace App\Models;

use App\Notifications\AnnouncementPublished;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Announcement extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'category', 'audience'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('announcement');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match($eventName) {
            'created' => "Announcement \"{$this->title}\" created",
            'updated' => "Announcement \"{$this->title}\" updated",
            'deleted' => "Announcement \"{$this->title}\" deleted",
            default   => "Announcement \"{$this->title}\" {$eventName}",
        };
    }

    protected $fillable = [
        'title', 'content', 'category', 'audience',
        'status', 'published_at', 'expires_at', 'posted_by',
    ];

    protected $casts = [
        'published_at' => 'date',
        'expires_at'   => 'date',
        'emailed_at'   => 'datetime',
    ];

    /** Announcements dated for a later day are emailed from this hour (Manila time) on their publish date. */
    public const EMAIL_HOUR = 7;

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'Published');
    }

    public const AUDIENCES = ['All Residents', 'Senior Citizens', 'PWD', '4Ps', 'Voters'];

    /** Published, already live and not yet expired — what residents should see. */
    public function scopeLive($query)
    {
        $today = now()->toDateString();

        return $query->published()
            ->where(fn ($q) => $q->whereNull('published_at')->orWhereDate('published_at', '<=', $today))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', $today));
    }

    /** Audiences a resident belongs to ("All" is a legacy value treated like All Residents). */
    public static function audiencesFor(?Resident $resident): array
    {
        $audiences = ['All Residents', 'All'];
        if (!$resident) {
            return $audiences;
        }
        if ($resident->is_senior_citizen) $audiences[] = 'Senior Citizens';
        if ($resident->is_pwd)            $audiences[] = 'PWD';
        if ($resident->is_voter)          $audiences[] = 'Voters';
        // 4Ps is recorded on the household head and covers the whole household.
        if ($resident->is_4ps || $resident->household?->residents()->where('is_head', true)->where('is_4ps', true)->exists()) {
            $audiences[] = '4Ps';
        }

        return $audiences;
    }

    /** Active residents an announcement is meant for. */
    public function audienceResidents()
    {
        $query = Resident::where('status', 'Active');

        return match ($this->audience) {
            'Senior Citizens' => $query->where('is_senior_citizen', true),
            'PWD'             => $query->where('is_pwd', true),
            'Voters'          => $query->where('is_voter', true),
            '4Ps'             => $query->whereHas('household.residents', fn ($h) => $h->where('is_head', true)->where('is_4ps', true)),
            default           => $query,
        };
    }

    /**
     * Email the audience once the announcement is live (published, publish date reached, not expired).
     * emailed_at is claimed before sending so a save and the scheduler can never both send it.
     */
    public function emailAudienceIfDue(): bool
    {
        $claimed = static::live()->whereKey($this->id)->whereNull('emailed_at')->update(['emailed_at' => now()]);
        if (!$claimed) {
            return false;
        }

        $emails = $this->audienceResidents()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->pluck('email')
            ->unique()
            ->values();

        // Residents with a portal login get mail + a bell notification; others get mail only
        $portalUsers = User::where('role', 'resident')
            ->whereIn('email', $emails)
            ->get()
            ->keyBy('email');

        foreach ($emails as $email) {
            // A mail/queue failure for one resident must not stop the rest or fail the save.
            try {
                ($portalUsers->get($email) ?? Notification::route('mail', $email))->notify(new AnnouncementPublished($this));
            } catch (\Throwable $e) {
                Log::error('Failed to send notification: ' . AnnouncementPublished::class, [
                    'announcement_id' => $this->id, 'email' => $email, 'error' => $e->getMessage(),
                ]);
            }
        }

        return true;
    }
}
