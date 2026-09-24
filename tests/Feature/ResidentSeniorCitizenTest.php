<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Resident;
use App\Models\User;
use App\Services\ClassificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResidentSeniorCitizenTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function headFields(array $overrides = []): array
    {
        return array_merge([
            'first_name'    => 'Juan',
            'last_name'     => 'Dela Cruz',
            'date_of_birth' => now()->subYears(40)->toDateString(),
            'gender'        => 'Male',
            'civil_status'  => 'Married',
        ], $overrides);
    }

    public function test_store_flags_member_aged_60_plus_without_checkbox(): void
    {
        $this->actingAs($this->admin())->post(route('residents.store'), [
            'purok' => 'Purok 1',
            'families' => [[
                'head' => $this->headFields(),
                'members' => [[
                    'first_name' => 'Lola', 'last_name' => 'Dela Cruz',
                    'date_of_birth' => now()->subYears(65)->toDateString(),
                    'gender' => 'Female', 'relationship' => 'Mother',
                ]],
            ]],
        ])->assertSessionDoesntHaveErrors();

        $lola = Resident::where('first_name', 'Lola')->firstOrFail();
        $this->assertTrue($lola->is_senior_citizen);
        $this->assertSame(65, $lola->age);
        $this->assertFalse(Resident::where('first_name', 'Juan')->firstOrFail()->is_senior_citizen);
    }

    public function test_senior_checkbox_is_ignored_for_someone_under_60(): void
    {
        $this->actingAs($this->admin())->post(route('residents.store'), [
            'purok' => 'Purok 1',
            'families' => [[
                'head' => $this->headFields([
                    'date_of_birth' => now()->subYears(30)->toDateString(),
                    'is_senior_citizen' => '1',
                ]),
                'members' => [],
            ]],
        ])->assertSessionDoesntHaveErrors();

        $this->assertFalse(Resident::firstOrFail()->is_senior_citizen);
    }

    public function test_senior_status_starts_on_the_60th_birthday(): void
    {
        $household = Household::create(['purok' => 'Purok 1']);
        $turnsToday = $household->residents()->create(array_merge($this->headFields(), [
            'date_of_birth' => now()->subYears(60)->toDateString(),
            'relationship_to_head' => 'Head', 'is_head' => true,
        ]));
        $turnsTomorrow = $household->residents()->create(array_merge($this->headFields(['first_name' => 'Pedro']), [
            'date_of_birth' => now()->subYears(60)->addDay()->toDateString(),
            'relationship_to_head' => 'Brother', 'is_head' => false,
        ]));

        $this->assertTrue($turnsToday->is_senior_citizen);
        $this->assertFalse($turnsTomorrow->is_senior_citizen);
    }

    public function test_sync_command_fixes_stale_flags_and_refreshes_score(): void
    {
        $household = Household::create(['purok' => 'Purok 1']);
        $turnedSixty = $household->residents()->create(array_merge($this->headFields(), [
            'date_of_birth' => now()->subYears(60)->toDateString(),
            'relationship_to_head' => 'Head', 'is_head' => true,
        ]));
        $wronglyFlagged = $household->residents()->create(array_merge($this->headFields(['first_name' => 'Ana']), [
            'date_of_birth' => now()->subYears(25)->toDateString(),
            'relationship_to_head' => 'Daughter', 'is_head' => false,
        ]));
        ClassificationService::refresh($household);
        $scoreBefore = (float) $household->fresh()->welfare_score;

        // Simulate records saved before the birthday / before this rule existed.
        DB::table('residents')->where('id', $turnedSixty->id)->update(['is_senior_citizen' => false, 'age' => 59]);
        DB::table('residents')->where('id', $wronglyFlagged->id)->update(['is_senior_citizen' => true]);
        DB::table('households')->where('id', $household->id)->update(['welfare_score' => 0]);

        $this->artisan('residents:sync-seniors')
            ->expectsOutput('Updated 2 resident(s) across 1 household(s).')
            ->assertSuccessful();

        $this->assertTrue($turnedSixty->fresh()->is_senior_citizen);
        $this->assertSame(60, $turnedSixty->fresh()->age);
        $this->assertFalse($wronglyFlagged->fresh()->is_senior_citizen);
        $this->assertEquals($scoreBefore, (float) $household->fresh()->welfare_score);
    }

    public function test_sync_command_is_a_no_op_when_everything_is_correct(): void
    {
        $household = Household::create(['purok' => 'Purok 1']);
        $household->residents()->create(array_merge($this->headFields(), [
            'relationship_to_head' => 'Head', 'is_head' => true,
        ]));

        $this->artisan('residents:sync-seniors')
            ->expectsOutput('Updated 0 resident(s) across 0 household(s).')
            ->assertSuccessful();
    }
}
