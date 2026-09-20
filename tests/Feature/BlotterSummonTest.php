<?php

namespace Tests\Feature;

use App\Models\BlotterRecord;
use App\Models\User;
use App\Notifications\BlotterStatusUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BlotterSummonTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeRecord(array $overrides = []): BlotterRecord
    {
        return BlotterRecord::create(array_merge([
            'case_number'        => BlotterRecord::generateCaseNumber(),
            'incident_date'      => '2026-01-01',
            'incident_type'      => 'Property Dispute',
            'location'           => 'Purok 2',
            'complainant_name'   => 'Juan Dela Cruz',
            'complainant_address'=> '123 Rizal St., Purok 2',
            'complainant_email'  => 'complainant@example.com',
            'respondent_name'    => 'Pedro Reyes',
            'respondent_address' => '456 Bonifacio St., Purok 3',
            'respondent_email'   => 'respondent@example.com',
            'narrative'          => 'A dispute over a shared fence line.',
            'action_taken'       => 'Recorded for mediation.',
            'status'             => 'Open',
            'filed_by'           => User::factory()->create(['role' => 'admin'])->id,
        ], $overrides));
    }

    public function test_print_summon_blocked_without_hearing_schedule(): void
    {
        $record = $this->makeRecord();

        $response = $this->actingAs($this->admin())->get(route('blotter.summon', $record->id));

        $response->assertRedirect(route('blotter.show', $record->id));
        $response->assertSessionHas('error');
    }

    public function test_print_summon_generates_pdf_when_hearing_schedule_set(): void
    {
        $record = $this->makeRecord([
            'hearing_date' => '2026-02-15',
            'hearing_time' => '14:30',
        ]);

        $response = $this->actingAs($this->admin())->get(route('blotter.summon', $record->id));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_show_page_gates_print_summon_button_on_hearing_schedule(): void
    {
        $admin = $this->admin();

        $withoutSchedule = $this->makeRecord();
        $this->actingAs($admin)->get(route('blotter.show', $withoutSchedule->id))
            ->assertOk()
            ->assertDontSee(route('blotter.summon', $withoutSchedule->id))
            ->assertSee('Set the hearing date and time');

        $withSchedule = $this->makeRecord([
            'hearing_date' => '2026-02-15',
            'hearing_time' => '14:30',
        ]);
        $this->actingAs($admin)->get(route('blotter.show', $withSchedule->id))
            ->assertOk()
            ->assertSee(route('blotter.summon', $withSchedule->id));
    }

    public function test_update_saves_hearing_schedule(): void
    {
        $admin = $this->admin();
        $record = $this->makeRecord();

        $this->actingAs($admin)->put(route('blotter.update', $record->id), [
            'incident_date'    => '2026-01-01',
            'incident_type'    => 'Property Dispute',
            'complainant_name' => 'Juan Dela Cruz',
            'respondent_name'  => 'Pedro Reyes',
            'narrative'        => 'A dispute over a shared fence line.',
            'action_taken'     => 'Recorded for mediation.',
            'status'           => 'Open',
            'hearing_date'     => '2026-03-01',
            'hearing_time'     => '09:00',
        ])->assertRedirect(route('blotter.index'));

        $record->refresh();
        $this->assertSame('2026-03-01', $record->hearing_date->format('Y-m-d'));
        $this->assertSame('09:00:00', \Carbon\Carbon::parse($record->hearing_time)->format('H:i:s'));
    }

    public function test_create_form_never_shows_print_summon_button(): void
    {
        $this->actingAs($this->admin())->get(route('blotter.create'))
            ->assertOk()
            ->assertDontSee('Print Summon');
    }

    public function test_edit_form_gates_print_summon_button_on_hearing_schedule(): void
    {
        $admin = $this->admin();

        $withoutSchedule = $this->makeRecord();
        $this->actingAs($admin)->get(route('blotter.edit', $withoutSchedule->id))
            ->assertOk()
            ->assertDontSee('Print Summon');

        $withSchedule = $this->makeRecord([
            'hearing_date' => '2026-02-15',
            'hearing_time' => '14:30',
        ]);
        $this->actingAs($admin)->get(route('blotter.edit', $withSchedule->id))
            ->assertOk()
            ->assertSee('Print Summon')
            ->assertSee(route('blotter.summon', $withSchedule->id));
    }

    public function test_gmail_notification_workflow_is_unaffected_by_summon_feature(): void
    {
        Notification::fake();

        $admin = $this->admin();
        $record = $this->makeRecord(['status' => 'Open']);

        $this->actingAs($admin)->put(route('blotter.update', $record->id), [
            'incident_date'    => '2026-01-01',
            'incident_type'    => 'Property Dispute',
            'complainant_name' => 'Juan Dela Cruz',
            'respondent_name'  => 'Pedro Reyes',
            'narrative'        => 'A dispute over a shared fence line.',
            'action_taken'     => 'Recorded for mediation.',
            'status'           => 'Under Mediation',
            'hearing_date'     => '2026-03-01',
            'hearing_time'     => '09:00',
        ]);

        // Both parties still get notified exactly as before when status changes,
        // regardless of the new hearing_date/hearing_time fields being present.
        Notification::assertSentOnDemand(BlotterStatusUpdated::class);
        Notification::assertSentTimes(BlotterStatusUpdated::class, 2);
    }
}
