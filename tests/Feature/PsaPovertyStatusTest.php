<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Setting;
use App\Models\User;
use App\Services\ClassificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PsaPovertyStatusTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function configure(float $food = 1500, float $poverty = 2500): void
    {
        Setting::set('psa_food_threshold', $food);
        Setting::set('psa_poverty_threshold', $poverty);
        Setting::set('psa_threshold_source', 'Test source');
    }

    /** Single-member household with the given monthly income (null = not entered). */
    private function household(?float $income, array $attrs = []): Household
    {
        $household = Household::create(['purok' => 'Purok 1']);
        $household->residents()->create(array_merge([
            'first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'gender' => 'Male',
            'date_of_birth' => now()->subYears(40)->toDateString(),
            'relationship_to_head' => 'Head', 'is_head' => true, 'monthly_income' => $income,
        ], $attrs));
        ClassificationService::refresh($household);

        return $household->fresh();
    }

    public function test_status_boundaries_follow_psa_and_pids_multiples(): void
    {
        $cases = [
            0      => 'Food Poor',
            1499   => 'Food Poor',
            1500   => 'Poor',          // at the food threshold = no longer food poor
            2499   => 'Poor',
            2500   => 'Low Income',    // at the poverty line = not poor (PSA: poor is *below*)
            4999   => 'Low Income',
            5000   => 'Lower Middle',  // 2x
            10000  => 'Middle',        // 4x
            17500  => 'Upper Middle',  // 7x
            29999  => 'Upper Middle',
            30000  => 'High Income',   // 12x
        ];
        foreach ($cases as $perCapita => $expected) {
            $this->assertSame($expected, ClassificationService::psaStatusFor($perCapita, 1500, 2500), "₱{$perCapita}");
        }
    }

    public function test_refresh_stores_psa_status_and_counts_pension(): void
    {
        $this->configure();
        $this->assertSame('Poor', $this->household(2000)->psa_status);

        // 2,000 income + 1,000 pension = 3,000 per capita -> Low Income
        $household = $this->household(2000, [
            'date_of_birth' => now()->subYears(70)->toDateString(),
            'is_social_pensioner' => true, 'pension_amount' => 1000,
        ]);
        $this->assertSame('Low Income', $household->psa_status);
    }

    public function test_not_assessed_when_no_income_entered_and_zero_is_assessed(): void
    {
        $this->configure();
        $this->assertNull($this->household(null)->psa_status);
        $this->assertSame('Food Poor', $this->household(0)->psa_status);
    }

    public function test_samar_defaults_apply_until_thresholds_are_saved(): void
    {
        $t = ClassificationService::psaThresholds();
        $this->assertEquals(2420, $t['poverty']);
        $this->assertEquals(1664, $t['food']);
        $this->assertTrue($t['is_default']);
        $this->assertStringContainsString('Samar', $t['source']);

        // Samar: food ₱1,664, poverty ₱2,420 per person per month.
        $this->assertSame('Food Poor', $this->household(1663)->psa_status);
        $this->assertSame('Poor', $this->household(1664)->psa_status);
        $this->assertSame('Poor', $this->household(2419)->psa_status);
        $this->assertSame('Low Income', $this->household(2420)->psa_status);
    }

    public function test_food_threshold_is_required(): void
    {
        $this->actingAs($this->admin())->post(route('settings.update'), [
            '_psa' => '1', 'psa_poverty_threshold' => 2500, 'psa_food_threshold' => '', 'psa_threshold_source' => 'PSA 2023, Samar',
        ])->assertSessionHasErrors('psa_food_threshold');
    }

    public function test_sector_tags_do_not_change_psa_status_but_do_change_barangay_score(): void
    {
        $this->configure();
        $plain  = $this->household(3000);
        $tagged = $this->household(3000, ['is_4ps' => true, 'is_indigent' => true, 'is_pwd' => true]);

        $this->assertSame($plain->psa_status, $tagged->psa_status);
        $this->assertLessThan((float) $plain->welfare_score, (float) $tagged->welfare_score);
    }

    public function test_settings_validates_and_reclassifies(): void
    {
        $household = $this->household(2000);
        $this->assertSame('Poor', $household->psa_status); // Samar default poverty threshold ₱2,420

        $this->actingAs($this->admin())->post(route('settings.update'), [
            '_psa' => '1', 'psa_food_threshold' => 2500, 'psa_poverty_threshold' => 2500, 'psa_threshold_source' => 'x',
        ])->assertSessionHasErrors('psa_food_threshold');

        $this->actingAs($this->admin())->post(route('settings.update'), [
            '_psa' => '1', 'psa_food_threshold' => 1500, 'psa_poverty_threshold' => 2500,
        ])->assertSessionHasErrors('psa_threshold_source');

        $this->actingAs($this->admin())->post(route('settings.update'), [
            '_psa' => '1', 'psa_food_threshold' => 1000, 'psa_poverty_threshold' => 1800, 'psa_threshold_source' => 'PSA 2023, Samar',
        ])->assertSessionDoesntHaveErrors();

        // 2,000 is now above the (lower) poverty line -> reclassified on save
        $this->assertSame('Low Income', $household->fresh()->psa_status);
        $this->assertFalse(ClassificationService::psaThresholds()['is_default']);
    }

    public function test_households_list_filters_by_psa_status(): void
    {
        $this->configure();
        $this->household(2000, ['first_name' => 'Alvarado']);   // Poor
        $this->household(1000, ['first_name' => 'Bustamante']); // Food Poor
        $this->household(9000, ['first_name' => 'Castellano']); // Lower Middle
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('residents.index', ['psa_status' => 'below']))
            ->assertOk()->assertSee('Alvarado')->assertSee('Bustamante')->assertDontSee('Castellano');

        $this->actingAs($admin)->get(route('residents.index', ['psa_status' => 'Lower Middle']))
            ->assertOk()->assertSee('Castellano')->assertDontSee('Alvarado');
    }

    public function test_pages_render_psa_sections(): void
    {
        $admin = $this->admin();
        $household = $this->household(2000);

        // Defaults: PSA view is shown first, with the Welfare Score available from the header switch.
        $this->actingAs($admin)->get(route('residents.show', $household->id))
            ->assertOk()
            ->assertSeeInOrder(['PSA Status', 'Welfare Score'])
            ->assertSee("view: 'psa'", false)
            ->assertSee('Samar (poverty ₱12,100 / food ₱8,320 per month for a family of five)', false)
            ->assertDontSee('national', false);
        $this->actingAs($admin)->get(route('settings.index'))
            ->assertOk()->assertSee('Pre-filled with the PSA 2023 poverty and food thresholds', false)->assertDontSee('national', false);

        $this->configure();
        ClassificationService::refresh($household);

        $this->actingAs($admin)->get(route('residents.show', $household->id))
            ->assertOk()->assertSee('PSA Poverty Status')->assertSee('Below the PSA poverty line')->assertSee('Test source');
        $this->actingAs($admin)->get(route('analytics.index'))
            ->assertOk()->assertSee('Households below the PSA poverty line');
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()->assertSee('PSA Poverty Status');
        $this->actingAs($admin)->get(route('settings.index'))
            ->assertOk()->assertSee('PSA Poverty Thresholds')->assertSee('Barangay Welfare Score Thresholds');
    }
}
