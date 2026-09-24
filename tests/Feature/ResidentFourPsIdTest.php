<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentFourPsIdTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function baseHeadFields(array $overrides = []): array
    {
        return array_merge([
            'first_name'    => 'Juan',
            'last_name'     => 'Dela Cruz',
            'date_of_birth' => '1990-01-01',
            'gender'        => 'Male',
            'civil_status'  => 'Married',
        ], $overrides);
    }

    private function householdWith4psHead(): Household
    {
        $household = Household::create(['house_no' => '1', 'street' => 'Main', 'purok' => 'Purok 1']);
        $household->residents()->create(array_merge($this->baseHeadFields(), [
            'relationship_to_head' => 'Head', 'is_head' => true, 'nationality' => 'Filipino',
            'is_4ps' => true,
            'fourps_id_url' => 'https://example.com/existing-4ps.jpg',
            'fourps_id_public_id' => 'existing-4ps-id',
        ]));

        return $household;
    }

    public function test_store_blocks_when_head_is_4ps_without_id_file(): void
    {
        $this->actingAs($this->admin())->post(route('residents.store'), [
            'purok' => 'Purok 1',
            'families' => [[
                'head' => $this->baseHeadFields(['is_4ps' => '1']),
                'members' => [],
            ]],
        ])->assertSessionHasErrors('families.0.head.fourps_id_document');

        $this->assertDatabaseCount('households', 0);
    }

    public function test_update_preserves_existing_4ps_id_without_reupload(): void
    {
        $household = $this->householdWith4psHead();

        $this->actingAs($this->admin())->put(route('residents.update', $household->id), [
            'purok' => 'Purok 1',
            'families' => [[
                'head' => $this->baseHeadFields([
                    'is_4ps' => '1',
                    'existing_fourps_id_url' => 'https://example.com/existing-4ps.jpg',
                    'existing_fourps_id_public_id' => 'existing-4ps-id',
                ]),
                'members' => [],
            ]],
        ])->assertSessionDoesntHaveErrors()
          ->assertRedirect(route('residents.show', $household->id));

        $head = Resident::where('household_id', $household->id)->where('is_head', true)->firstOrFail();
        $this->assertTrue((bool) $head->is_4ps);
        $this->assertSame('https://example.com/existing-4ps.jpg', $head->fourps_id_url);
        $this->assertSame('existing-4ps-id', $head->fourps_id_public_id);
    }

    public function test_update_blocks_when_head_newly_marked_4ps_without_file(): void
    {
        $household = Household::create(['house_no' => '1', 'street' => 'Main', 'purok' => 'Purok 1']);
        $household->residents()->create(array_merge($this->baseHeadFields(), [
            'relationship_to_head' => 'Head', 'is_head' => true, 'nationality' => 'Filipino',
        ]));

        $this->actingAs($this->admin())->put(route('residents.update', $household->id), [
            'purok' => 'Purok 1',
            'families' => [[
                'head' => $this->baseHeadFields(['is_4ps' => '1']),
                'members' => [],
            ]],
        ])->assertSessionHasErrors('families.0.head.fourps_id_document');

        $this->assertDatabaseHas('residents', ['household_id' => $household->id, 'is_4ps' => false]);
    }

    public function test_unchecking_4ps_clears_the_id(): void
    {
        $household = $this->householdWith4psHead();

        $this->actingAs($this->admin())->put(route('residents.update', $household->id), [
            'purok' => 'Purok 1',
            'families' => [[
                'head' => $this->baseHeadFields([
                    'existing_fourps_id_url' => 'https://example.com/existing-4ps.jpg',
                    'existing_fourps_id_public_id' => 'existing-4ps-id',
                ]),
                'members' => [],
            ]],
        ])->assertSessionDoesntHaveErrors();

        $head = Resident::where('household_id', $household->id)->where('is_head', true)->firstOrFail();
        $this->assertFalse((bool) $head->is_4ps);
        $this->assertNull($head->fourps_id_url);
        $this->assertNull($head->fourps_id_public_id);
    }

    public function test_update_member_modal_requires_4ps_id_for_head(): void
    {
        $household = Household::create(['house_no' => '1', 'street' => 'Main', 'purok' => 'Purok 1']);
        $head = $household->residents()->create(array_merge($this->baseHeadFields(), [
            'relationship_to_head' => 'Head', 'is_head' => true, 'nationality' => 'Filipino',
        ]));

        $admin = $this->admin();

        $this->actingAs($admin)->put(route('residents.member.update', [$household->id, $head->id]), [
            'first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'gender' => 'Male', 'is_4ps' => '1',
        ])->assertSessionHasErrors('fourps_id_document');

        $this->assertFalse((bool) $head->fresh()->is_4ps);

        $head->update(['fourps_id_url' => 'https://example.com/on-file.jpg', 'fourps_id_public_id' => 'pub4ps']);

        $this->actingAs($admin)->put(route('residents.member.update', [$household->id, $head->id]), [
            'first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'gender' => 'Male', 'is_4ps' => '1',
        ])->assertSessionDoesntHaveErrors();

        $head->refresh();
        $this->assertTrue((bool) $head->is_4ps);
        $this->assertSame('https://example.com/on-file.jpg', $head->fourps_id_url);
    }

    public function test_edit_and_show_pages_render_with_4ps_id_on_file(): void
    {
        $household = $this->householdWith4psHead();
        $admin = $this->admin();

        // The edit form shows the photo through the protected route and never carries the stored link.
        $headId = $household->residents()->where('is_head', true)->value('id');
        $this->actingAs($admin)->get(route('residents.edit', $household->id))
            ->assertOk()
            ->assertSee('id-photo\\/fourps\\/' . $headId, false)
            ->assertDontSee('existing-4ps.jpg', false);

        // The household page links to the protected route, never the raw image URL.
        $headId = $household->residents()->where('is_head', true)->value('id');
        $this->actingAs($admin)->get(route('residents.show', $household->id))
            ->assertOk()
            ->assertSee(route('id-photo.show', ['fourps', $headId]), false)
            ->assertDontSee('existing-4ps.jpg', false);
    }
}
