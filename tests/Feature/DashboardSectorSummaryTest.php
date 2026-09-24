<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSectorSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function resident(Household $household, array $attrs = []): Resident
    {
        return $household->residents()->create(array_merge([
            'first_name' => 'Res', 'last_name' => 'Ident', 'gender' => 'Female',
            'date_of_birth' => now()->subYears(30)->toDateString(),
            'relationship_to_head' => 'Daughter', 'is_head' => false, 'status' => 'Active',
        ], $attrs));
    }

    private function sectorSummary(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $summary = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->viewData('sectorSummary');

        return collect($summary)->mapWithKeys(fn ($s) => [$s['label'] => [$s['count'], $s['base']]])->all();
    }

    public function test_counts_reflect_real_current_data(): void
    {
        $h1 = Household::create(['purok' => 'Purok 1']);
        $h2 = Household::create(['purok' => 'Purok 2']);

        $this->resident($h1, ['is_head' => true, 'relationship_to_head' => 'Head', 'is_4ps' => true]);
        $this->resident($h1, ['is_pregnant' => true, 'pregnant_due_date' => now()->addMonths(3)->toDateString()]); // current
        $this->resident($h1, ['is_pregnant' => true, 'pregnant_due_date' => now()->subDay()->toDateString()]);     // already due
        $this->resident($h1, ['is_pwd' => true]);
        $this->resident($h2, ['is_head' => true, 'relationship_to_head' => 'Head',
            'date_of_birth' => now()->subYears(70)->toDateString()]);                                              // senior
        $this->resident($h2, ['is_pwd' => true, 'status' => 'Inactive']);                                          // not counted

        $s = $this->sectorSummary();

        $this->assertSame([1, 2], $s['4Ps Households']);   // 1 of 2 households
        $this->assertSame([1, 5], $s['Pregnant']);          // past-due excluded; 5 active residents
        $this->assertSame([1, 5], $s['PWD']);               // inactive excluded
        $this->assertSame([1, 5], $s['Senior Citizens']);
    }

    public function test_pregnant_filters_exclude_past_due(): void
    {
        $h = Household::create(['purok' => 'Purok 1']);
        $this->resident($h, ['is_head' => true, 'relationship_to_head' => 'Head', 'first_name' => 'Alvarado',
            'is_pregnant' => true, 'pregnant_due_date' => now()->subWeek()->toDateString()]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('residents.list', ['sector' => 'Pregnant']))->assertOk()->assertDontSee('Alvarado');
        $this->actingAs($admin)->get(route('residents.index', ['sector' => 'Pregnant']))->assertOk()->assertDontSee('Alvarado');
    }

    public function test_indigent_tag_is_shown_on_household_page(): void
    {
        $h = Household::create(['purok' => 'Purok 1']);
        $head = $this->resident($h, ['is_head' => true, 'relationship_to_head' => 'Head', 'is_indigent' => true]);

        $this->assertContains('Indigent', $head->sectors);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('residents.show', $h->id))
            ->assertOk()->assertSee('bg-amber-50 text-amber-700 ring-1 ring-amber-100">Indigent', false);
    }
}
