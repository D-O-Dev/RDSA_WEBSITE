<?php

namespace Tests\Feature;

use App\Models\Edition;
use App\Models\Participant;
use App\Models\Participation;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    private function participation(bool $paid = true): Participation
    {
        $edition = Edition::firstOrCreate(['number' => 1], [
            'name' => 'RDSA test', 'slug' => 'rdsa-test', 'theme' => 'Prière et adoration',
            'starts_at' => '2027-02-13 08:00:00', 'venue' => 'Temple de test',
            'base_price' => 1000, 'is_active' => true,
        ]);
        $participant = Participant::create(['full_name' => 'Participant billet', 'phone' => '07 00 00 00 01']);
        $participation = Participation::create([
            'edition_id' => $edition->id, 'participant_id' => $participant->id,
            'reference' => 'RDSA-' . Str::random(12), 'submission_token' => Str::uuid(),
            'unit_price' => 1000, 'base_amount' => 1000, 'extra_contribution' => 0,
            'total_expected' => 1000, 'status' => $paid ? 'confirmed' : 'pending',
        ]);
        return $participation;
    }

    private function ticket(): Ticket
    {
        return app(TicketService::class)->issue($this->participation());
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();
        return $admin;
    }

    public function test_issuance_is_individual_and_idempotent_and_secrets_are_encrypted(): void
    {
        $participation = $this->participation();
        $service = app(TicketService::class);
        $ticket = $service->issue($participation);
        $this->assertSame($ticket->id, $service->issue($participation)->id);
        $this->assertDatabaseCount('tickets', 1);
        $this->assertSame('valid', $ticket->status);
        $this->assertSame(hash('sha256', $ticket->qr_token), $ticket->qr_token_hash);
        $this->assertSame(hash('sha256', $ticket->access_token), $ticket->access_token_hash);
        $stored = DB::table('tickets')->first();
        $this->assertNotSame($ticket->access_token, $stored->access_token);
        $this->assertNotSame($ticket->qr_token, $stored->qr_token);
        foreach (['qr_token', 'qr_token_hash', 'access_token', 'access_token_hash'] as $secret) {
            $this->assertArrayNotHasKey($secret, $ticket->toArray());
        }
        $this->assertSame('RDSA1:' . $ticket->reference . ':' . $ticket->qr_token, $service->qrPayload($ticket));
    }

    public function test_unconfirmed_reservation_does_not_allow_ticket_issuance(): void
    {
        try {
            app(TicketService::class)->issue($this->participation(false));
            $this->fail('Une réservation non confirmée ne doit pas produire de billet.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('ticket', $exception->errors());
        }
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_private_link_renders_real_qr_and_download_is_self_contained(): void
    {
        $this->withoutExceptionHandling();
        $ticket = $this->ticket();
        $url = app(TicketService::class)->publicUrl($ticket);
        $response = $this->get($url)->assertOk()->assertSee('Participant billet')->assertSee('Billet valide')
            ->assertSee('data:image/svg+xml;base64,', false)->assertSee($ticket->reference)
            ->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertTrue($response->viewData('usable'));
        $response->assertDontSee($ticket->participation->edition->theme)
            ->assertDontSee($ticket->participation->reference)->assertDontSee('Code d’accès');
        $this->assertStringStartsWith('data:image/jpeg;base64,', $response->viewData('logo'));
        $pdfHtml = view('tickets.pdf', app(\App\Services\TicketDocumentService::class)->data($ticket))->render();
        $this->assertStringNotContainsString($ticket->access_token, $pdfHtml);
        $this->assertStringNotContainsString($ticket->participation->edition->theme, $pdfHtml);
        $qr = $response->viewData('qrDataUri');
        $svg = base64_decode(substr($qr, strpos($qr, ',') + 1));
        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('<path', $svg);
        $download = $this->get($url . '?download=1')->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="' . $ticket->reference . '.pdf"');
        $download->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $download->getContent());
    }

    public function test_reference_alone_or_wrong_token_cannot_open_ticket(): void
    {
        $ticket = $this->ticket();
        $other = $this->ticket();
        $this->get('/billets/' . $ticket->reference)->assertNotFound();
        $this->get(route('tickets.show', ['reference' => $ticket->reference, 'token' => str_repeat('A', 32)]))->assertNotFound();
        $this->get(route('tickets.show', ['reference' => $ticket->reference, 'token' => $other->access_token]))->assertNotFound();
        $this->get('/participations/confirmation/' . $ticket->participation->reference)
            ->assertNotFound();
    }

    public function test_public_lookup_requires_reference_and_private_code(): void
    {
        $ticket = $this->ticket();
        $this->get('/mon-billet')->assertOk()->assertSee('Retrouver mon billet');
        $this->post('/mon-billet', ['reference' => $ticket->reference, 'access_code' => 'incorrect'])
            ->assertSessionHasErrors('reference');
        $this->post('/mon-billet', ['reference' => strtolower($ticket->reference), 'access_code' => strtolower($ticket->access_token)])
            ->assertRedirect(app(TicketService::class)->publicUrl($ticket));
    }

    public function test_used_cancelled_or_unconfirmed_ticket_does_not_display_qr(): void
    {
        $ticket = $this->ticket();
        $url = app(TicketService::class)->publicUrl($ticket);
        $ticket->update(['status' => 'used']);
        $this->get($url)->assertOk()->assertSee('Billet déjà utilisé')->assertDontSee('data:image/svg+xml;base64,', false);
        $ticket->update(['status' => 'cancelled']);
        $this->get($url)->assertOk()->assertSee('Billet indisponible')->assertDontSee('data:image/svg+xml;base64,', false);
        $ticket->update(['status' => 'valid']);
        $ticket->participation->update(['status' => 'cancelled']);
        $this->get($url)->assertOk()->assertSee('Billet indisponible')->assertDontSee('data:image/svg+xml;base64,', false);
    }

    public function test_admin_can_share_whatsapp_link_but_visitors_cannot(): void
    {
        $ticket = $this->ticket();
        $this->get('/admin/tickets')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->get('/admin/tickets/' . $ticket->id)->assertForbidden();
        $response = $this->actingAs($this->admin())->get('/admin/tickets/' . $ticket->id)
            ->assertOk()->assertSee('Envoyer sur WhatsApp');
        $whatsapp = $response->viewData('whatsappUrl');
        $this->assertStringStartsWith('https://wa.me/2250700000001?text=', $whatsapp);
        $message = rawurldecode(substr($whatsapp, strpos($whatsapp, '?text=') + 6));
        $this->assertStringContainsString(app(TicketService::class)->publicUrl($ticket), $message);
        $this->assertStringContainsString($ticket->access_token, $message);
        $this->get('/admin/tickets?q=' . $ticket->reference)->assertOk()->assertSee($ticket->reference);
        $this->post('/admin/participations/' . $ticket->participation_id . '/ticket')->assertRedirect('/admin/tickets/' . $ticket->id);
        $this->assertDatabaseCount('tickets', 1);
    }

    public function test_used_ticket_keeps_admin_view_and_pdf_links_without_whatsapp_resending(): void
    {
        $ticket = $this->ticket(); $ticket->update(['status' => 'used']);
        $url = app(TicketService::class)->publicUrl($ticket);
        $response = $this->actingAs($this->admin())->get('/admin/tickets/' . $ticket->id)->assertOk()
            ->assertSee('Ouvrir le billet')->assertSee('Télécharger le PDF')->assertDontSee('Envoyer sur WhatsApp');
        $this->assertSame($url, $response->viewData('publicUrl'));
        $this->assertNull($response->viewData('whatsappUrl'));
        $this->get($url)->assertOk()->assertSee('Billet déjà utilisé')->assertDontSee('data:image/svg+xml;base64,', false);
        $this->get($url . '?download=1')->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_existing_confirmed_reservation_can_receive_its_ticket_from_admin(): void
    {
        $participation = $this->participation();
        $this->actingAs($this->admin())->post('/admin/participations/' . $participation->id . '/ticket')->assertRedirect();
        $this->assertDatabaseCount('tickets', 1);
        $this->get('/admin/tickets')->assertOk()->assertSee($participation->tickets()->firstOrFail()->reference);
    }
}
