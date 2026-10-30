<?php

namespace Tests\Feature;

use App\Models\WhatsappDelivery;
use App\Models\WhatsappSetting;
use App\Services\TicketDocumentService;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTicketRecords;
use Tests\TestCase;

class WhatsappAfterCommitTest extends TestCase
{
    use CreatesTicketRecords;

    protected function setUp(): void
    {
        parent::setUp();
        DB::purge();
        $this->artisan('migrate:fresh', ['--force' => true]);
        Http::preventStrayRequests();
        Http::fake([
            'graph.facebook.com/*/media' => Http::response(['id' => 'MEDIA']),
            'graph.facebook.com/*/messages' => Http::response(['messages' => [['id' => 'wamid.AUTO']]]),
        ]);
        $this->mock(TicketDocumentService::class, fn ($mock) => $mock->shouldReceive('pdf')->andReturn('%PDF-TEST'));
        WhatsappSetting::current()->update([
            'enabled' => true, 'phone_number_id' => '123456789', 'access_token' => 'TEST',
            'app_secret' => 'TEST', 'template_name' => 'rdsa_billet', 'public_base_url' => 'https://rdsa.example.test',
        ]);
    }

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_automatic_send_occurs_only_after_successful_database_commit(): void
    {
        $participation = $this->makeParticipation(true);
        DB::transaction(function () use ($participation) {
            app(TicketService::class)->issue($participation);
            Http::assertNothingSent();
        });
        $delivery = WhatsappDelivery::firstOrFail();
        $this->assertSame('accepted', $delivery->status, (string) $delivery->last_error);
        Http::assertSentCount(2);
    }

    public function test_rollback_discards_both_delivery_and_after_commit_send(): void
    {
        $participation = $this->makeParticipation(true);
        try {
            DB::transaction(function () use ($participation) {
                app(TicketService::class)->issue($participation);
                throw new \RuntimeException('Annulation de test');
            });
        } catch (\RuntimeException $exception) {
            $this->assertSame('Annulation de test', $exception->getMessage());
        }
        Http::assertNothingSent();
        $this->assertDatabaseCount('tickets', 0);
        $this->assertDatabaseCount('whatsapp_deliveries', 0);
    }
}
