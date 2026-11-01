<?php

namespace Tests\Feature;

use App\Models\Edition;
use App\Models\Participation;
use App\Models\User;
use App\Services\CheckInService;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketRecords;
use Tests\TestCase;

class V2ReservationManagementTest extends TestCase
{
    use CreatesTicketRecords, RefreshDatabase;

    private function owner(): User
    {
        $owner = $this->makeAdmin();
        $owner->forceFill(['is_super_admin' => true, 'password' => 'Owner-Password-123!'])->save();

        return $owner;
    }

    private function approval(Participation $p): array
    {
        return ['current_password' => 'Owner-Password-123!', 'confirmation_reference' => $p->reference, 'reason' => 'Essai caméra'];
    }

    public function test_only_owner_can_remove_a_scanned_test_and_all_its_records(): void
    {
        $owner = $this->owner();
        $ticket = $this->makeTicket();
        $p = $ticket->participation;
        app(CheckInService::class)->confirm($ticket->reference, $p->edition_id, $owner);
        $url = '/admin/participations/'.$p->id.'/purge-test';
        $publicUrl = app(TicketService::class)->publicUrl($ticket);
        $this->actingAs($this->makeAdmin())->post($url, $this->approval($p))->assertForbidden();
        $this->app['session']->flush();
        $this->actingAs($owner)->post($url, array_replace($this->approval($p), ['confirmation_reference' => 'wrong']))->assertSessionHasErrors();
        $this->assertDatabaseCount('attendances', 1);
        $this->post($url, $this->approval($p))->assertSessionHasNoErrors()->assertRedirect('/admin/participations');
        $this->assertDatabaseCount('attendances', 0);
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
        $this->assertDatabaseMissing('participations', ['id' => $p->id]);
        $this->get($publicUrl)->assertNotFound();
        $this->assertDatabaseHas('admin_actions', ['action' => 'admin.participations.purge-test']);
    }

    public function test_owner_can_undo_an_entry_without_deleting_the_reservation(): void
    {
        $owner = $this->owner();
        $ticket = $this->makeTicket();
        $p = $ticket->participation;
        app(CheckInService::class)->confirm($ticket->reference, $p->edition_id, $owner);
        $this->actingAs($owner)->post('/admin/participations/'.$p->id.'/undo-entry', $this->approval($p))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('attendances', 0);
        $this->assertSame('valid', $ticket->fresh()->status);
        $this->assertTrue(app(CheckInService::class)->confirm($ticket->reference, $p->edition_id, $owner)['ok']);
    }

    public function test_correction_preserves_other_reservations_and_rejects_duplicate_phones(): void
    {
        $p = $this->makeParticipation(true);
        $other = $this->makeParticipation();
        $other->update(['participant_id' => $p->participant_id]);
        $data = ['full_name' => 'Nom corrigé', 'phone' => '+2250700000002', 'reason' => 'Erreur de saisie'];
        $this->actingAs($this->makeAdmin())->put('/admin/participations/'.$p->id.'/manage', $data)->assertSessionHasNoErrors();
        $this->assertSame('Nom corrigé', $p->fresh()->participant->full_name);
        $this->assertFalse($p->fresh()->whatsapp_opt_in);
        $this->assertSame('Participant test', $other->fresh()->participant->full_name);
        $this->put('/admin/participations/'.$other->id.'/manage', $data)->assertSessionHasErrors('phone');
    }

    public function test_exports_filter_rows_and_never_include_private_ticket_tokens(): void
    {
        $ticket = $this->makeTicket();
        $p = $ticket->participation;
        $p->participant->update(['full_name' => '=HYPERLINK("bad")']);
        $other = $this->makeTicket();
        $other->participation->participant->update(['full_name' => 'Autre participant']);
        $admin = $this->owner();
        app(CheckInService::class)->confirm($ticket->reference, $p->edition_id, $admin);
        foreach (['participations', 'tickets', 'attendances'] as $kind) {
            $response = $this->actingAs($admin)->get('/admin/exports/'.$kind.'?q='.urlencode($p->participant->fresh()->full_name));
            $response->assertOk()->assertHeader('Cache-Control', 'no-store, private');
            $csv = $response->streamedContent();
            $this->assertStringContainsString("'=HYPERLINK", $csv);
            $this->assertStringNotContainsString('Autre participant', $csv);
            $this->assertStringNotContainsString($ticket->access_token, $csv);
            $this->assertStringNotContainsString($ticket->qr_token, $csv);
        }
        $controller = User::factory()->create();
        $controller->forceFill(['team_role' => 'controller'])->save();
        $this->app['session']->flush();
        $this->actingAs($controller)->get('/admin/exports/participations')->assertForbidden();
    }

    public function test_admin_cannot_edit_owner_and_last_owner_cannot_be_demoted(): void
    {
        $owner = $this->owner();
        $data = ['name' => $owner->name, 'email' => $owner->email, 'role' => 'admin', 'is_active' => 1, 'current_password' => 'Owner-Password-123!'];
        $this->actingAs($this->makeAdmin())->put('/admin/team/'.$owner->id, $data)->assertForbidden();
        $this->app['session']->flush();
        $this->actingAs($owner)->put('/admin/team/'.$owner->id, $data)->assertSessionHasErrors('role');
        $this->assertTrue($owner->fresh()->is_super_admin);
        $this->get('/admin/participations/'.$this->makeParticipation()->id.'/manage')->assertOk()->assertSee('Actions du Super ADMIN');
    }

    public function test_reopening_and_replacing_revoke_previous_qr_and_private_url(): void
    {
        $owner = $this->owner();
        $ticket = $this->makeTicket();
        $p = $ticket->participation;
        $oldUrl = app(TicketService::class)->publicUrl($ticket);
        $oldRef = $ticket->reference;
        $this->actingAs($owner)->post('/admin/participations/'.$p->id.'/replace-ticket', $this->approval($p))->assertSessionHasNoErrors();
        $this->assertNotSame($oldRef, $ticket->fresh()->reference);
        $this->get($oldUrl)->assertNotFound();
        $this->post('/admin/participations/'.$p->id.'/cancel', ['reason' => 'Erreur'])->assertSessionHasNoErrors();
        $this->post('/admin/participations/'.$p->id.'/reopen', $this->approval($p))->assertSessionHasNoErrors();
        $this->assertSame('pending', $p->fresh()->status);
        $this->assertSame('cancelled', $ticket->fresh()->status);
        $this->post('/admin/participations/'.$p->id.'/review', ['decision' => 'approve'])->assertSessionHasNoErrors();
        $this->assertSame('valid', $ticket->fresh()->status);
        $this->assertDatabaseCount('tickets', 1);
    }

    public function test_active_edition_is_default_and_all_editions_remains_available(): void
    {
        $p = $this->makeParticipation();
        $other = Edition::create(['number' => 2, 'name' => 'Ancienne édition', 'slug' => 'ancienne', 'theme' => 'Prière', 'starts_at' => '2026-01-01', 'venue' => 'Abidjan', 'is_active' => false, 'is_published' => true]);
        $old = $this->makeParticipation();
        $old->update(['edition_id' => $other->id]);
        $this->actingAs($this->owner())->get('/admin/participations')->assertOk()->assertSee($p->reference)->assertDontSee($old->reference);
        $this->get('/admin/participations?edition=')->assertOk()->assertSee($p->reference)->assertSee($old->reference);
    }

    public function test_assisted_registration_obeys_capacity_and_schedule_and_duplicate_makes_a_draft(): void
    {
        $owner = $this->owner();
        $p = $this->makeParticipation();
        $e = $p->edition;
        $e->update(['capacity' => 1]);
        $data = ['edition_id' => $e->id, 'full_name' => 'Nouvelle personne', 'phone' => '+2250700000015', 'reason' => 'Assistance'];
        $this->actingAs($owner)->post('/admin/participations', $data)->assertSessionHasErrors('edition_id');
        $e->update(['capacity' => null, 'reservations_open_at' => now()->addDay()]);
        $this->post('/admin/participations', $data)->assertSessionHasErrors('edition_id');
        $e->update(['reservations_open_at' => null]);
        $this->post('/admin/participations', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('participations', ['status' => 'pending', 'edition_id' => $e->id]);
        $this->post('/admin/editions/'.$e->id.'/duplicate')->assertSessionHasNoErrors();
        $copy = Edition::where('number', 2)->firstOrFail();
        $this->assertFalse($copy->is_active);
        $this->assertFalse($copy->is_published);
        $this->assertSame($e->theme, $copy->theme);
        $this->delete('/admin/editions/'.$e->id, ['confirmation_name' => $e->name, 'current_password' => 'Owner-Password-123!'])->assertSessionHasErrors('edition');
        $this->delete('/admin/editions/'.$copy->id, ['confirmation_name' => $copy->name, 'current_password' => 'Owner-Password-123!'])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('editions', ['id' => $copy->id]);
    }

    public function test_changing_phone_revokes_the_old_private_url_without_resetting_a_used_ticket(): void
    {
        $owner = $this->owner();
        $ticket = $this->makeTicket(true);
        $p = $ticket->participation;
        $oldUrl = app(TicketService::class)->publicUrl($ticket);
        app(CheckInService::class)->confirm($ticket->reference, $p->edition_id, $owner);
        $this->actingAs($owner)->put('/admin/participations/'.$p->id.'/manage', ['full_name' => 'Nom corrigé', 'phone' => '+2250700000088', 'reason' => 'Numéro corrigé'])->assertSessionHasNoErrors();
        $this->get($oldUrl)->assertNotFound();
        $this->assertSame('used', $ticket->fresh()->status);
        $this->assertDatabaseCount('attendances', 1);
        $this->assertFalse($p->fresh()->whatsapp_opt_in);
    }

    public function test_phone_correction_replaces_a_valid_ticket_so_the_previous_pdf_cannot_enter(): void
    {
        $owner = $this->owner();
        $ticket = $this->makeTicket();
        $p = $ticket->participation;
        $oldCode = app(TicketService::class)->qrPayload($ticket);
        $this->actingAs($owner)->put('/admin/participations/'.$p->id.'/manage', ['full_name' => 'Nom corrigé', 'phone' => '+2250700000099', 'reason' => 'Numéro incorrect'])->assertSessionHasNoErrors();
        $this->assertFalse(app(CheckInService::class)->inspect($oldCode, $p->edition_id)['ok']);
        $this->assertTrue(app(CheckInService::class)->inspect(app(TicketService::class)->qrPayload($ticket->fresh()), $p->edition_id)['ok']);
    }
}
