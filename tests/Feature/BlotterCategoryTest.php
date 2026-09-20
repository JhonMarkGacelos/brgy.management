<?php

namespace Tests\Feature;

use App\Models\BlotterRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlotterCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function baseFields(array $overrides = []): array
    {
        return array_merge([
            'incident_date'    => '2026-01-01',
            'incident_type'    => 'Theft',
            'complainant_name' => 'Juan Dela Cruz',
            'respondent_name'  => 'Pedro Reyes',
            'narrative'        => 'Something happened.',
            'action_taken'     => 'Recorded for follow-up.',
            'status'           => 'Open',
        ], $overrides);
    }

    public function test_store_blocks_others_without_specify_text(): void
    {
        $this->actingAs($this->admin())->post(route('blotter.store'), $this->baseFields([
            'incident_type' => 'Others',
        ]))->assertSessionHasErrors('incident_type_other');

        $this->assertDatabaseCount('blotter_records', 0);
    }

    public function test_store_succeeds_with_others_and_specify_text(): void
    {
        $this->actingAs($this->admin())->post(route('blotter.store'), $this->baseFields([
            'incident_type'       => 'Others',
            'incident_type_other' => 'Missing pet dispute',
        ]))->assertSessionDoesntHaveErrors()
          ->assertRedirect(route('blotter.index'));

        $record = BlotterRecord::firstOrFail();
        $this->assertSame('Others', $record->incident_type);
        $this->assertSame('Missing pet dispute', $record->incident_type_other);
    }

    public function test_store_ignores_specify_text_for_predefined_category(): void
    {
        $this->actingAs($this->admin())->post(route('blotter.store'), $this->baseFields([
            'incident_type'       => 'Theft',
            'incident_type_other' => 'Should be ignored',
        ]))->assertSessionDoesntHaveErrors();

        $record = BlotterRecord::firstOrFail();
        $this->assertSame('Theft', $record->incident_type);
        $this->assertNull($record->incident_type_other);
    }

    public function test_store_rejects_old_renamed_category_values(): void
    {
        $this->actingAs($this->admin())->post(route('blotter.store'), $this->baseFields([
            'incident_type' => 'Domestic',
        ]))->assertSessionHasErrors('incident_type');

        $this->actingAs($this->admin())->post(route('blotter.store'), $this->baseFields([
            'incident_type' => 'Family / Domestic Dispute',
        ]))->assertSessionDoesntHaveErrors();

        $this->assertDatabaseCount('blotter_records', 1);
    }

    public function test_update_preserves_others_specify_text_when_unchanged(): void
    {
        $admin = $this->admin();
        $record = BlotterRecord::create(array_merge($this->baseFields([
            'incident_type'       => 'Others',
            'incident_type_other' => 'Loud karaoke every night',
        ]), [
            'case_number' => BlotterRecord::generateCaseNumber(),
            'filed_by'    => $admin->id,
        ]));

        $this->actingAs($admin)->put(route('blotter.update', $record->id), $this->baseFields([
            'incident_type'       => 'Others',
            'incident_type_other' => 'Loud karaoke every night',
            'status'              => 'Open',
        ]))->assertSessionDoesntHaveErrors()
          ->assertRedirect(route('blotter.index'));

        $this->assertSame('Loud karaoke every night', $record->fresh()->incident_type_other);
    }

    public function test_update_blocks_others_without_specify_text(): void
    {
        $admin = $this->admin();
        $record = BlotterRecord::create(array_merge($this->baseFields(), [
            'case_number' => BlotterRecord::generateCaseNumber(),
            'filed_by'    => $admin->id,
        ]));

        $this->actingAs($admin)->put(route('blotter.update', $record->id), $this->baseFields([
            'incident_type' => 'Others',
        ]))->assertSessionHasErrors('incident_type_other');
    }

    public function test_create_edit_index_and_show_pages_render(): void
    {
        $admin = $this->admin();
        $record = BlotterRecord::create(array_merge($this->baseFields([
            'incident_type'       => 'Others',
            'incident_type_other' => 'Boundary marker moved',
        ]), [
            'case_number' => BlotterRecord::generateCaseNumber(),
            'filed_by'    => $admin->id,
        ]));

        $this->actingAs($admin)->get(route('blotter.create'))->assertOk();
        $this->actingAs($admin)->get(route('blotter.edit', $record->id))->assertOk();
        $this->actingAs($admin)->get(route('blotter.index'))->assertOk()->assertSee('Boundary marker moved');
        $this->actingAs($admin)->get(route('blotter.show', $record->id))->assertOk()->assertSee('Boundary marker moved');
    }
}
