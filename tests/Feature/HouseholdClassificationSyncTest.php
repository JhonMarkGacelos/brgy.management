<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\User;
use App\Services\ClassificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HouseholdClassificationSyncTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /** Head earning $headIncome plus a zero-income member; stored Poverty Status refreshed. */
    private function household(float $headIncome): Household
    {
        $household = Household::create(['purok' => 'Purok 1']);
        $household->residents()->create([
            'first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'gender' => 'Male',
            'date_of_birth' => now()->subYears(40)->toDateString(),
            'relationship_to_head' => 'Head', 'is_head' => true, 'monthly_income' => $headIncome,
        ]);
        $household->residents()->create([
            'first_name' => 'Ana', 'last_name' => 'Dela Cruz', 'gender' => 'Female',
            'date_of_birth' => now()->subYears(10)->toDateString(),
            'relationship_to_head' => 'Daughter', 'is_head' => false, 'monthly_income' => 0,
        ]);
        ClassificationService::refresh($household);

        return $household->fresh();
    }

    public function test_edit_member_modal_updates_stored_poverty_status(): void
    {
        $household = $this->household(20000);
        $this->assertEquals(10000, (float) $household->per_capita_income);
        $head = $household->residents()->where('is_head', true)->first();

        $this->actingAs($this->admin())->put(route('residents.member.update', [$household->id, $head->id]), [
            'first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'gender' => 'Male',
            'date_of_birth' => now()->subYears(40)->toDateString(),
            'monthly_income' => 1000,
        ])->assertSessionDoesntHaveErrors();

        $household->refresh();
        $this->assertEquals(500, (float) $household->per_capita_income);
        $this->assertSame('Food Poor', $household->psa_status);
    }

    public function test_removing_a_member_updates_stored_per_capita(): void
    {
        $household = $this->household(6000);
        $this->assertEquals(3000, (float) $household->per_capita_income);
        $ana = $household->residents()->where('is_head', false)->first();

        $this->actingAs($this->admin())
            ->delete(route('residents.member.destroy', [$household->id, $ana->id]))
            ->assertSessionDoesntHaveErrors();

        $this->assertEquals(6000, (float) $household->fresh()->per_capita_income);
    }

    public function test_reclassify_command_fixes_stale_values(): void
    {
        $household = $this->household(20000);
        DB::table('households')->where('id', $household->id)
            ->update(['per_capita_income' => 1, 'psa_status' => 'Food Poor']);

        $this->artisan('households:reclassify')
            ->expectsOutput('Reclassified 1 household(s).')
            ->assertSuccessful();

        $this->assertEquals(10000, (float) $household->fresh()->per_capita_income);
        $this->assertSame('Middle', $household->fresh()->psa_status);
    }

    public function test_welfare_score_is_no_longer_shown_anywhere(): void
    {
        $admin     = $this->admin();
        $household = $this->household(6000);

        $this->actingAs($admin)->get(route('settings.index'))
            ->assertOk()->assertDontSee('Welfare Score')->assertDontSee('name="per_capita_poor"', false);
        $this->actingAs($admin)->get(route('residents.show', $household->id))
            ->assertOk()->assertDontSee('Welfare Score')->assertSee('Poverty Status');
        $this->actingAs($admin)->get(route('residents.index'))
            ->assertOk()->assertDontSee('Welfare Score')->assertSee('Middle &amp; above', false);
        $this->actingAs($admin)->get(route('analytics.index'))
            ->assertOk()->assertDontSee('Welfare Score')->assertSee('Average per capita income');
    }

    public function test_middle_and_above_card_filters_households(): void
    {
        $middle = $this->household(20000);  // ₱10,000 per capita → Middle
        $poor   = $this->household(4000);   // ₱2,000 per capita → Poor
        $middle->residents()->where('is_head', true)->update(['last_name' => 'Middleton']);
        $poor->residents()->where('is_head', true)->update(['last_name' => 'Poorman']);

        $this->actingAs($this->admin())->get(route('residents.index', ['psa_status' => 'middle_up']))
            ->assertOk()->assertSee('Middleton')->assertDontSee('Poorman');
    }
}
