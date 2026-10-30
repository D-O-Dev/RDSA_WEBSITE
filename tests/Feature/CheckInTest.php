<?php

namespace Tests\Feature;

use App\Models\Edition;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\CreatesTicketRecords;
use Tests\TestCase;

class CheckInTest extends TestCase
{
    use RefreshDatabase, CreatesTicketRecords;

    public function test_control_is_restricted_to_admins(): void
    {
        $this->get('/admin/check-in')->assertRedirect('/admin/login');
        $this->post('/admin/check-in/confirm')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->get('/admin/check-in')->assertForbidden();
        $this->postJson('/admin/check-in/confirm', [])->assertForbidden();
        $this->actingAs($this->makeAdmin())->get('/admin/check-in')->assertOk()->assertSee('Confirmer l’entrée');
    }

    public function test_manual_inspection_does_not_use_ticket_and_confirmation_records_agent(): void
    {
        $ticket = $this->makeTicket();
        $admin = $this->makeAdmin();
        $data = ['edition_id' => $ticket->participation->edition_id, 'code' => strtolower($ticket->reference)];
        $this->actingAs($admin)->postJson('/admin/check-in/inspect', $data)->assertOk()->assertJson(['status' => 'valid', 'can_confirm' => true]);
        $this->assertDatabaseCount('attendances', 0);
        $this->assertSame('valid', $ticket->fresh()->status);
        $this->postJson('/admin/check-in/confirm', $data)->assertOk()->assertJson(['status' => 'used', 'checked_by' => $admin->name]);
        $this->assertSame('used', $ticket->fresh()->status);
        $attendance = $ticket->attendance()->firstOrFail();
        $this->assertSame($admin->id, $attendance->checked_by);
        $this->assertNotNull($attendance->checked_in_at);
        $this->get('/admin/attendances')->assertOk()->assertSee($ticket->reference)->assertSee($admin->name);
        $this->postJson('/admin/check-in/confirm', $data)->assertConflict()->assertJson(['status' => 'already_used']);
        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_qr_token_is_checked_and_duplicate_scan_is_refused(): void
    {
        $ticket = $this->makeTicket();
        $data = ['edition_id' => $ticket->participation->edition_id, 'code' => app(TicketService::class)->qrPayload($ticket)];
        $this->actingAs($this->makeAdmin())->postJson('/admin/check-in/inspect', [...$data, 'code' => 'RDSA1:' . $ticket->reference . ':' . str_repeat('0', 64)])
            ->assertUnprocessable()->assertJson(['status' => 'invalid']);
        $this->assertDatabaseCount('attendances', 0);
        $this->postJson('/admin/check-in/confirm', $data)->assertOk();
        $this->postJson('/admin/check-in/confirm', $data)->assertConflict();
        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_wrong_edition_cancelled_and_unconfirmed_tickets_cannot_enter(): void
    {
        $ticket = $this->makeTicket();
        $other = Edition::create([
            'number' => 2, 'name' => 'Autre édition', 'slug' => 'autre', 'theme' => 'Test',
            'starts_at' => '2027-06-01 08:00:00', 'venue' => 'Autre lieu', 'base_price' => 1000,
        ]);
        $data = ['edition_id' => $ticket->participation->edition_id, 'code' => $ticket->reference];
        $this->actingAs($this->makeAdmin())->postJson('/admin/check-in/confirm', [...$data, 'edition_id' => $other->id])
            ->assertUnprocessable()->assertJson(['status' => 'wrong_edition']);
        $ticket->update(['status' => 'cancelled']);
        $this->postJson('/admin/check-in/confirm', $data)->assertUnprocessable();
        $ticket->update(['status' => 'valid']);
        $ticket->participation->update(['status' => 'cancelled']);
        $this->postJson('/admin/check-in/confirm', $data)->assertUnprocessable();
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_confirmation_rechecks_changes_after_inspection(): void
    {
        $ticket = $this->makeTicket();
        $data = ['edition_id' => $ticket->participation->edition_id, 'code' => $ticket->reference];
        $this->actingAs($this->makeAdmin())->postJson('/admin/check-in/inspect', $data)->assertOk();
        $ticket->participation->update(['status' => 'cancelled']);
        $this->postJson('/admin/check-in/confirm', $data)->assertUnprocessable();
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_attendance_rolls_back_if_ticket_update_fails(): void
    {
        $ticket = $this->makeTicket();
        Event::listen('eloquent.updating: ' . Ticket::class, fn () => throw new \RuntimeException('Test écriture impossible'));
        $this->withoutExceptionHandling()->actingAs($this->makeAdmin());
        try {
            $this->postJson('/admin/check-in/confirm', ['edition_id' => $ticket->participation->edition_id, 'code' => $ticket->reference]);
            $this->fail('L’échec doit annuler le passage.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Test écriture impossible', $exception->getMessage());
        }
        $this->assertDatabaseCount('attendances', 0);
        $this->assertSame('valid', $ticket->fresh()->status);
    }
}
