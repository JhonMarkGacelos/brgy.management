<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Resident;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use App\Notifications\BlotterStatusUpdated;
use App\Notifications\DocumentRequestSubmitted;
use App\Notifications\DocumentStatusUpdated;
use App\Services\CloudinaryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RemainingFixesTest extends TestCase
{
    use RefreshDatabase;

    private const PRIVATE_URL = 'https://res.cloudinary.com/demo/image/authenticated/s--abc--/v1/PWD%20IDs/x.png';

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function makeHead(array $attrs = []): Resident
    {
        $household = Household::create(['purok' => 'Purok 1']);

        return $household->residents()->create(array_merge([
            'first_name' => 'Juan', 'last_name' => 'Cruz', 'gender' => 'Male', 'status' => 'Active',
            'date_of_birth' => now()->subYears(40)->toDateString(), 'relationship_to_head' => 'Head', 'is_head' => true,
        ], $attrs));
    }

    private function fakeCloudinary(): void
    {
        $fake = new class extends CloudinaryService {
            public function __construct() {}
            public function privateUrl(string $publicId, string $storedUrl, int $ttl = self::PRIVATE_URL_TTL): string
            {
                return 'https://api.cloudinary.com/v1_1/demo/image/download?public_id=' . urlencode($publicId) . '&expires_at=1';
            }
        };
        $this->app->instance(CloudinaryService::class, $fake);
    }

    // ── Private ID photos ─────────────────────────────────────

    public function test_staff_and_admin_get_a_short_lived_link_others_are_blocked(): void
    {
        $this->fakeCloudinary();
        $resident = $this->makeHead(['is_pwd' => true, 'pwd_id_url' => self::PRIVATE_URL, 'pwd_id_public_id' => 'PWD IDs/x']);
        $url = route('id-photo.show', ['pwd', $resident->id]);

        foreach (['admin', 'staff'] as $role) {
            $this->actingAs($this->user($role))->get($url)
                ->assertRedirect('https://api.cloudinary.com/v1_1/demo/image/download?public_id=PWD+IDs%2Fx&expires_at=1')
                ->assertHeader('Cache-Control', 'no-store, private');
        }
        $this->actingAs($this->user('resident'))->get($url)->assertForbidden();
        auth()->logout();
        $this->get($url)->assertRedirect(route('login'));
    }

    public function test_unknown_kind_or_missing_photo_is_not_found(): void
    {
        $resident = $this->makeHead();
        $admin = $this->user('admin');

        $this->actingAs($admin)->get(route('id-photo.show', ['pwd', $resident->id]))->assertNotFound();
        $this->actingAs($admin)->get('/id-photo/password/' . $resident->id)->assertNotFound();
    }

    public function test_household_page_links_to_the_protected_route_not_the_raw_image(): void
    {
        $resident = $this->makeHead(['is_pwd' => true, 'pwd_id_url' => self::PRIVATE_URL, 'pwd_id_public_id' => 'PWD IDs/x']);

        $this->actingAs($this->user('admin'))->get(route('residents.show', $resident->household_id))
            ->assertOk()
            ->assertSee(route('id-photo.show', ['pwd', $resident->id]), false)
            ->assertDontSee(self::PRIVATE_URL, false);
    }

    public function test_private_url_passes_through_older_public_images(): void
    {
        $public = 'https://res.cloudinary.com/demo/image/upload/v1/old.png';

        $this->assertSame($public, (new CloudinaryService)->privateUrl('old', $public));
        $this->assertTrue(CloudinaryService::isPrivateUrl(self::PRIVATE_URL));
        $this->assertFalse(CloudinaryService::isPrivateUrl($public));
    }

    public function test_secure_ids_dry_run_lists_only_public_images(): void
    {
        $this->makeHead(['pwd_id_url' => 'https://res.cloudinary.com/demo/image/upload/v1/a.png', 'pwd_id_public_id' => 'a']);
        $this->makeHead(['pwd_id_url' => self::PRIVATE_URL, 'pwd_id_public_id' => 'b']);

        $this->artisan('cloudinary:secure-ids', ['--dry-run' => true])
            ->expectsOutput('Would make private: a')
            ->expectsOutput('Would make private: 1, already private: 1, failed: 0.')
            ->assertSuccessful();
    }

    // ── Other items ───────────────────────────────────────────

    public function test_notifications_are_queued(): void
    {
        foreach ([AnnouncementPublished::class, BlotterStatusUpdated::class, DocumentRequestSubmitted::class, DocumentStatusUpdated::class] as $class) {
            $this->assertTrue(is_subclass_of($class, ShouldQueue::class), $class);
        }
    }

    public function test_cloudinary_keys_come_from_config(): void
    {
        $this->assertArrayHasKey('cloud_name', config('services.cloudinary'));
        $this->assertStringNotContainsString("env('CLOUDINARY", file_get_contents(app_path('Services/CloudinaryService.php')));
    }

    public function test_failed_registration_leaves_no_household_behind(): void
    {
        // Break the residents insert after the household row is created.
        DB::statement('CREATE TRIGGER fail_residents BEFORE INSERT ON residents BEGIN SELECT RAISE(ABORT, "boom"); END');

        try {
            $this->actingAs($this->user('admin'))->post(route('residents.store'), [
                'purok' => 'Purok 1',
                'families' => [[
                    'head' => ['first_name' => 'Juan', 'last_name' => 'Cruz', 'gender' => 'Male', 'civil_status' => 'Single',
                        'date_of_birth' => now()->subYears(30)->toDateString()],
                    'members' => [],
                ]],
            ]);
        } catch (\Throwable) {
            // the exception is expected; what matters is the rollback
        }

        $this->assertDatabaseCount('households', 0);
    }

    public function test_age_is_always_current(): void
    {
        $resident = $this->makeHead(['date_of_birth' => now()->subYears(45)->toDateString()]);
        DB::table('residents')->where('id', $resident->id)->update(['age' => 44]);

        $this->assertSame(45, $resident->fresh()->age);
    }

    public function test_member_civil_status_is_saved(): void
    {
        $this->actingAs($this->user('admin'))->post(route('residents.store'), [
            'purok' => 'Purok 1',
            'families' => [[
                'head' => ['first_name' => 'Juan', 'last_name' => 'Cruz', 'gender' => 'Male', 'civil_status' => 'Married',
                    'date_of_birth' => now()->subYears(40)->toDateString()],
                'members' => [[
                    'first_name' => 'Maria', 'last_name' => 'Cruz', 'gender' => 'Female', 'relationship' => 'Wife',
                    'civil_status' => 'Married', 'date_of_birth' => now()->subYears(38)->toDateString(),
                ]],
            ]],
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame('Married', Resident::where('first_name', 'Maria')->value('civil_status'));
    }

    public function test_portal_request_does_not_write_requester_email_onto_resident(): void
    {
        $resident = $this->makeHead(['email' => null]);
        $portalUser = $this->user('resident');

        // Stub the upload so the request reaches the lookup and save steps without calling Cloudinary.
        $this->app->instance(CloudinaryService::class, new class extends CloudinaryService {
            public function __construct() {}
            public function uploadIdPhoto(UploadedFile $file, string $folder = "ID's", bool $private = true): array
            {
                return ['url' => 'https://res.cloudinary.com/demo/image/authenticated/s--x--/v1/a.png', 'public_id' => 'a'];
            }
        });

        $this->actingAs($portalUser)->post(route('resident.documents.store'), [
            'document_type' => 'Certificate of Indigency', 'purpose' => 'x',
            'first_name' => 'juan', 'last_name' => 'CRUZ',
            'id_photo' => UploadedFile::fake()->image('id.jpg'),
        ]);

        $this->assertDatabaseCount('document_requests', 1); // the request went through
        $this->assertNull($resident->fresh()->email);
    }

    // ── Needs review, expired announcement links ──────────────

    public function test_contradictory_household_is_flagged_and_filterable(): void
    {
        $rich = $this->makeHead(['first_name' => 'Rico', 'is_4ps' => true, 'monthly_income' => 800000]);
        $plain = $this->makeHead(['first_name' => 'Plain', 'monthly_income' => 800000]);
        $poorTagged = $this->makeHead(['first_name' => 'Poorita', 'is_4ps' => true, 'monthly_income' => 1000]);
        foreach ([$rich, $plain, $poorTagged] as $r) {
            \App\Services\ClassificationService::refresh($r->household);
        }

        $this->assertNotEmpty($rich->household->fresh()->reviewFlags());
        $this->assertEmpty($plain->household->fresh()->reviewFlags());
        $this->assertEmpty($poorTagged->household->fresh()->reviewFlags());
        $this->assertSame([$rich->household_id], Household::needsReview()->pluck('id')->all());

        $admin = $this->user('admin');
        $this->actingAs($admin)->get(route('residents.show', $rich->household_id))->assertOk()->assertSee('Needs review');
        $this->actingAs($admin)->get(route('residents.index', ['review' => 1]))
            ->assertOk()->assertSee('Rico')->assertDontSee('Plain')->assertDontSee('Poorita');
    }

    public function test_missing_gender_is_flagged(): void
    {
        $r = $this->makeHead();
        DB::table('residents')->where('id', $r->id)->update(['gender' => null]);

        $this->assertStringContainsString('no gender recorded', implode(' ', $r->household->fresh()->reviewFlags()));
        $this->assertSame(1, Household::needsReview()->count());
    }

    public function test_zero_fee_paid_document_is_flagged(): void
    {
        $admin = $this->user('admin');
        $base = ['purpose' => 'x', 'status' => 'Issued', 'requested_by' => $admin->id];
        $zero = \App\Models\DocumentRequest::create($base + ['tracking_number' => 'DOC-1', 'document_type' => 'Barangay Clearance', 'fee' => 0, 'or_number' => 'OR-1']);
        $free = \App\Models\DocumentRequest::create($base + ['tracking_number' => 'DOC-2', 'document_type' => 'Certificate of Indigency', 'fee' => 0, 'or_number' => 'OR-2']);
        $paid = \App\Models\DocumentRequest::create($base + ['tracking_number' => 'DOC-3', 'document_type' => 'Barangay Clearance', 'fee' => 50, 'or_number' => 'OR-3']);

        $this->assertSame([$zero->id], \App\Models\DocumentRequest::needsReview()->pluck('id')->all());
        $this->assertNotNull($zero->review_flag);
        $this->assertNull($free->review_flag);
        $this->assertNull($paid->review_flag);

        $this->actingAs($admin)->get(route('documents.index', ['review' => 1]))->assertOk()->assertSee('DOC-1')->assertDontSee('DOC-3');
        $this->actingAs($admin)->get(route('documents.show', $zero->id))->assertOk()->assertSee('Needs review');
    }

    public function test_expired_announcement_link_redirects_with_a_message(): void
    {
        $admin = $this->user('admin');
        $a = \App\Models\Announcement::create([
            'title' => 'Old', 'content' => 'x', 'category' => 'General', 'audience' => 'All Residents',
            'status' => 'Published', 'published_at' => now()->subMonth(), 'expires_at' => now()->subDay(), 'posted_by' => $admin->id,
        ]);

        $this->actingAs($this->user('resident'))->get(route('resident.announcements.show', $a->id))
            ->assertRedirect(route('resident.announcements.index'))
            ->assertSessionHas('info', 'That announcement is no longer available.');
    }

    public function test_edit_form_does_not_carry_stored_id_links(): void
    {
        $r = $this->makeHead(['is_pwd' => true, 'pwd_id_url' => self::PRIVATE_URL, 'pwd_id_public_id' => 'PWD IDs/x']);

        $this->actingAs($this->user('admin'))->get(route('residents.edit', $r->household_id))
            ->assertOk()
            ->assertDontSee('s--abc--', false)
            ->assertDontSee('PWD IDs/x', false);
    }
}
