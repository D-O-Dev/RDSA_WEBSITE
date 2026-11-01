<?php

namespace Tests\Feature;

use App\Models\AdminAction;
use App\Models\MediaItem;
use App\Models\Participant;
use App\Models\Participation;
use App\Models\SiteContent;
use App\Models\WhatsappDelivery;
use App\Services\CheckInService;
use App\Services\TicketService;
use App\Support\ImageSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTicketRecords;
use Tests\TestCase;

class ExternalImagesAndDeletionTest extends TestCase
{
    use RefreshDatabase, CreatesTicketRecords;

    public function test_direct_images_accept_durable_https_urls_without_a_server_request(): void
    {
        Http::preventStrayRequests();
        $admin = $this->makeAdmin(); $this->actingAs($admin);
        $data = collect(SiteContent::fields())->map(fn ($f) => $f['default'])->all();
        $data['logo_path'] = 'https://cdn.example.org/rdsa/logo.png';
        $data['admin_background_path'] = 'https://cdn.example.org/rdsa/background.jpg';
        $this->put('/admin/content', $data)->assertSessionHasNoErrors();
        $this->get('/')->assertSee($data['logo_path'], false);
        $this->withHeaders(['X-RDSA-Navigation' => '1'])->get('/admin/content')->assertSee($data['admin_background_path'], false)->assertSee('data-image-address', false);
        $edition = $this->makeParticipation()->edition;
        $payload = $edition->only(['name', 'number', 'theme', 'venue', 'is_active', 'is_published', 'is_archived']);
        $payload['starts_at'] = $edition->starts_at->format('Y-m-d\TH:i');
        $payload['hero_background_path'] = 'https://cdn.example.org/future.jpg?version=2';
        $this->put('/admin/editions/' . $edition->id, $payload)->assertSessionHasNoErrors();
        $this->get('/')->assertSee('https://cdn.example.org/future.jpg?version=2', false);
        Http::assertNothingSent();
    }

    public function test_malformed_private_and_post_links_are_rejected_as_image_sources(): void
    {
        foreach (['http://cdn.example.org/img.jpg', 'javascript:alert(1)', '//cdn.example.org/img.jpg', 'https://127.0.0.1/a.jpg', 'https://10.0.0.1/a.jpg', 'https://foo.local/a.jpg', 'https://localhost/a.jpg', 'https://user:secret@cdn.example.org/a.jpg', 'https://cdn.example.org:8080/a.jpg', 'https://facebook.com/posts/123', 'https://cdn.example.org/x.svg', 'https://cdn.example.org/x.jpg" style="x', '../.env'] as $url) {
            $this->assertFalse(ImageSource::valid($url), $url);
        }
        $this->assertTrue(ImageSource::valid('https://cdn.example.org/images/rdsa?width=1200&format=webp'));
    }

    public function test_photos_and_video_covers_can_be_hosted_outside_the_application(): void
    {
        $this->actingAs($this->makeAdmin());
        $data = ['title' => 'Photo distante', 'kind' => 'photo', 'album_name' => 'RDSA', 'sort_order' => 0, 'is_published' => true,
            'image_url' => 'https://cdn.example.org/photo.jpg'];
        $this->post('/admin/media', $data)->assertSessionHasNoErrors();
        $photo = MediaItem::firstOrFail();
        $this->assertSame('external', $photo->disk);
        $this->assertSame($data['image_url'], $photo->publicData()['source']);
        $this->assertSame($data['image_url'], $this->get('/')->viewData('mediaItems')->first()['thumbnail']);
        $this->post('/admin/media', array_replace($data, ['kind' => 'video', 'image_url' => '', 'video_url' => 'https://youtu.be/AbCdEfGhI12', 'cover_path' => 'https://cdn.example.org/cover.webp']))->assertSessionHasNoErrors();
        $this->assertSame('https://cdn.example.org/cover.webp', MediaItem::latest('id')->firstOrFail()->publicData()['thumbnail']);
        $this->post('/admin/media', array_replace($data, ['image_url' => 'javascript:alert(1)']))->assertSessionHasErrors('image_url');
    }

    private function credentials(Participation $record): array
    {
        return ['current_password' => 'Qa-Password-123!', 'confirmation_reference' => $record->reference];
    }

    public function test_permanent_removal_requires_admin_password_exact_reference_and_prior_cancellation(): void
    {
        $admin = $this->makeAdmin(); $admin->password = Hash::make('Qa-Password-123!'); $admin->save();
        $ticket = $this->makeTicket(); $record = $ticket->participation; $url = app(TicketService::class)->publicUrl($ticket);
        $this->actingAs($admin)->delete('/admin/participations/' . $record->id, $this->credentials($record))->assertSessionHasErrors('deletion');
        $this->post('/admin/participations/' . $record->id . '/cancel', ['reason' => 'Test'])->assertRedirect();
        $this->get('/admin/participations?delete=' . $record->id)->assertOk()->assertSee('reservation-delete-title', false)->assertSee('name="current_password"', false);
        $this->delete('/admin/participations/' . $record->id, array_replace($this->credentials($record), ['current_password' => 'incorrect']))->assertSessionHasErrors('current_password');
        $this->delete('/admin/participations/' . $record->id, array_replace($this->credentials($record), ['confirmation_reference' => 'incorrect']))->assertSessionHasErrors('confirmation_reference');
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);
        WhatsappDelivery::create(['ticket_id' => $ticket->id, 'status' => 'cancelled']);
        $this->delete('/admin/participations/' . $record->id, $this->credentials($record))->assertSessionHasNoErrors()->assertRedirect('/admin/participations');
        $this->assertDatabaseMissing('participations', ['id' => $record->id]);
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
        $this->assertDatabaseMissing('participants', ['id' => $record->participant_id]);
        $this->assertDatabaseCount('whatsapp_deliveries', 0);
        $this->get($url)->assertNotFound();
        $this->assertDatabaseHas('admin_actions', ['action' => 'admin.participations.destroy', 'target' => 'Réservation #' . $record->id]);
    }

    public function test_used_ticket_or_uncertain_delivery_cannot_be_purged_and_shared_person_is_preserved(): void
    {
        $admin = $this->makeAdmin(); $admin->password = Hash::make('Qa-Password-123!'); $admin->save();
        $ticket = $this->makeTicket(); $record = $ticket->participation;
        app(CheckInService::class)->confirm($ticket->reference, $record->edition_id, $admin);
        $record->update(['status' => 'cancelled']);
        $this->actingAs($admin)->delete('/admin/participations/' . $record->id, $this->credentials($record))->assertSessionHasErrors('deletion');
        $this->assertDatabaseCount('attendances', 1);
        $other = $this->makeTicket(); $otherRecord = $other->participation; $otherRecord->update(['status' => 'cancelled']);
        $delivery = WhatsappDelivery::create(['ticket_id' => $other->id, 'status' => 'uncertain']);
        $this->delete('/admin/participations/' . $otherRecord->id, $this->credentials($otherRecord))->assertSessionHasErrors('deletion');
        $delivery->update(['status' => 'cancelled']);
        $shared = $this->makeParticipation(); $shared->update(['participant_id' => $otherRecord->participant_id]);
        $this->delete('/admin/participations/' . $otherRecord->id, $this->credentials($otherRecord))->assertSessionHasNoErrors();
        $this->assertNotNull(Participant::find($shared->participant_id));
    }
    public function test_unauthenticated_and_controller_accounts_cannot_permanently_delete(): void
    {
        $record = $this->makeParticipation(); $record->update(['status' => 'cancelled']);
        $this->delete('/admin/participations/' . $record->id, $this->credentials($record))->assertRedirect('/admin/login');
        $controller = \App\Models\User::factory()->create(); $controller->forceFill(['team_role' => 'controller'])->save();
        $this->actingAs($controller)->delete('/admin/participations/' . $record->id, $this->credentials($record))->assertForbidden();
        $this->assertDatabaseHas('participations', ['id' => $record->id]);
        $this->assertDatabaseCount('admin_actions', 0);
    }

    public function test_deployment_check_refuses_a_development_environment_without_mutation(): void
    {
        $this->makeAdmin();
        config(['app.env' => 'testing', 'app.debug' => true, 'app.url' => 'http://localhost', 'session.secure' => false]);
        $this->artisan('rdsa:check-deployment')->expectsOutputToContain('[À CORRIGER] Environnement production')->assertExitCode(1);
        $this->assertDatabaseCount('admin_actions', 0);
        $this->assertDatabaseCount('users', 1);
    }

}
