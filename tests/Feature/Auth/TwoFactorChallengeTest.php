<?php

namespace Tests\Feature\Auth;

use App\Models\LoginOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\Notifications\LoginOtpCode;
use Tests\TestCase;

class TwoFactorChallengeTest extends TestCase
{
    use RefreshDatabase;

    private function startChallenge(User $user): void
    {
        Notification::fake();
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
    }

    public function test_challenge_page_redirects_to_login_without_a_pending_otp(): void
    {
        $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));
    }

    public function test_challenge_page_renders_after_password_login(): void
    {
        // Regression: the page referenced a removed <x-language-switcher> component and 500'd in production.
        $user = User::factory()->create(['role' => 'admin', 'email' => 'captain@example.com']);
        $this->startChallenge($user);

        $this->get(route('two-factor.challenge'))
            ->assertOk()
            ->assertSee('ca*****@example.com', false)
            ->assertDontSee('auth_pages.', false);
    }

    public function test_correct_code_logs_the_user_in_and_redirects_to_their_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->startChallenge($user);

        $otp = LoginOtp::where('user_id', $user->id)->firstOrFail();
        $code = $this->capturedCode($user);

        $response = $this->post(route('two-factor.store'), ['code' => $code]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertNotNull($otp->fresh()->consumed_at);
        $this->assertNull(session('login.otp_user_id'));
    }

    public function test_wrong_code_does_not_log_in_and_increments_attempts(): void
    {
        $user = User::factory()->create();
        $this->startChallenge($user);

        $otp = LoginOtp::where('user_id', $user->id)->firstOrFail();

        $response = $this->post(route('two-factor.store'), ['code' => '000001']);

        $this->assertGuest();
        $response->assertSessionHasErrors('code');
        $this->assertSame(1, $otp->fresh()->attempts);
    }

    public function test_too_many_wrong_attempts_forces_back_to_login(): void
    {
        $user = User::factory()->create();
        $this->startChallenge($user);

        $otp = LoginOtp::where('user_id', $user->id)->firstOrFail();
        $otp->update(['attempts' => 5]);

        $response = $this->post(route('two-factor.store'), ['code' => '000001']);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $this->assertNull(LoginOtp::find($otp->id));
    }

    public function test_expired_code_forces_back_to_login(): void
    {
        $user = User::factory()->create();
        $this->startChallenge($user);

        $otp = LoginOtp::where('user_id', $user->id)->firstOrFail();
        $realCode = $this->capturedCode($user);
        $otp->update(['expires_at' => now()->subMinute()]);

        $response = $this->post(route('two-factor.store'), ['code' => $realCode]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_resend_issues_a_new_code_and_is_rate_limited(): void
    {
        $user = User::factory()->create();
        $this->startChallenge($user);

        $firstOtpId = LoginOtp::where('user_id', $user->id)->firstOrFail()->id;

        $this->post(route('two-factor.resend'))->assertSessionHasNoErrors();
        $secondOtp = LoginOtp::where('user_id', $user->id)->firstOrFail();
        $this->assertNotSame($firstOtpId, $secondOtp->id, 'resending should invalidate the old code and issue a new one');

        // Immediately resending again should be throttled.
        $response = $this->post(route('two-factor.resend'));
        $response->assertSessionHasErrors('code');
    }

    public function test_wrong_password_never_reaches_the_challenge(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'not-the-password']);

        $this->assertGuest();
        $this->assertNull(session('login.otp_user_id'));
        $response->assertSessionHasErrors('email');
    }

    /** Pull the plaintext code the fake notification captured, since it isn't stored anywhere else. */
    private function capturedCode(User $user): string
    {
        $code = null;
        Notification::assertSentTo($user, LoginOtpCode::class, function ($notification) use (&$code) {
            $code = $notification->code;
            return true;
        });

        $this->assertNotNull($code, 'LoginOtpCode notification was not captured');

        return $code;
    }
}
