<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Resident;
use App\Models\User;
use App\Services\ClassificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentSocialPensionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /** $psaStatus null = household not assessed (no income entered). */
    private function household(?string $psaStatus = 'Middle'): Household
    {
        $household = Household::create(['purok' => 'Purok 1']);
        $household->forceFill(['psa_status' => $psaStatus])->save();

        return $household;
    }

    private function resident(Household $household, int $age, array $attrs = []): Resident
    {
        return $household->residents()->create(array_merge([
            'first_name' => 'Res', 'last_name' => 'Ident',
            'date_of_birth' => now()->subYears($age)->toDateString(),
            'gender' => 'Female', 'relationship_to_head' => 'Mother', 'is_head' => false,
        ], $attrs));
    }

    private function storePayload(array $member): array
    {
        return [
            'purok' => 'Purok 1',
            'families' => [[
                'head' => [
                    'first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'gender' => 'Male', 'civil_status' => 'Married',
                    'date_of_birth' => now()->subYears(40)->toDateString(),
                    // Hidden for non-seniors; must be ignored rather than block the save.
                    'pension' => 'other',
                ],
                'members' => [array_merge([
                    'first_name' => 'Lola', 'last_name' => 'Dela Cruz', 'gender' => 'Female', 'relationship' => 'Mother',
                    'date_of_birth' => now()->subYears(65)->toDateString(),
                ], $member)],
            ]],
        ];
    }

    public function test_store_saves_pension_and_amount_for_senior_and_clears_them_for_non_senior(): void
    {
        $this->actingAs($this->admin())
            ->post(route('residents.store'), $this->storePayload(['pension' => 'other', 'pension_amount' => '3500']))
            ->assertSessionDoesntHaveErrors();

        $lola = Resident::where('first_name', 'Lola')->firstOrFail();
        $this->assertTrue($lola->has_other_pension);
        $this->assertFalse($lola->is_social_pensioner);
        $this->assertEquals(3500, (float) $lola->pension_amount);

        $juan = Resident::where('first_name', 'Juan')->firstOrFail();
        $this->assertFalse($juan->is_social_pensioner);
        $this->assertFalse($juan->has_other_pension);
        $this->assertNull($juan->pension_amount);
    }

    public function test_store_requires_amount_when_a_pension_is_chosen(): void
    {
        $this->actingAs($this->admin())
            ->post(route('residents.store'), $this->storePayload(['pension' => 'other']))
            ->assertSessionHasErrors('families.0.members.0.pension_amount');

        $this->assertDatabaseCount('households', 0);
    }

    public function test_store_requires_senior_id_for_social_pension(): void
    {
        $this->actingAs($this->admin())
            ->post(route('residents.store'), $this->storePayload(['pension' => 'social', 'pension_amount' => '1000']))
            ->assertSessionHasErrors('families.0.members.0.senior_id_document');

        $this->assertDatabaseCount('households', 0);
    }

    public function test_per_capita_income_includes_pension_amount(): void
    {
        $household = $this->household();
        $this->resident($household, 40, ['is_head' => true, 'relationship_to_head' => 'Head', 'monthly_income' => 5000]);
        $this->resident($household, 70, ['has_other_pension' => true, 'pension_amount' => 3000]);

        $result = ClassificationService::classify($household->fresh());

        $this->assertEquals(8000, $result['total_income']);
        $this->assertEquals(3000, $result['pension_income']);
        $this->assertEquals(4000, $result['per_capita']);
    }

    public function test_edit_member_modal_sets_single_pension_choice(): void
    {
        $household = $this->household();
        $lola = $this->resident($household, 70, [
            'first_name' => 'Lola', 'is_social_pensioner' => true, 'pension_amount' => 1000,
            'senior_id_url' => 'https://example.com/osca.jpg', 'senior_id_public_id' => 'osca1',
        ]);
        $this->assertSame('social', $lola->pension);

        $admin = $this->admin();
        $route = route('residents.member.update', [$household->id, $lola->id]);
        $fields = ['first_name' => 'Lola', 'last_name' => 'Ident', 'gender' => 'Female', 'date_of_birth' => now()->subYears(70)->toDateString()];

        // Keeping Social Pension with the ID already on file needs no re-upload.
        $this->actingAs($admin)->put($route, $fields + ['pension' => 'social', 'pension_amount' => '1000'])->assertSessionDoesntHaveErrors();
        $this->assertSame('https://example.com/osca.jpg', $lola->fresh()->senior_id_url);

        $this->actingAs($admin)->put($route, $fields + ['pension' => 'other'])->assertSessionHasErrors('pension_amount');

        $this->actingAs($admin)->put($route, $fields + ['pension' => 'other', 'pension_amount' => '2500'])->assertSessionDoesntHaveErrors();
        $lola->refresh();
        $this->assertFalse($lola->is_social_pensioner);
        $this->assertTrue($lola->has_other_pension);
        $this->assertSame('other', $lola->pension);
        $this->assertEquals(2500, (float) $lola->pension_amount);
        $this->assertNull($lola->senior_id_url);

        $this->actingAs($admin)->put($route, $fields + ['pension' => 'none', 'pension_amount' => '2500'])->assertSessionDoesntHaveErrors();
        $this->assertSame('none', $lola->fresh()->pension);
        $this->assertNull($lola->fresh()->pension_amount);

        // Newly marked Social Pension without an ID on file is blocked.
        $this->actingAs($admin)->put($route, $fields + ['pension' => 'social', 'pension_amount' => '1000'])->assertSessionHasErrors('senior_id_document');

        $this->actingAs($admin)->put($route, $fields + ['pension' => 'both'])->assertSessionHasErrors('pension');
    }

    public function test_candidate_scope(): void
    {
        $notPoor    = $this->household('Middle');
        $poor       = $this->household('Poor');
        $unassessed = $this->household(null);

        $poorHousehold     = $this->resident($poor, 70, ['first_name' => 'PoorHousehold']);
        $indigentNoIncome  = $this->resident($unassessed, 70, ['first_name' => 'IndigentNoIncome', 'is_indigent' => true]);
        // Tagged Indigent but the household's recorded income is well above the poverty line (the ₱800k case).
        $indigentRich      = $this->resident($notPoor, 70, ['first_name' => 'IndigentRich', 'is_indigent' => true]);
        $alreadyPensioner  = $this->resident($poor, 70, ['first_name' => 'AlreadyPensioner', 'is_social_pensioner' => true]);
        $hasSss            = $this->resident($poor, 70, ['first_name' => 'HasSss', 'has_other_pension' => true]);
        $unassessedNoTag   = $this->resident($unassessed, 70, ['first_name' => 'UnassessedNoTag']);
        $young             = $this->resident($poor, 40, ['first_name' => 'Young']);

        $candidates = Resident::socialPensionCandidates()->pluck('first_name')->sort()->values()->all();
        $this->assertSame(['IndigentNoIncome', 'PoorHousehold'], $candidates);

        $this->assertTrue($poorHousehold->fresh()->is_social_pension_candidate);
        $this->assertTrue($indigentNoIncome->fresh()->is_social_pension_candidate);
        foreach ([$indigentRich, $alreadyPensioner, $hasSss, $unassessedNoTag, $young] as $r) {
            $this->assertFalse($r->fresh()->is_social_pension_candidate, $r->first_name);
        }
    }

    public function test_roster_filters(): void
    {
        $poor = $this->household('Poor');
        $this->resident($poor, 70, ['first_name' => 'Alvarado', 'is_social_pensioner' => true]);
        $this->resident($poor, 70, ['first_name' => 'Bustamante']);
        $this->resident($poor, 30, ['first_name' => 'Castellano']);

        $admin = $this->admin();

        $this->actingAs($admin)->get(route('residents.list', ['sector' => 'Social Pension']))
            ->assertOk()->assertSee('Alvarado')->assertDontSee('Bustamante')->assertDontSee('Castellano');

        $this->actingAs($admin)->get(route('residents.list', ['sector' => 'Social Pension Candidates']))
            ->assertOk()->assertSee('Bustamante')->assertDontSee('Alvarado')->assertDontSee('Castellano');
    }

    public function test_social_pension_does_not_change_welfare_score(): void
    {
        $household = $this->household();
        $senior = $this->resident($household, 70, ['is_head' => true, 'relationship_to_head' => 'Head']);
        ClassificationService::refresh($household);
        $before = (float) $household->fresh()->welfare_score;

        $senior->update(['is_social_pensioner' => true]);
        ClassificationService::refresh($household);

        $this->assertEquals($before, (float) $household->fresh()->welfare_score);
    }

    public function test_show_and_edit_pages_render_pension_and_senior_id(): void
    {
        $household = $this->household();
        $this->resident($household, 72, [
            'is_head' => true, 'relationship_to_head' => 'Head', 'monthly_income' => 500,
            'is_social_pensioner' => true, 'pension_amount' => 1000,
            'senior_id_url' => 'https://example.com/osca-on-file.jpg', 'senior_id_public_id' => 'osca2',
        ]);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('residents.show', $household->id))
            ->assertOk()
            ->assertSee('+ ₱1,000.00 pension', false)
            ->assertSee('Senior Citizen ID', false)
            ->assertSee(route('id-photo.show', ['senior', $household->residents()->value('id')]), false)
            ->assertDontSee('osca-on-file.jpg', false);

        $this->actingAs($admin)->get(route('residents.edit', $household->id))
            ->assertOk()
            ->assertSee('id-photo\\/senior\\/' . $household->residents()->value('id'), false)
            ->assertDontSee('osca-on-file.jpg', false);
    }

    public function test_dashboard_and_show_pages_render(): void
    {
        $poor = $this->household('Poor');
        $this->resident($poor, 70, ['is_head' => true, 'relationship_to_head' => 'Head']);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Pension Candidates');
        $this->actingAs($admin)->get(route('residents.show', $poor->id))->assertOk()->assertSee('Possible Social Pension candidate');
        $this->actingAs($admin)->get(route('analytics.index'))->assertOk()->assertSee('Social Pension');
        $this->actingAs($admin)->get(route('analytics.index', ['sector' => 'Social Pension']))->assertOk();
    }
}
