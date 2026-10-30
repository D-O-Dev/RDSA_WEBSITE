<?php

namespace Tests\Feature;

use App\Models\WhatsappDelivery;
use App\Models\WhatsappSetting;
use App\Services\TicketDocumentService;
use App\Services\TicketService;
use App\Services\WhatsappTicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTicketRecords;
use Tests\TestCase;

class WhatsappTest extends TestCase
{
    use RefreshDatabase, CreatesTicketRecords;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->mock(TicketDocumentService::class, fn ($mock) => $mock->shouldReceive('pdf')->andReturn('%PDF-TEST'));
    }

    private function enable(): WhatsappSetting
    {
        $settings = WhatsappSetting::current();
        $settings->fill([
            'enabled' => true, 'phone_number_id' => '123456789', 'access_token' => 'TEST-TOKEN',
            'app_secret' => 'TEST-SECRET', 'template_name' => 'rdsa_billet', 'template_language' => 'fr',
            'public_base_url' => 'https://rdsa.example.test',
        ])->save();
        return $settings;
    }

    private function delivery(): WhatsappDelivery
    {
        $ticket = $this->makeTicket(true);
        $this->enable();
        return WhatsappDelivery::create(['ticket_id' => $ticket->id]);
    }

    private function fakeSuccess(): void
    {
        Http::fake([
            'graph.facebook.com/*/media' => Http::response(['id' => 'MEDIA-123']),
            'graph.facebook.com/*/messages' => Http::response(['messages' => [['id' => 'wamid.TEST']]]),
        ]);
    }

    private function webhook(array $status, bool $validSignature = true)
    {
        $body = json_encode(['entry' => [['changes' => [['value' => [
            'metadata' => ['phone_number_id' => '123456789'], 'statuses' => [$status],
        ]]]]]]);
        return $this->call('POST', '/webhooks/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $validSignature ? 'sha256=' . hash_hmac('sha256', $body, 'TEST-SECRET') : 'sha256=incorrect',
        ], $body);
    }

    public function test_default_configuration_and_absence_of_opt_in_never_send_messages(): void
    {
        $this->makeTicket(true);
        $this->assertFalse(WhatsappSetting::current()->enabled);
        $this->assertDatabaseCount('whatsapp_deliveries', 0);
        $this->enable();
        $this->makeTicket(false);
        $this->assertDatabaseCount('whatsapp_deliveries', 0);
        Http::assertNothingSent();
    }

    public function test_issuance_enqueues_one_delivery_only_when_enabled_and_requested(): void
    {
        $this->enable();
        $this->fakeSuccess();
        $participation = $this->makeParticipation(true);
        app(TicketService::class)->issue($participation);
        app(TicketService::class)->issue($participation);
        $this->assertDatabaseCount('whatsapp_deliveries', 1);
        $this->assertSame('accepted', WhatsappDelivery::firstOrFail()->status);
        Http::assertSentCount(2);
    }

    public function test_pdf_is_uploaded_and_template_includes_document_and_private_link(): void
    {
        $delivery = $this->delivery();
        $this->fakeSuccess();
        app(WhatsappTicketService::class)->send($delivery->id);
        $this->assertSame('accepted', $delivery->fresh()->status);
        $this->assertNull($delivery->fresh()->delivered_at);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/media') && str_contains($request->body(), '%PDF-TEST'));
        Http::assertSent(function ($request) use ($delivery) {
            if (!str_ends_with($request->url(), '/messages')) return false;
            $data = $request->data();
            return $data['to'] === '2250700000001'
                && $data['template']['components'][0]['parameters'][0]['document']['id'] === 'MEDIA-123'
                && str_ends_with($data['template']['components'][0]['parameters'][0]['document']['filename'], '.pdf')
                && str_starts_with($data['template']['components'][1]['parameters'][3]['text'], 'https://rdsa.example.test/billets/')
                && $data['template']['components'][1]['parameters'][4]['text'] === $delivery->ticket->access_token;
        });
        app(WhatsappTicketService::class)->send($delivery->id);
        Http::assertSentCount(2);
    }

    public function test_timeout_does_not_trigger_a_blind_resend_or_invalidate_payment(): void
    {
        $delivery = $this->delivery();
        Http::fake([
            'graph.facebook.com/*/media' => Http::response(['id' => 'MEDIA-123']),
            'graph.facebook.com/*/messages' => fn () => throw new ConnectionException('timeout'),
        ]);
        app(WhatsappTicketService::class)->send($delivery->id);
        $this->assertSame('uncertain', $delivery->fresh()->status);
        app(WhatsappTicketService::class)->send($delivery->id);
        $this->assertSame(1, $delivery->fresh()->attempts);
        $this->assertSame('confirmed', $delivery->ticket->participation->status);
        $this->assertSame('valid', $delivery->ticket->status);
    }

    public function test_explicit_rejection_can_be_retried_from_admin(): void
    {
        $delivery = $this->delivery();
        Http::fake([
            'graph.facebook.com/*/media' => Http::sequence()->push([], 400)->push(['id' => 'MEDIA-123']),
            'graph.facebook.com/*/messages' => Http::response(['messages' => [['id' => 'wamid.TEST']]]),
        ]);
        app(WhatsappTicketService::class)->send($delivery->id);
        $this->assertSame('failed', $delivery->fresh()->status);
        $this->actingAs($this->makeAdmin())->post('/admin/tickets/' . $delivery->ticket_id . '/whatsapp')->assertRedirect();
        $this->assertSame('accepted', $delivery->fresh()->status);
        $this->assertSame(2, $delivery->fresh()->attempts);
        $this->post('/admin/tickets/' . $delivery->ticket_id . '/whatsapp')->assertSessionHasErrors('whatsapp');
    }

    public function test_webhook_checks_signature_and_never_downgrades_delivered_messages(): void
    {
        $delivery = $this->delivery();
        $delivery->update(['status' => 'accepted', 'provider_message_id' => 'wamid.TEST']);
        $this->webhook(['id' => 'wamid.TEST', 'status' => 'delivered', 'timestamp' => '100'], false)->assertForbidden();
        $this->assertSame('accepted', $delivery->fresh()->status);
        $this->webhook(['id' => 'wamid.TEST', 'status' => 'delivered', 'timestamp' => '100'])->assertOk();
        $this->assertSame('delivered', $delivery->fresh()->status);
        $this->assertNotNull($delivery->fresh()->delivered_at);
        $this->webhook(['id' => 'wamid.TEST', 'status' => 'sent', 'timestamp' => '101'])->assertOk();
        $this->assertSame('delivered', $delivery->fresh()->status);
        $this->webhook(['id' => 'wamid.TEST', 'status' => 'read', 'timestamp' => '102'])->assertOk();
        $this->assertSame('read', $delivery->fresh()->status);
    }

    public function test_settings_are_admin_only_and_secrets_are_not_redisplayed(): void
    {
        $settings = $this->enable();
        $stored = DB::table('whatsapp_settings')->first();
        $this->assertNotSame('TEST-TOKEN', $stored->access_token);
        $this->get('/admin/whatsapp')->assertRedirect('/admin/login');
        $this->actingAs($this->makeAdmin())->get('/admin/whatsapp')->assertOk()->assertDontSee('TEST-TOKEN')->assertDontSee('TEST-SECRET');
        $this->put('/admin/whatsapp', [
            'enabled' => 1, 'phone_number_id' => '123456789', 'api_version' => 'v24.0',
            'template_name' => 'rdsa_billet', 'template_language' => 'fr', 'public_base_url' => 'https://rdsa.example.test',
            'access_token' => '', 'app_secret' => '',
        ])->assertSessionHasNoErrors();
        $this->assertSame('TEST-TOKEN', $settings->fresh()->access_token);
        $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=' . $settings->verify_token . '&hub.challenge=12345')
            ->assertOk()->assertSee('12345');
    }

    public function test_cancelled_ticket_is_not_sent(): void
    {
        $delivery = $this->delivery();
        $delivery->ticket->update(['status' => 'cancelled']);
        app(WhatsappTicketService::class)->send($delivery->id);
        $this->assertSame('cancelled', $delivery->fresh()->status);
        Http::assertNothingSent();
    }
}
