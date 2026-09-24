<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LoginOtp;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request. Credentials are verified
     * here, but the session isn't started yet — that only happens once the
     * OTP challenge is passed, in TwoFactorChallengeController::store().
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = User::where('email', $request->string('email'))->firstOrFail();

        try {
            LoginOtp::issueFor($user);
        } catch (\Throwable $e) {
            Log::error('Failed to send login OTP', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return back()->withErrors([
                'email' => 'We could not send your verification code. Please try again in a moment.',
            ]);
        }

        $request->session()->put('login.otp_user_id', $user->id);
        $request->session()->put('login.otp_remember', $request->boolean('remember'));

        return redirect()->route('two-factor.challenge');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
