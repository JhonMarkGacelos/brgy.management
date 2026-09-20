<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentPwdSoloParentIdTest extends TestCase
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
            'civil_status'  => 'Single',
        ], $overrides);
    }

    public function test_store_blocks_when_head_is_pwd_without_id_file(): void
    {
        $this->actingAs($this->admin())->post(route('residents.store'), [
            'purok' => 'Purok 1',
            'families' => [[
                'head' => $this->baseHeadFields(['is_pwd' => '1']),
                'members' => [],
            ]],
        ])->assertSessionHasErrors('families.0.head.pwd_id_document');

        $this->assertDatabaseCount('households', 0);
    }

    public function test_store_succeeds_without_file_when_no_pwd_or_solo_parent(): void
    {
        $this->actingAs($this->admin())->post(route('residents.store'), [
            'purok' => 'Purok 1',
            'families' => [[
                'head' => $this->baseHeadFields(),
                'members' => [],
            ]],
        ])->assertSessionDoesntHaveErrors()
          ->assertRedirect(route('residents.index'));

        $this->assertDatabaseCount('households', 1);
        $resident = Resident::where('is_head', true)->firstOrFail();
        $this->assertNull($resident->pwd_id_url);
        $this->assertNull($resident->solo_parent_id_url);
    }

    public function test_update_preserves_existing_pwd_id_for_member_without_reupload(): void
    {
        $household = Household::create(['house_no' => '1', 'street' => 'Main', 'purok' => 'Purok 1']);
        $household->residents()->create(array_merge($this->baseHeadFields(), [
            'relationship_to_head' => 'Head', 'is_head' => true, 'nationality' => 'Filipino',
        ]));
        $household->residents()->create([
            'first_name' => 'Maria', 'last_name' => 'Dela Cruz', 'date_of_birth' => '1995-01-01',
            'gender' => 'Female', 'relationship_to_head' => 'Daughter', 'is_head' => false,
            'nationality' => 'Filipino', 'is_pwd' => true,
            'pwd_id_url' => 'https://example.com/existing-pwd.jpg',
            'pwd_id_public_id' => 'existing-public-id',
        ]);

        $this->actingAs($this->admin())->put(route('residents.update', $household->id), [
            'purok' => 'Purok 1',
            'families' => [[
                'head' => $this->baseHeadFields(),
                'members' => [[
                    'first_name' => 'Maria', 'last_name' => 'Dela Cruz', 'date_of_birth' => '1995-01-01',
                    'gender' => 'Female', 'relationship' => 'Daughter', 'is_pwd' => '1',
                    'existing_pwd_id_url' => 'https://example.com/existing-pwd.jpg',
                    'existing_pwd_id_public_id' => 'existing-public-id',
                ]],
            ]],
        ])->assertSessionDoesntHaveErrors()
          ->assertRedirect(route('residents.show', $household->id));

        $member = Resident::where('household_id', $household->id)->where('is_head', false)->firstOrFail();
        $this->assertTrue((bool) $member->is_pwd);
        $this->assertSame('https://example.com/existing-pwd.jpg', $member->pwd_id_url);
        $this->assertSame('existing-public-id', $member->pwd_id_public_id);
    }

    public function test_update_blocks_when_member_newly_marked_pwd_without_file(): void
    {
        $household = Household::create(['house_no' => '1', 'street' => 'Main', 'purok' => 'Purok 1']);
        $household->residents()->create(array_merge($this->baseHeadFields(), [
            'relationship_to_head' => 'Head', 'is_head' => true, 'nationality' => 'Filipino',
        ]));
        $household->residents()->create([
            'first_name' => 'Maria', 'last_name' => 'Dela Cruz', 'date_of_birth' => '1995-01-01',
            'gender' => 'Female', 'relationship_to_head' => 'Daughter', 'is_head' => false,
            'nationality' => 'Filipino', 'is_pwd' => false,
        ]);

        $this->actingAs($this->admin())->put(route('residents.update', $household->id), [
            'purok' => 'Purok 1',
            'families' => [[
                'head' => $this->baseHeadFields(),
                'members' => [[
                    'first_name' => 'Maria', 'last_name' => 'Dela Cruz', 'date_of_birth' => '1995-01-01',
                    'gender' => 'Female', 'relationship' => 'Daughter', 'is_pwd' => '1',
                ]],
            ]],
        ])->assertSessionHasErrors('families.0.members.0.pwd_id_document');

        // Member row must be untouched (delete-and-recreate must not have run).
        $this->assertDatabaseHas('residents', [
            'household_id' => $household->id,
            'first_name'   => 'Maria',
            'is_pwd'       => false,
        ]);
    }

    public function test_update_member_modal_blocks_then_succeeds_once_already_on_file(): void
    {
        $household = Household::create(['house_no' => '1', 'street' => 'Main', 'purok' => 'Purok 1']);
        $member = $household->residents()->create([
            'first_name' => 'Maria', 'last_name' => 'Dela Cruz', 'date_of_birth' => '1995-01-01',
            'gender' => 'Female', 'relationship_to_head' => 'Daughter', 'is_head' => false,
            'nationality' => 'Filipino', 'is_pwd' => false,
        ]);

        $admin = $this->admin();

        // No file, no existing ID -> blocked.
        $this->actingAs($admin)->put(route('residents.member.update', [$household->id, $member->id]), [
            'first_name' => 'Maria', 'last_name' => 'Dela Cruz', 'is_pwd' => '1',
        ])->assertSessionHasErrors('pwd_id_document');

        $this->assertFalse((bool) $member->fresh()->is_pwd);

        // Simulate an ID already on file (e.g. uploaded earlier), then re-save without a new file.
        $member->update(['pwd_id_url' => 'https://example.com/on-file.jpg', 'pwd_id_public_id' => 'pub123']);

        $this->actingAs($admin)->put(route('residents.member.update', [$household->id, $member->id]), [
            'first_name' => 'Maria', 'last_name' => 'Dela Cruz', 'is_pwd' => '1',
        ])->assertSessionDoesntHaveErrors()
          ->assertRedirect(route('residents.show', $household->id));

        $member->refresh();
        $this->assertTrue((bool) $member->is_pwd);
        $this->assertSame('https://example.com/on-file.jpg', $member->pwd_id_url);
    }

    public function test_create_page_renders(): void
    {
        $this->actingAs($this->admin())->get(route('residents.create'))->assertOk();
    }

    public function test_edit_and_show_pages_render_with_existing_ids_on_file(): void
    {
        $household = Household::create(['house_no' => '1', 'street' => 'Main', 'purok' => 'Purok 1']);
        $household->residents()->create(array_merge($this->baseHeadFields(), [
            'relationship_to_head' => 'Head', 'is_head' => true, 'nationality' => 'Filipino',
            'is_pwd' => true, 'pwd_id_url' => 'https://example.com/id.jpg', 'pwd_id_public_id' => 'pub1',
        ]));
        $household->residents()->create([
            'first_name' => 'Maria', 'last_name' => 'Dela Cruz', 'date_of_birth' => '1995-01-01',
            'gender' => 'Female', 'relationship_to_head' => 'Daughter', 'is_head' => false,
            'nationality' => 'Filipino', 'is_solo_parent' => true,
            'solo_parent_id_url' => 'https://example.com/id2.jpg', 'solo_parent_id_public_id' => 'pub2',
        ]);

        $admin = $this->admin();
        $this->actingAs($admin)->get(route('residents.edit', $household->id))->assertOk();
        $this->actingAs($admin)->get(route('residents.show', $household->id))->assertOk();
    }
}
