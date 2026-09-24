<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginOtp;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function create(Request $request): View|RedirectResponse
    {
        $userId = $request->session()->get('login.otp_user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (! $user) {
            $request->session()->forget(['login.otp_user_id', 'login.otp_remember']);
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge', ['user' => $user]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $userId = $request->session()->get('login.otp_user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        $otp  = $user ? LoginOtp::where('user_id', $user->id)->whereNull('consumed_at')->latest()->first() : null;

        if (! $user || ! $otp || $otp->isExpired()) {
            $request->session()->forget(['login.otp_user_id', 'login.otp_remember']);

            return redirect()->route('login')->withErrors([
                'email' => 'Your verification code has expired. Please log in again.',
            ]);
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            $otp->delete();
            $request->session()->forget(['login.otp_user_id', 'login.otp_remember']);

            return redirect()->route('login')->withErrors([
                'email' => 'Too many incorrect attempts. Please log in again.',
            ]);
        }

        if (! Hash::check($request->string('code'), $otp->code)) {
            $otp->increment('attempts');

            return back()->withErrors([
                'code' => 'Incorrect code. Please try again.',
            ]);
        }

        $otp->update(['consumed_at' => now()]);

        $remember = (bool) $request->session()->pull('login.otp_remember', false);
        $request->session()->forget('login.otp_user_id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function resend(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('login.otp_user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (! $user) {
            $request->session()->forget(['login.otp_user_id', 'login.otp_remember']);
            return redirect()->route('login');
        }

        $key = 'otp-resend:' . $user->id;

        if (RateLimiter::tooManyAttempts($key, 1)) {
            return back()->withErrors([
                'code' => 'Please wait a bit before requesting another code.',
            ]);
        }

        RateLimiter::hit($key, 30);

        try {
            LoginOtp::issueFor($user);
        } catch (\Throwable $e) {
            Log::error('Failed to resend login OTP', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return back()->withErrors([
                'code' => 'We could not send a new code. Please try again in a moment.',
            ]);
        }

        return back()->with('status', 'A new code has been sent to your email.');
    }
}
