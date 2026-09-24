<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalyticsAccuracyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function analytics(array $query = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin)->get(route('analytics.index', $query))->assertOk();
    }

    private function resident(Household $h, array $attrs = []): void
    {
        $h->residents()->create(array_merge([
            'first_name' => 'Res', 'last_name' => 'Ident', 'gender' => 'Female',
            'date_of_birth' => now()->subYears(30)->toDateString(),
            'relationship_to_head' => 'Daughter', 'is_head' => false, 'status' => 'Active',
        ], $attrs));
    }

    public function test_complaint_cases_use_real_blotter_statuses(): void
    {
        $statuses = ['Open', 'Pending Official', 'Returned w/ Remarks', 'Under Mediation', 'Under Mediation', 'Settled', 'Referred'];
        foreach ($statuses as $i => $status) {
            DB::table('blotter_records')->insert([
                'case_number' => 'C-' . $i, 'incident_date' => now()->toDateString(), 'incident_type' => 'Other',
                'complainant_name' => 'A', 'respondent_name' => 'B', 'narrative' => 'x', 'status' => $status,
                'filed_by' => $this->admin->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $r = $this->analytics();
        $this->assertSame(7, $r->viewData('totalCases'));
        $this->assertSame(3, $r->viewData('pendingCases'));
        $this->assertSame(2, $r->viewData('ongoingCases'));
        $this->assertSame(1, $r->viewData('settledCases'));
        $this->assertSame(1, $r->viewData('referredCases'));
        $this->assertEquals(50.0, $r->viewData('settlementRate'));
    }

    public function test_approved_documents_count_as_in_progress(): void
    {
        foreach (['Pending', 'Pending Official', 'Approved', 'Issued', 'Rejected'] as $i => $status) {
            DB::table('document_requests')->insert([
                'tracking_number' => 'T-' . $i, 'document_type' => 'Barangay Clearance', 'purpose' => 'x',
                'status' => $status, 'requested_by' => $this->admin->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $r = $this->analytics();
        $this->assertSame(5, $r->viewData('totalDocuments'));
        $this->assertSame(3, $r->viewData('pendingDocuments'));
        $this->assertSame(1, $r->viewData('issuedDocuments'));
        $this->assertSame(1, $r->viewData('rejectedDocuments'));
    }

    public function test_inactive_residents_are_excluded_and_ages_come_from_date_of_birth(): void
    {
        $h = Household::create(['purok' => 'Purok 1']);
        $this->resident($h, ['is_head' => true, 'relationship_to_head' => 'Head']);
        $this->resident($h, ['status' => 'Inactive', 'is_pwd' => true]);
        $this->resident($h, ['first_name' => 'Teen', 'date_of_birth' => now()->subYears(13)->toDateString()]);
        // Stored age is stale (saved when she was 12); the date of birth says 13.
        DB::table('residents')->where('first_name', 'Teen')->update(['age' => 12]);

        $r = $this->analytics();
        $this->assertSame(2, $r->viewData('totalResidents'));
        $this->assertSame(0, $r->viewData('pwds'));

        $groups = collect($r->viewData('ageGroups'))->keyBy('label');
        $this->assertSame(0, $groups['Children (0–12)']['female']);
        $this->assertSame(1, $groups['Teenagers (13–17)']['female']);
        $this->assertSame(1, $groups['Adults (18–59)']['female']);
    }

    public function test_sector_filter_narrows_households_and_psa_section(): void
    {
        $withPwd = Household::create(['purok' => 'Purok 1']);
        $this->resident($withPwd, ['is_head' => true, 'relationship_to_head' => 'Head', 'is_pwd' => true, 'monthly_income' => 1000]);
        $other = Household::create(['purok' => 'Purok 2']);
        $this->resident($other, ['is_head' => true, 'relationship_to_head' => 'Head', 'monthly_income' => 50000]);
        foreach ([$withPwd, $other] as $h) {
            \App\Services\ClassificationService::refresh($h);
        }

        $all = $this->analytics();
        $this->assertSame(2, $all->viewData('totalHouseholds'));
        $this->assertSame(2, $all->viewData('psaAssessed'));

        $pwd = $this->analytics(['sector' => 'PWD']);
        $this->assertSame(1, $pwd->viewData('totalHouseholds'));
        $this->assertSame(1, $pwd->viewData('psaAssessed'));
        $this->assertSame(1, $pwd->viewData('psaBelowLine'));
    }

    public function test_month_filter_shows_population_as_of_that_month(): void
    {
        $lastMonth = now()->subMonthNoOverflow();
        $old = Household::create(['purok' => 'Purok 1']);
        $this->resident($old, ['is_head' => true, 'relationship_to_head' => 'Head']);
        $this->resident($old, ['first_name' => 'Lola', 'date_of_birth' => now()->subYears(60)->toDateString()]); // turns 60 today
        DB::table('households')->update(['created_at' => $lastMonth->copy()->subMonths(3)]);
        DB::table('residents')->update(['created_at' => $lastMonth->copy()->subMonths(3)]);

        $mid = Household::create(['purok' => 'Purok 2']);
        $this->resident($mid, ['is_head' => true, 'relationship_to_head' => 'Head']);
        DB::table('households')->where('id', $mid->id)->update(['created_at' => $lastMonth->copy()->startOfMonth()->addDays(2)]);
        DB::table('residents')->where('household_id', $mid->id)->update(['created_at' => $lastMonth->copy()->startOfMonth()->addDays(2)]);

        $new = Household::create(['purok' => 'Purok 3']);   // registered this month
        $this->resident($new, ['is_head' => true, 'relationship_to_head' => 'Head']);

        $r = $this->analytics(['month' => $lastMonth->format('Y-m')]);
        $this->assertSame(3, $r->viewData('totalResidents'));   // everyone registered by the end of last month
        $this->assertSame(2, $r->viewData('totalHouseholds'));
        $this->assertSame(1, $r->viewData('newResidents'));     // registered during last month
        $this->assertSame(1, $r->viewData('newHouseholds'));
        $this->assertSame(0, $r->viewData('seniorCitizens'));   // Lola wasn't 60 yet at the end of last month

        $now = $this->analytics();
        $this->assertSame(4, $now->viewData('totalResidents'));
        $this->assertSame(1, $now->viewData('seniorCitizens'));
        $this->assertNull($now->viewData('newResidents'));
    }

    public function test_age_table_and_gender_cards_add_up_with_missing_gender(): void
    {
        $h = Household::create(['purok' => 'Purok 1']);
        $this->resident($h, ['is_head' => true, 'relationship_to_head' => 'Head', 'gender' => 'Male']);
        $this->resident($h, ['gender' => 'Female']);
        $this->resident($h, ['first_name' => 'NoGender']);
        DB::table('residents')->where('first_name', 'NoGender')->update(['gender' => null]);

        $r = $this->analytics();
        $this->assertSame(3, $r->viewData('totalResidents'));
        $this->assertSame(1, $r->viewData('genderUnknown'));
        $this->assertSame(3, $r->viewData('maleResidents') + $r->viewData('femaleResidents') + $r->viewData('genderUnknown'));

        $sum = collect($r->viewData('ageGroups'))->sum(fn ($g) => $g['male'] + $g['female'] + $g['unspecified']);
        $this->assertSame(3, $sum);
        $r->assertSee('Not specified')->assertSee('1 gender not specified');
    }

    public function test_civil_status_and_employment_include_not_recorded(): void
    {
        $h = Household::create(['purok' => 'Purok 1']);
        $this->resident($h, ['is_head' => true, 'relationship_to_head' => 'Head', 'civil_status' => 'Married', 'employment_status' => 'Employed']);
        $this->resident($h);

        $r = $this->analytics();
        $this->assertSame(['Married' => 1, 'Not recorded' => 1], $r->viewData('civilStatus'));
        $this->assertSame(['Employed' => 1, 'Not recorded' => 1], $r->viewData('employmentStatus'));
    }

    public function test_free_documents_are_not_paid_requests(): void
    {
        foreach ([['Barangay Clearance', 50], ['Barangay Clearance', 50], ['Certificate of Indigency', 0]] as $i => [$type, $fee]) {
            DB::table('document_requests')->insert([
                'tracking_number' => 'P-' . $i, 'document_type' => $type, 'purpose' => 'x', 'status' => 'Issued',
                'fee' => $fee, 'or_number' => 'OR-' . $i,
                'requested_by' => $this->admin->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $r = $this->analytics();
        $this->assertEquals(100, $r->viewData('totalRevenue'));
        $this->assertSame(2, $r->viewData('paidDocumentsCount'));
        $this->assertEquals(50, $r->viewData('avgRevenuePerDocument'));
        $this->assertEquals(0, $r->viewData('revenueByType')['Certificate of Indigency']->count);
    }

    public function test_month_lists_have_no_duplicates_on_the_31st(): void
    {
        \Carbon\Carbon::setTestNow('2026-03-31 10:00:00');
        try {
            $r = $this->analytics();
            $labels = $r->viewData('monthLabels');
            $this->assertCount(12, array_unique($labels));
            $this->assertSame('Feb 2026', $labels[10]);
            $this->assertSame('Mar 2026', $labels[11]);
            $this->assertArrayHasKey('2026-02', $r->viewData('availableMonths'));
            $this->assertCount(24, $r->viewData('availableMonths'));
        } finally {
            \Carbon\Carbon::setTestNow();
        }
    }

    public function test_edit_member_requires_gender_and_name(): void
    {
        $h = Household::create(['purok' => 'Purok 1']);
        $h->residents()->create([
            'first_name' => 'Ana', 'last_name' => 'Cruz', 'gender' => 'Female', 'status' => 'Active',
            'date_of_birth' => now()->subYears(30)->toDateString(), 'relationship_to_head' => 'Head', 'is_head' => true,
        ]);
        $member = $h->residents()->first();

        $this->actingAs($this->admin)->put(route('residents.member.update', [$h->id, $member->id]), [
            'first_name' => 'Ana', 'last_name' => 'Cruz', 'gender' => '',
        ])->assertSessionHasErrors('gender');
        $this->actingAs($this->admin)->put(route('residents.member.update', [$h->id, $member->id]), [
            'first_name' => '', 'last_name' => 'Cruz', 'gender' => 'Female',
        ])->assertSessionHasErrors('first_name');

        $this->assertSame('Female', $member->fresh()->gender);
    }
}
