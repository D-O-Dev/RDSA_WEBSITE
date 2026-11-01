<?php
namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Edition;
use App\Models\SiteUpdate;
use App\Models\User;
use App\Services\CheckInService;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketRecords;
use Tests\TestCase;

class ExperienceUpgradeTest extends TestCase
{
    use RefreshDatabase, CreatesTicketRecords;

    public function test_notification_feed_is_admin_only_and_returns_new_reservations_with_live_pending_totals(): void
    {
        $this->getJson('/admin/notifications')->assertUnauthorized();
        $first = $this->makeParticipation(); $first->update(['status' => 'pending']);
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->getJson('/admin/notifications')->assertOk()->assertJsonPath('pending', 1)->assertJsonCount(0, 'items')->assertJsonPath('latest', $first->id);
        $second = $this->makeParticipation(); $second->update(['status' => 'pending']);
        $this->getJson('/admin/notifications?after=' . $first->id)->assertOk()->assertJsonCount(1, 'items')->assertJsonPath('items.0.id', $second->id)->assertJsonPath('pending', 2);
        $this->getJson('/admin/notifications?after=-1')->assertUnprocessable();
        $controller = User::factory()->create(); $controller->forceFill(['team_role' => 'controller'])->save();
        $this->actingAs($controller)->getJson('/admin/notifications')->assertForbidden();
    }

    public function test_public_alert_feed_never_exposes_hidden_expired_faq_or_other_edition_content(): void
    {
        $admin = $this->makeAdmin(); $edition = $this->makeParticipation()->edition;
        $values = ['title' => 'Annonce publique', 'body' => 'Portes ouvertes', 'kind' => 'announcement', 'sort_order' => 0, 'is_published' => true, 'created_by' => $admin->id];
        $notice = SiteUpdate::forceCreate($values);
        SiteUpdate::forceCreate(array_replace($values, ['title' => 'Masqué', 'is_published' => false]));
        SiteUpdate::forceCreate(array_replace($values, ['title' => 'Expiré', 'ends_at' => now()->subDay()]));
        SiteUpdate::forceCreate(array_replace($values, ['title' => 'FAQ privée au flux', 'kind' => 'faq']));
        $other = Edition::create(['number' => 9, 'name' => 'Autre', 'slug' => 'autre', 'theme' => 'Test', 'venue' => 'Salle', 'starts_at' => now(), 'is_active' => false, 'is_published' => true]);
        SiteUpdate::forceCreate(array_replace($values, ['title' => 'Autre édition', 'edition_id' => $other->id]));
        $this->getJson('/informations')->assertOk()->assertJsonCount(1, 'items')->assertJsonPath('items.0.id', $notice->id)->assertJsonMissingPath('items.0.created_by');
        $this->get('/')->assertSee('data-announcements-url', false);
    }

    public function test_hero_settings_are_saved_per_edition_and_rendered_without_injectable_styles(): void
    {
        $edition = $this->makeParticipation()->edition;
        $data = $edition->only(['name', 'number', 'theme', 'venue', 'is_active', 'is_published', 'is_archived']);
        $data['starts_at'] = $edition->starts_at->format('Y-m-d\TH:i');
        $data['hero_settings'] = ['mode' => 'zoom', 'position' => 'center', 'font' => 'classic', 'accent' => '#aabbcc', 'interval' => 8, 'overlay' => 60, 'slides' => ['https://cdn.example.org/second.jpg', '']];
        $this->actingAs($this->makeAdmin())->put('/admin/editions/' . $edition->id, $data)->assertSessionHasNoErrors();
        $this->assertSame(['https://cdn.example.org/second.jpg'], $edition->fresh()->hero_settings['slides']);
        $this->get('/')->assertSee('hero-position-center')->assertSee('data-hero-mode="zoom"', false)->assertSee('https://cdn.example.org/second.jpg', false)->assertSee('data-hero-pause', false);
        foreach ([['accent' => 'red;display:none'], ['position' => 'evil'], ['mode' => 'script'], ['interval' => 0], ['slides' => ['javascript:alert(1)']]] as $bad) {
            $invalid = $data; $invalid['hero_settings'] = array_replace($data['hero_settings'], $bad);
            $this->put('/admin/editions/' . $edition->id, $invalid)->assertSessionHasErrors();
            $this->assertSame('#aabbcc', $edition->fresh()->hero_settings['accent']);
        }
    }

    public function test_private_qr_download_requires_approval_and_stops_after_entry_or_cancellation(): void
    {
        $ticket = $this->makeTicket(); $service = app(TicketService::class); $url = $service->publicUrl($ticket);
        $response = $this->get($url . '?qr=1')->assertOk()->assertHeader('Content-Type', 'image/svg+xml')->assertDownload($ticket->reference . '-qr.svg');
        $this->assertStringContainsString('<svg', $response->getContent());
        $this->get($url)->assertSee('data-qr-download', false)->assertSee('Partager sur WhatsApp');
        $admin = $this->makeAdmin();
        app(CheckInService::class)->confirm($ticket->reference, $ticket->participation->edition_id, $admin);
        $this->get($url . '?qr=1')->assertStatus(409);
        $second = $this->makeTicket(); $second->participation->update(['status' => 'cancelled']);
        $this->get($service->publicUrl($second) . '?qr=1')->assertStatus(409);
        $this->get(route('tickets.show', ['reference' => $second->reference, 'token' => str_repeat('A', 32)]) . '?qr=1')->assertNotFound();
    }
}
