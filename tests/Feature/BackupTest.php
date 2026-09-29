<?php

namespace Tests\Feature;

use App\Mail\BackupCreated;
use App\Models\Announcement;
use App\Models\Backup;
use App\Models\Household;
use App\Models\Setting;
use App\Models\User;
use App\Services\BackupService;
use App\Services\CloudinaryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    private ?string $uploadedSql = null;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /** Fake Cloudinary: capture the uploaded dump instead of sending it anywhere. */
    private function fakeCloudinary(): \Mockery\MockInterface
    {
        return $this->mock(CloudinaryService::class, function ($mock) {
            $mock->shouldReceive('uploadPrivateFile')->andReturnUsing(function (string $path) {
                $this->uploadedSql = gzdecode(file_get_contents($path));
                return ['url' => 'https://res.cloudinary.com/x/raw/authenticated/Backups/b.sql.gz', 'public_id' => 'Backups/b-' . uniqid() . '.sql.gz'];
            });
            $mock->shouldReceive('deleteFile')->byDefault();
            $mock->shouldReceive('privateFileUrl')->andReturn('https://res.cloudinary.com/signed-download')->byDefault();
        });
    }

    private function schedule(array $settings): void
    {
        foreach ($settings + ['backup_schedule_set_at' => '2026-01-01 00:00:00'] as $key => $value) {
            Setting::set($key, $value);
        }
    }

    private function scheduledBackupAt(string $when): Backup
    {
        $this->travelTo(Carbon::parse($when, 'Asia/Manila'));
        return Backup::create(['filename' => 'x.sql.gz', 'trigger' => 'scheduled', 'status' => 'success']);
    }

    // ── Back Up Now / download / delete ─────────────────────────

    public function test_back_up_now_stores_a_restorable_dump(): void
    {
        $this->fakeCloudinary();
        Household::create(['house_no' => '7', 'purok' => 'Purok 3']);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('settings.backups.store'))
            ->assertRedirect(route('settings.index'))
            ->assertSessionHas('success');

        $backup = Backup::sole();
        $this->assertSame('success', $backup->status);
        $this->assertSame('manual', $backup->trigger);
        $this->assertSame($admin->id, $backup->created_by);
        $this->assertGreaterThan(0, $backup->size);

        $this->assertStringContainsString('CREATE TABLE "households"', $this->uploadedSql);
        $this->assertStringContainsString("'Purok 3'", $this->uploadedSql);
        // Session rows (login tokens) are never included, only the table structure.
        $this->assertStringContainsString('CREATE TABLE "sessions"', $this->uploadedSql);
        $this->assertStringNotContainsString('INSERT INTO "sessions"', $this->uploadedSql);
    }

    public function test_failed_upload_is_recorded_and_shown(): void
    {
        $this->mock(CloudinaryService::class, fn ($m) => $m->shouldReceive('uploadPrivateFile')->andThrow(new \RuntimeException('Cloudinary is down')));

        $this->actingAs($this->admin())->post(route('settings.backups.store'))
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'Cloudinary is down'));

        $this->assertSame('failed', Backup::sole()->status);
    }

    public function test_download_serves_the_file_under_its_name_and_delete_removes_it(): void
    {
        $cloudinary = $this->fakeCloudinary();
        \Illuminate\Support\Facades\Http::fake(['res.cloudinary.com/signed-download' => \Illuminate\Support\Facades\Http::response('GZDATA')]);
        $admin = $this->admin();
        $backup = Backup::create(['filename' => 'a.sql.gz', 'public_id' => 'Backups/a.sql.gz', 'trigger' => 'manual', 'status' => 'success']);

        $response = $this->actingAs($admin)->get(route('settings.backups.download', $backup))->assertOk();
        $this->assertSame('GZDATA', $response->getContent());
        $this->assertStringContainsString('filename="a.sql.gz"', $response->headers->get('Content-Disposition'));

        $cloudinary->shouldReceive('deleteFile')->once()->with('Backups/a.sql.gz');
        $this->actingAs($admin)->delete(route('settings.backups.destroy', $backup))->assertRedirect(route('settings.index'));
        $this->assertModelMissing($backup);
    }

    public function test_only_admins_can_manage_backups(): void
    {
        $this->fakeCloudinary();
        $backup = Backup::create(['filename' => 'a.sql.gz', 'public_id' => 'p', 'trigger' => 'manual', 'status' => 'success']);

        foreach (['staff', 'resident'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->post(route('settings.backups.store'))->assertForbidden();
            $this->actingAs($user)->get(route('settings.backups.download', $backup))->assertForbidden();
            $this->actingAs($user)->delete(route('settings.backups.destroy', $backup))->assertForbidden();
        }
        $this->assertSame(1, Backup::count());
    }

    // ── Retention & email ───────────────────────────────────────

    public function test_keeps_only_the_newest_backups(): void
    {
        $cloudinary = $this->fakeCloudinary();
        Setting::set('backup_keep', 2);
        $oldest = Backup::create(['filename' => 'old.sql.gz', 'public_id' => 'Backups/old.sql.gz', 'trigger' => 'manual', 'status' => 'success']);
        Backup::create(['filename' => 'mid.sql.gz', 'public_id' => 'Backups/mid.sql.gz', 'trigger' => 'manual', 'status' => 'success']);

        $cloudinary->shouldReceive('deleteFile')->once()->with('Backups/old.sql.gz');
        app(BackupService::class)->run('manual');

        $this->assertModelMissing($oldest);
        $this->assertSame(2, Backup::count());
    }

    public function test_emails_a_copy_only_when_enabled(): void
    {
        $this->fakeCloudinary();
        Mail::fake();

        app(BackupService::class)->run('manual');
        Mail::assertNothingSent();

        Setting::set('backup_email_enabled', '1');
        Setting::set('backup_email', 'captain@example.com');
        $backup = app(BackupService::class)->run('scheduled');

        Mail::assertSent(BackupCreated::class, fn ($mail) => $mail->hasTo('captain@example.com')
            && $mail->hasAttachment(\Illuminate\Mail\Mailables\Attachment::fromPath($mail->path)->as($backup->filename)->withMime('application/gzip')));
        $this->assertSame('captain@example.com', $backup->fresh()->emailed_to);
    }

    // ── Schedule ────────────────────────────────────────────────

    public function test_off_is_never_due(): void
    {
        $this->schedule(['backup_frequency' => 'off']);
        $service = app(BackupService::class);

        $this->assertFalse($service->isScheduledBackupDue(Carbon::parse('2026-10-01 02:05', 'Asia/Manila')));
        $this->assertNull($service->nextRunAt());
    }

    public function test_daily_schedule_runs_once_per_day_and_catches_up(): void
    {
        $this->schedule(['backup_frequency' => 'daily', 'backup_time' => '02:00']);
        $service = app(BackupService::class);

        $this->assertTrue($service->isScheduledBackupDue(Carbon::parse('2026-10-01 02:05', 'Asia/Manila')));
        // Catch-up: the app was asleep at 2:00 and wakes at 9:00.
        $this->assertTrue($service->isScheduledBackupDue(Carbon::parse('2026-10-01 09:00', 'Asia/Manila')));

        $this->scheduledBackupAt('2026-10-01 09:00');
        $this->assertFalse($service->isScheduledBackupDue(Carbon::parse('2026-10-01 23:59', 'Asia/Manila')));
        $this->assertTrue($service->isScheduledBackupDue(Carbon::parse('2026-10-02 02:00', 'Asia/Manila')));
        $this->assertSame('2026-10-02 02:00', $service->nextRunAt(Carbon::parse('2026-10-01 12:00', 'Asia/Manila'))->format('Y-m-d H:i'));
    }

    public function test_saving_the_schedule_does_not_fire_for_a_time_already_past_today(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 15:00', 'Asia/Manila'));
        $this->actingAs($this->admin())->post(route('settings.update'), [
            '_backup' => '1', 'backup_frequency' => 'daily', 'backup_time' => '02:00',
            'backup_day_of_week' => 0, 'backup_day_of_month' => 1, 'backup_keep' => 7,
        ])->assertSessionHasNoErrors();

        $service = app(BackupService::class);
        $this->assertFalse($service->isScheduledBackupDue(now()));
        $this->assertSame('2026-10-02 02:00', $service->nextRunAt()->format('Y-m-d H:i'));
    }

    public function test_weekly_and_monthly_slots(): void
    {
        // Weekly on Monday 03:30. 2026-10-05 is a Monday.
        $this->schedule(['backup_frequency' => 'weekly', 'backup_time' => '03:30', 'backup_day_of_week' => 1]);
        $service = app(BackupService::class);
        $this->scheduledBackupAt('2026-09-28 04:00');
        $this->assertFalse($service->isScheduledBackupDue(Carbon::parse('2026-10-05 03:00', 'Asia/Manila')));
        $this->assertTrue($service->isScheduledBackupDue(Carbon::parse('2026-10-05 03:31', 'Asia/Manila')));

        // Monthly on the 15th at 01:00.
        $this->schedule(['backup_frequency' => 'monthly', 'backup_time' => '01:00', 'backup_day_of_month' => 15]);
        $this->scheduledBackupAt('2026-10-15 01:10');
        $this->assertFalse($service->isScheduledBackupDue(Carbon::parse('2026-11-14 23:00', 'Asia/Manila')));
        $this->assertTrue($service->isScheduledBackupDue(Carbon::parse('2026-11-15 01:00', 'Asia/Manila')));
        $this->assertSame('2026-11-15 01:00', $service->nextRunAt(Carbon::parse('2026-10-20 12:00', 'Asia/Manila'))->format('Y-m-d H:i'));
    }

    public function test_scheduled_command_runs_the_backup(): void
    {
        $this->fakeCloudinary();
        $this->artisan('backup:run --scheduled')->assertSuccessful();
        $this->assertSame('scheduled', Backup::sole()->trigger);
    }

    public function test_schedule_form_validation(): void
    {
        $this->actingAs($this->admin())->post(route('settings.update'), [
            '_backup' => '1', 'backup_frequency' => 'hourly', 'backup_time' => '25:00',
            'backup_day_of_week' => 9, 'backup_day_of_month' => 31, 'backup_keep' => 0,
            'backup_email_enabled' => '1', 'backup_email' => '',
        ])->assertSessionHasErrors(['backup_frequency', 'backup_time', 'backup_day_of_week', 'backup_day_of_month', 'backup_keep', 'backup_email']);

        $this->assertNull(Setting::get('backup_frequency'));
    }

    // ── Settings page & other audit fixes ───────────────────────

    public function test_settings_page_shows_backups_and_no_danger_zone(): void
    {
        Backup::create(['filename' => 'a.sql.gz', 'public_id' => 'p', 'size' => 2048, 'trigger' => 'scheduled', 'status' => 'success']);

        $this->actingAs($this->admin())->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Back Up Now')
            ->assertSee('2 KB')
            ->assertDontSee('Clear All Records')
            ->assertDontSee('Reset to Defaults')
            ->assertDontSee('April 14, 2025');
    }

    public function test_staff_deleting_an_announcement_returns_to_the_staff_list(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $announcement = Announcement::create([
            'title' => 'Clean-up drive', 'content' => 'Saturday 7 AM', 'category' => 'Events',
            'audience' => 'All Residents', 'status' => 'Draft', 'posted_by' => $staff->id,
        ]);

        $this->actingAs($staff)->delete(route('staff.announcements.destroy', $announcement->id))
            ->assertRedirect(route('staff.announcements.index'));
    }
}
