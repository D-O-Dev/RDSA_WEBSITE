<?php

namespace Tests\Feature;

use App\Models\Edition;
use App\Models\Participation;
use App\Models\Ticket;
use App\Services\ParticipationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesTicketRecords;
use Tests\TestCase;

class FreeReservationTest extends TestCase
{
    use RefreshDatabase, CreatesTicketRecords;

    private function form(): array
    {
        $edition = Edition::create(['name' => 'RDSA rencontre', 'number' => 1, 'slug' => 'rdsa', 'theme' => 'Sa présence', 'venue' => 'Notre lieu', 'starts_at' => '2027-06-01 09:30:00', 'is_published' => true, 'is_active' => true]);
        $token = $this->get('/')->assertOk()->viewData('submissionToken');
        $this->travel(4)->seconds();
        return ['review_confirmed' => 1, 'has_attended_before' => 0, 'edition_id' => $edition->id, 'submission_token' => $token, 'full_name' => 'Visiteur réservation', 'phone' => '07 00 00 00 01'];
    }

    public function test_request_stays_pending_without_ticket_until_admin_approves(): void
    {
        $data = $this->form();
        $response = $this->postJson('/participations', [...$data, 'status' => 'confirmed', 'payment_amount' => 999999])->assertOk()->assertJson(['status' => 'pending']);
        $record = Participation::firstOrFail();
        $this->assertSame('pending', $record->status);
        $this->assertSame('2250700000001', $record->participant->phone);
        $this->assertDatabaseCount('tickets', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->get($response->json('url'))->assertOk()->assertSee('Demande reçue')->assertDontSee('data:image/svg+xml;base64,', false);
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->post('/admin/participations/' . $record->id . '/review', ['decision' => 'approve'])->assertSessionHasNoErrors();
        $ticket = Ticket::firstOrFail();
        $this->assertSame('confirmed', $record->fresh()->status);
        $this->assertSame($admin->id, $record->fresh()->reviewed_by);
        $this->assertNotNull($record->fresh()->reviewed_at);
        $url = app(\App\Services\TicketService::class)->publicUrl($ticket);
        $this->get($response->json('url'))->assertRedirect($url);
        $this->get($url)->assertOk()->assertSee('data:image/svg+xml;base64,', false);
        $pdf = $this->get($url . '?download=1')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
    }

    public function test_double_submission_is_idempotent_even_after_home_refresh(): void
    {
        $data = $this->form(); $this->get('/');
        $first = $this->postJson('/participations', $data)->assertOk()->json('url');
        $this->postJson('/participations', $data)->assertOk()->assertJson(['url' => $first]);
        $this->assertDatabaseCount('participations', 1);
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_closed_edition_and_invalid_session_cannot_book(): void
    {
        $data = $this->form();
        $this->postJson('/participations', [...$data, 'submission_token' => (string) Str::uuid()])->assertUnprocessable()->assertJsonValidationErrors('submission_token');
        Edition::first()->update(['is_active' => false]);
        $this->postJson('/participations', $data)->assertUnprocessable()->assertJsonValidationErrors('edition_id');
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_ticket_failure_rolls_back_admin_approval(): void
    {
        $record = app(ParticipationService::class)->create($this->form());
        Event::listen('eloquent.creating: ' . Ticket::class, fn () => throw new \RuntimeException('Test émission indisponible'));
        try { app(\App\Services\ReservationReviewService::class)->review($record, $this->makeAdmin(), 'approve'); $this->fail('L’émission doit échouer.'); }
        catch (\RuntimeException $error) { $this->assertSame('Test émission indisponible', $error->getMessage()); }
        $this->assertSame('pending', $record->fresh()->status);
        $this->assertNull($record->fresh()->reviewed_at);
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_reference_plus_phone_recovers_ticket_but_phone_alone_or_wrong_phone_does_not(): void
    {
        $ticket = $this->makeTicket();
        $this->postJson('/mon-billet', ['reference' => $ticket->reference, 'access_code' => '0700000002'])->assertUnprocessable();
        $this->postJson('/mon-billet', ['reference' => '', 'access_code' => '0700000001'])->assertUnprocessable();
        $this->postJson('/mon-billet', ['reference' => $ticket->participation->reference, 'access_code' => '+225 07 00 00 00 01'])->assertOk()->assertJson(['url' => app(\App\Services\TicketService::class)->publicUrl($ticket)]);
    }

    public function test_retired_payment_routes_are_unavailable_and_admin_has_no_financial_controls(): void
    {
        $this->actingAs($this->makeAdmin());
        foreach (['/admin/payments', '/admin/payments/1', '/admin/payment-proofs/1', '/admin/wave-numbers'] as $url) $this->get($url)->assertNotFound();
        $this->post('/admin/payments/1/review', ['decision' => 'verify'])->assertNotFound();
        $this->get('/admin/dashboard')->assertOk()->assertDontSee('FCFA')->assertDontSee('Wave')->assertDontSee('Paiements');
        $this->get('/admin?create=1')->assertOk()->assertDontSee('edition-price')->assertDontSee('Photo paysage')->assertDontSee('Photo portrait');
        $this->get('/admin/participations')->assertOk()->assertDontSee('Montant attendu');
    }

    public function test_ticket_views_do_not_exhaust_lookup_or_admin_login_rate_limits(): void
    {
        $ticket = $this->makeTicket();
        $url = app(\App\Services\TicketService::class)->publicUrl($ticket);
        for ($i = 0; $i < 6; $i++) $this->get($url)->assertOk();
        $this->postJson('/mon-billet', ['reference' => $ticket->reference, 'access_code' => '0700000001'])->assertOk();
        $admin = $this->makeAdmin();
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect('/admin/dashboard');
    }

    public function test_home_keeps_background_only_hero_and_a_lookup_dialog_without_full_lookup_section(): void
    {
        $response = $this->get('/')->assertOk()->assertDontSee('payment_proof')->assertDontSee('FCFA')->assertDontSee('Wave');
        $doc = new \DOMDocument(); @$doc->loadHTML($response->getContent()); $x = new \DOMXPath($doc);
        $this->assertSame(1, $x->query('//section[@id="accueil"]//img')->length);
        $this->assertSame(0, $x->query('//section[@id="accueil"]//a[@href="#participer"]')->length);
        $this->assertSame(0, $x->query('//section[@id="mon-billet"]')->length);
        $this->assertSame(1, $x->query('//dialog[@id="ticket-dialog"]//form')->length);
        $this->assertSame(1, $x->query('//form[@data-reservation-form]')->length);
    }
}
