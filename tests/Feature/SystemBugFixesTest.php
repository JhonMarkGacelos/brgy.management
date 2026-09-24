<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\BlotterRecord;
use App\Models\DocumentRequest;
use App\Models\Household;
use App\Models\Resident;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SystemBugFixesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function blotterPayload(array $overrides = []): array
    {
        return array_merge([
            'incident_date' => now()->toDateString(), 'incident_type' => 'Theft',
            'complainant_name' => 'Ana', 'respondent_name' => 'Ben',
            'narrative' => 'x', 'action_taken' => 'y', 'status' => 'Open',
        ], $overrides);
    }

    private function document(array $attrs = []): DocumentRequest
    {
        return DocumentRequest::create(array_merge([
            'tracking_number' => DocumentRequest::generateTrackingNumber(),
            'document_type' => 'Barangay Clearance', 'purpose' => 'Employment',
            'fee' => 50, 'status' => 'Pending', 'requested_by' => $this->admin->id,
        ], $attrs));
    }

    private function resident(array $attrs = []): Resident
    {
        $household = $attrs['household'] ?? Household::create(['purok' => 'Purok 1']);
        unset($attrs['household']);

        return $household->residents()->create(array_merge([
            'first_name' => 'Juan', 'last_name' => 'Cruz', 'gender' => 'Male', 'status' => 'Active',
            'date_of_birth' => now()->subYears(40)->toDateString(),
            'relationship_to_head' => 'Head', 'is_head' => true,
        ], $attrs));
    }

    // ── Blotter ───────────────────────────────────────────────

    public function test_blotter_rejects_unknown_status_and_blank_fields_on_edit(): void
    {
        $this->actingAs($this->admin)->post(route('blotter.store'), $this->blotterPayload(['status' => 'Whatever']))
            ->assertSessionHasErrors('status');

        $this->actingAs($this->admin)->post(route('blotter.store'), $this->blotterPayload())->assertSessionDoesntHaveErrors();
        $record = BlotterRecord::firstOrFail();

        $this->actingAs($this->admin)->put(route('blotter.update', $record->id), $this->blotterPayload(['complainant_name' => '']))
            ->assertSessionHasErrors('complainant_name');
        $this->assertSame('Ana', $record->fresh()->complainant_name);
    }

    public function test_blotter_closing_date_is_set_on_filing_and_kept_on_later_edits(): void
    {
        $this->actingAs($this->admin)->post(route('blotter.store'), $this->blotterPayload(['status' => 'Settled']))
            ->assertSessionDoesntHaveErrors();
        $record = BlotterRecord::firstOrFail();
        $this->assertNotNull($record->resolved_at);

        DB::table('blotter_records')->update(['resolved_at' => now()->subMonths(2)]);
        $this->actingAs($this->admin)->put(route('blotter.update', $record->id), $this->blotterPayload(['status' => 'Settled', 'narrative' => 'typo fixed']))
            ->assertSessionDoesntHaveErrors();

        $this->assertTrue($record->fresh()->resolved_at->lt(now()->subMonth()));
    }

    // ── Documents ─────────────────────────────────────────────

    public function test_rejected_document_cannot_be_printed(): void
    {
        $doc = $this->document(['status' => 'Rejected']);

        $this->actingAs($this->admin)->get(route('documents.print', $doc->id))->assertRedirect(route('documents.show', $doc->id));
        $this->assertSame('Rejected', $doc->fresh()->status);
    }

    public function test_printing_a_paid_document_records_or_number_and_payment(): void
    {
        $doc = $this->document(['fee' => 50]);

        $this->actingAs($this->admin)->get(route('documents.print', $doc->id))->assertOk();

        $doc->refresh();
        $this->assertSame('Issued', $doc->status);
        $this->assertNotNull($doc->or_number);
        $this->assertNotNull($doc->paid_at);
    }

    public function test_document_update_validates_status_and_keeps_issue_date(): void
    {
        $doc = $this->document(['status' => 'Issued', 'issued_at' => now()->subWeek(), 'or_number' => 'OR-2026-00001']);

        $this->actingAs($this->admin)->put(route('documents.update', $doc->id), ['status' => 'Done'])
            ->assertSessionHasErrors('status');

        $this->actingAs($this->admin)->put(route('documents.update', $doc->id), ['status' => 'Issued', 'remarks' => 'note'])
            ->assertSessionDoesntHaveErrors();
        $this->assertTrue($doc->fresh()->issued_at->lt(now()->subDays(6)));
    }

    public function test_document_store_rejects_unknown_type(): void
    {
        $this->actingAs($this->admin)->post(route('documents.store'), ['document_type' => 'Fake Permit', 'purpose' => 'x'])
            ->assertSessionHasErrors('document_type');
    }

    // ── Resident portal & public verify ───────────────────────

    public function test_portal_name_lookup_treats_wildcards_literally(): void
    {
        $this->resident();
        $portalUser = User::factory()->create(['role' => 'resident']);

        $this->actingAs($portalUser)->post(route('resident.documents.store'), [
            'document_type' => 'Certificate of Indigency', 'purpose' => 'x',
            'first_name' => '%', 'last_name' => '%',
            'id_photo' => UploadedFile::fake()->image('id.jpg'),
        ])->assertSessionHas('resident_not_found');

        $this->assertSame(0, DocumentRequest::count());
    }

    public function test_residents_can_only_track_their_own_requests(): void
    {
        $owner = User::factory()->create(['role' => 'resident']);
        $other = User::factory()->create(['role' => 'resident']);
        $doc = $this->document(['requested_by' => $owner->id]);

        $this->actingAs($other)->get(route('resident.documents.track', ['tracking_number' => $doc->tracking_number]))
            ->assertOk()->assertViewHas('document', null);
        $this->actingAs($owner)->get(route('resident.documents.track', ['tracking_number' => $doc->tracking_number]))
            ->assertOk()->assertViewHas('document', fn ($d) => $d?->id === $doc->id);
    }

    public function test_public_verify_only_confirms_issued_documents(): void
    {
        $pending = $this->document();
        $issued  = $this->document(['status' => 'Issued', 'issued_at' => now(), 'or_number' => 'OR-2026-00009']);

        $this->post(route('document.verify.check'), ['code' => $pending->tracking_number])->assertViewHas('document', null);
        $this->post(route('document.verify.check'), ['code' => 'OR-2026-00009'])->assertViewHas('document', fn ($d) => $d?->id === $issued->id);
    }

    // ── Announcements ─────────────────────────────────────────

    public function test_targeted_and_expired_announcements_in_portal(): void
    {
        $seniorUser = User::factory()->create(['role' => 'resident', 'email' => 'lola@example.com']);
        $youngUser  = User::factory()->create(['role' => 'resident', 'email' => 'juan@example.com']);
        $this->resident(['email' => 'lola@example.com', 'first_name' => 'Lola', 'date_of_birth' => now()->subYears(70)->toDateString()]);
        $this->resident(['email' => 'juan@example.com']);

        $base = ['content' => 'x', 'category' => 'General', 'status' => 'Published', 'published_at' => now()->subDay(), 'posted_by' => $this->admin->id];
        Announcement::create($base + ['title' => 'Seniors Payout', 'audience' => 'Senior Citizens']);
        Announcement::create($base + ['title' => 'Old Notice', 'audience' => 'All Residents', 'expires_at' => now()->subDay()]);
        Announcement::create($base + ['title' => 'Town Fiesta', 'audience' => 'All Residents']);

        $this->actingAs($seniorUser)->get(route('resident.announcements.index'))
            ->assertSee('Seniors Payout')->assertSee('Town Fiesta')->assertDontSee('Old Notice');
        $this->actingAs($youngUser)->get(route('resident.announcements.index'))
            ->assertDontSee('Seniors Payout')->assertSee('Town Fiesta');
    }

    public function test_announcement_emails_go_only_to_its_audience(): void
    {
        Notification::fake();
        $this->resident(['email' => 'lola@example.com', 'first_name' => 'Lola', 'date_of_birth' => now()->subYears(70)->toDateString()]);
        $this->resident(['email' => 'juan@example.com']);

        $this->actingAs($this->admin)->post(route('announcements.store'), [
            'title' => 'Seniors Payout', 'content' => 'x', 'category' => 'General', 'audience' => 'Senior Citizens', 'status' => 'Published',
        ])->assertSessionDoesntHaveErrors();

        Notification::assertSentOnDemand(AnnouncementPublished::class, fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === 'lola@example.com');
        Notification::assertSentOnDemandTimes(AnnouncementPublished::class, 1);
    }

    public function test_announcement_update_requires_title(): void
    {
        $a = Announcement::create(['title' => 'A', 'content' => 'x', 'category' => 'General', 'audience' => 'All Residents', 'status' => 'Draft', 'posted_by' => $this->admin->id]);

        $this->actingAs($this->admin)->put(route('announcements.update', $a->id), ['title' => '', 'content' => 'x', 'category' => 'General'])
            ->assertSessionHasErrors('title');
    }

    // ── Dashboards & users ────────────────────────────────────

    public function test_staff_dashboard_counts_active_cases(): void
    {
        foreach (['Open', 'Under Mediation', 'Settled'] as $i => $status) {
            DB::table('blotter_records')->insert([
                'case_number' => 'C-' . $i, 'incident_date' => now()->toDateString(), 'incident_type' => 'Theft',
                'complainant_name' => 'A', 'respondent_name' => 'B', 'narrative' => 'x', 'status' => $status,
                'filed_by' => $this->admin->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)->get(route('staff.dashboard'))->assertOk()->assertViewHas('activeBlotterCases', 2);
    }

    public function test_admin_cannot_remove_own_admin_role(): void
    {
        $this->actingAs($this->admin)->put(route('users.update', $this->admin->id), [
            'name' => $this->admin->name, 'email' => $this->admin->email, 'role' => 'staff',
        ])->assertSessionHas('error');

        $this->assertSame('admin', $this->admin->fresh()->role);
    }

    // ── Households ────────────────────────────────────────────

    public function test_editing_a_household_keeps_members_and_their_links(): void
    {
        $head   = $this->resident(['civil_status' => 'Married']);
        $member = $this->resident(['household' => $head->household, 'first_name' => 'Ana', 'is_head' => false,
            'relationship_to_head' => 'Daughter', 'gender' => 'Female', 'employment_status' => 'Employed', 'status' => 'Inactive']);
        $doc = $this->document(['resident_id' => $member->id]);

        $this->actingAs($this->admin)->put(route('residents.update', $head->household_id), [
            'purok' => 'Purok 1',
            'families' => [[
                'head' => ['first_name' => 'Juan', 'last_name' => 'Cruz', 'gender' => 'Male', 'civil_status' => 'Married',
                    'date_of_birth' => now()->subYears(40)->toDateString()],
                'members' => [[
                    'id' => $member->id, 'first_name' => 'Ana', 'last_name' => 'Cruz', 'gender' => 'Female',
                    'relationship' => 'Daughter', 'date_of_birth' => now()->subYears(40)->toDateString(),
                    'employment_status' => 'Employed',
                ]],
            ]],
        ])->assertSessionDoesntHaveErrors();

        $this->assertNotNull($member->fresh(), 'member row kept');
        $this->assertSame('Inactive', $member->fresh()->status);
        $this->assertSame('Employed', $member->fresh()->employment_status);
        $this->assertSame($member->id, $doc->fresh()->resident_id);
    }
}
