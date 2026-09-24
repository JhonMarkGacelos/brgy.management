<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Resident;
use App\Models\User;
use App\Services\ClassificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsAccuracyTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /** Household with one resident per income given (null = income not entered). */
    private function household(array $incomes, array $headAttrs = []): Household
    {
        $household = Household::create(['purok' => 'Purok 1']);
        foreach (array_values($incomes) as $i => $income) {
            $household->residents()->create(array_merge([
                'first_name' => 'Member' . $i, 'last_name' => 'Test', 'gender' => 'Female',
                'date_of_birth' => now()->subYears(35)->toDateString(),
                'relationship_to_head' => $i === 0 ? 'Head' : 'Daughter', 'is_head' => $i === 0,
                'monthly_income' => $income,
            ], $i === 0 ? $headAttrs : []));
        }
        ClassificationService::refresh($household);

        return $household->fresh();
    }

    public function test_household_without_any_income_entered_is_not_classified_or_counted(): void
    {
        $household = $this->household([null, null]);

        $this->assertNull($household->classification);
        $this->assertNull($household->welfare_score);
        $this->assertNull($household->per_capita_income);
        $this->assertNull($household->psa_status);
    }

    public function test_zero_income_entered_is_still_assessed(): void
    {
        $household = $this->household([0, 0]);

        $this->assertSame('Extremely Poor', $household->classification);
        $this->assertEquals(0, (float) $household->per_capita_income);
        $this->assertSame('Food Poor', $household->psa_status);
    }

    public function test_unassessed_senior_is_not_a_social_pension_candidate(): void
    {
        $this->household([null], ['date_of_birth' => now()->subYears(70)->toDateString()]);

        $this->assertSame(0, Resident::socialPensionCandidates()->count());
    }

    public function test_analytics_reports_family_and_population_incidence(): void
    {
        // Samar defaults: poverty ₱2,420 per capita.
        $this->household([2000, 0, 0]);   // 3 members, per capita 666.67 -> poor
        $this->household([10000]);        // 1 member, not poor
        $this->household([null]);         // not assessed, excluded

        // Families: 1 of 2 = 50%. Population: 3 of 4 people = 75%.
        $this->actingAs($this->admin())->get(route('analytics.index'))
            ->assertOk()
            ->assertSeeInOrder(['50%', 'Poverty incidence among families', '75%', 'Poverty incidence among population'], false);
    }

    public function test_average_per_capita_ignores_unassessed_households(): void
    {
        $this->household([3000]);
        $this->household([null]);

        $this->actingAs($this->admin())->get(route('analytics.index'))
            ->assertOk()
            ->assertSee('₱3,000', false);
    }
}
