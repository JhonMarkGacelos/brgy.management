<?php

namespace App\Models;

use App\Notifications\LoginOtpCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class LoginOtp extends Model
{
    protected $fillable = ['user_id', 'code', 'attempts', 'expires_at', 'consumed_at'];

    protected $casts = [
        'expires_at'  => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Invalidate any previous unconsumed code for this user, generate a new
     * one, and email it.
     */
    public static function issueFor(User $user): self
    {
        static::where('user_id', $user->id)->whereNull('consumed_at')->delete();

        $plainCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp = static::create([
            'user_id'    => $user->id,
            'code'       => Hash::make($plainCode),
            'expires_at' => now()->addMinutes(10),
        ]);

        $user->notify(new LoginOtpCode($plainCode));

        return $otp;
    }
}
