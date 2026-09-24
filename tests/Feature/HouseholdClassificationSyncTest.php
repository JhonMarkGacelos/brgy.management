<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Setting;
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

    /** Head earning $headIncome plus a zero-income member; stored classification refreshed. */
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

    private function thresholds(array $values = []): array
    {
        return array_merge([
            '_thresholds'               => '1',
            'per_capita_extremely_poor' => 1500,
            'per_capita_poor'           => 2500,
            'per_capita_near_poor'      => 3500,
            'per_capita_vulnerable'     => 5000,
        ], $values);
    }

    public function test_edit_member_modal_updates_stored_classification(): void
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
        $this->assertSame(
            ClassificationService::classify($household->load('residents'))['classification'],
            $household->classification
        );
        $this->assertSame('Extremely Poor', $household->classification);
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

    public function test_non_increasing_thresholds_are_rejected(): void
    {
        $this->actingAs($this->admin())->post(route('settings.update'), $this->thresholds())
            ->assertSessionDoesntHaveErrors();

        $this->actingAs($this->admin())
            ->post(route('settings.update'), $this->thresholds(['per_capita_poor' => 1500]))
            ->assertSessionHasErrors('per_capita_poor');

        $this->actingAs($this->admin())
            ->post(route('settings.update'), $this->thresholds(['per_capita_extremely_poor' => 0]))
            ->assertSessionHasErrors('per_capita_extremely_poor');

        $this->assertEquals(2500, (float) Setting::get('per_capita_poor'));
    }

    public function test_saving_thresholds_reclassifies_existing_households(): void
    {
        $this->actingAs($this->admin())->post(route('settings.update'), $this->thresholds());
        $household = $this->household(6000); // per capita 3,000 → between Poor and Near Poor
        $before = $household->classification;

        // Raise every threshold well above 3,000 per capita → household becomes Extremely Poor.
        $this->actingAs($this->admin())->post(route('settings.update'), $this->thresholds([
            'per_capita_extremely_poor' => 10000,
            'per_capita_poor'           => 20000,
            'per_capita_near_poor'      => 30000,
            'per_capita_vulnerable'     => 40000,
        ]))->assertSessionDoesntHaveErrors();

        $this->assertNotSame($before, $household->fresh()->classification);
        $this->assertSame('Extremely Poor', $household->fresh()->classification);
    }

    public function test_reclassify_command_fixes_stale_values(): void
    {
        $household = $this->household(20000);
        DB::table('households')->where('id', $household->id)
            ->update(['per_capita_income' => 1, 'classification' => 'Extremely Poor', 'welfare_score' => 0]);

        $this->artisan('households:reclassify')
            ->expectsOutput('Reclassified 1 household(s).')
            ->assertSuccessful();

        $this->assertEquals(10000, (float) $household->fresh()->per_capita_income);
        $this->assertNotSame('Extremely Poor', $household->fresh()->classification);
    }
}
