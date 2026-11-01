<?php

namespace Tests\Feature;

use App\Models\AdminAction;
use App\Models\Attendance;
use App\Models\Edition;
use App\Models\MediaItem;
use App\Models\Participation;
use App\Models\SiteUpdate;
use App\Models\User;
use App\Services\CheckInService;
use App\Services\TicketService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketRecords;
use Tests\TestCase;

class AdminCompletionTest extends TestCase
{
    use RefreshDatabase, CreatesTicketRecords;

    public function test_statistics_and_audit_and_retirements_are_restricted_server_side(): void
    {
        $ticket = $this->makeTicket();
        $this->get('/admin/statistics')->assertRedirect('/admin/login');
        $controller = User::factory()->create(['name' => 'Contrôleur']);
        $controller->forceFill(['team_role' => 'controller'])->save();
        $this->actingAs($controller)->get('/admin/statistics')->assertForbidden();
        $this->get('/admin/statistics/export')->assertForbidden();
        $this->get('/admin/audit')->assertForbidden();
        $this->post('/admin/participations/' . $ticket->participation_id . '/cancel', ['reason' => 'Test'])->assertForbidden();
        $this->assertDatabaseCount('admin_actions', 0);
    }

    public function test_statistics_use_actual_statuses_and_edition_and_date_boundaries(): void
    {
        $this->travelTo(Carbon::parse('2026-10-30 14:00:00'));
        $admin = $this->makeAdmin(); $ticket = $this->makeTicket();
        $old = $this->makeParticipation(); $old->update(['status' => 'pending']);
        // created_at is not fillable: set it explicitly for the reporting fixture.
        $old->forceFill(['created_at' => now()->subDays(31)])->save();
        app(CheckInService::class)->confirm($ticket->reference, $ticket->participation->edition_id, $admin);
        $other = Edition::create(['number' => 2, 'name' => 'Autre', 'slug' => 'autre', 'theme' => 'Test', 'venue' => 'Salle', 'starts_at' => now()]);
        $extra = $this->makeParticipation(); $extra->update(['edition_id' => $other->id, 'status' => 'rejected']);
        $response = $this->actingAs($admin)->get('/admin/statistics?edition=' . $ticket->participation->edition_id)->assertOk();
        $this->assertSame(2, $response->viewData('total'));
        $this->assertSame(1, $response->viewData('ticketTotal'));
        $this->assertSame(1, $response->viewData('entered'));
        $this->assertEquals(100, $response->viewData('entryRate'));
        $days = $response->viewData('days'); $this->assertCount(30, $days);
        $this->assertSame('2026-10-01', $days[0]['date']);
        $this->assertSame(['date' => '2026-10-30', 'reservations' => 1, 'entries' => 1], $days[29]);
        $this->assertSame(1, array_sum(array_column($days, 'reservations')));
        $this->get('/admin/statistics?edition=' . $other->id)->assertViewHas('entryRate', 0);
        $this->get('/admin/statistics?edition=999')->assertSessionHasErrors('edition');
        $this->get('/admin/statistics/export?edition=' . $other->id)->assertOk()->assertDownload('rdsa-statistiques-30-jours.csv')
            ->assertStreamedContent("\xEF\xBB\xBFDate;\"Demandes reçues\";\"Entrées enregistrées\"\n" . implode('', array_map(fn ($day) => $day['date'] . ';' . ($day['date'] === '2026-10-30' ? '1' : '0') . ";0\n", $days)));
    }

    public function test_media_removal_preserves_shared_original_and_removes_public_visibility(): void
    {
        $admin = $this->makeAdmin();
        $item = MediaItem::create(['title' => 'Photo à retirer', 'kind' => 'photo', 'album_name' => 'RDSA', 'sort_order' => 0,
            'is_published' => true, 'disk' => 'assets', 'file_path' => 'images/hero/rdsa3-banner.jpeg']);
        $this->assertTrue($this->get('/')->viewData('mediaItems')->contains('id', $item->id));
        $this->actingAs($admin)->get('/admin/media?edit=' . $item->id)->assertSee('Confirmer la suppression');
        $this->delete('/admin/media/' . $item->id)->assertRedirect('/admin/media');
        $this->assertSoftDeleted($item); $this->assertFileExists(public_path('images/hero/rdsa3-banner.jpeg'));
        $this->assertFalse($this->get('/')->viewData('mediaItems')->contains('id', $item->id));
        $this->get('/admin/media?edit=' . $item->id)->assertNotFound();
        $this->assertDatabaseHas('admin_actions', ['action' => 'admin.media.destroy', 'actor_id' => $admin->id, 'target' => 'Média #' . $item->id]);
    }

    public function test_cancelling_a_reservation_invalidates_private_ticket_pdf_and_scan(): void
    {
        $admin = $this->makeAdmin(); $ticket = $this->makeTicket();
        $url = app(TicketService::class)->publicUrl($ticket);
        $this->actingAs($admin)->post('/admin/participations/' . $ticket->participation_id . '/cancel', ['reason' => 'Indisponibilité'])->assertSessionHasNoErrors()->assertRedirect('/admin/participations');
        $this->assertSame('cancelled', $ticket->fresh()->status);
        $this->assertSame('cancelled', $ticket->participation->fresh()->status);
        $this->get($url)->assertOk()->assertSee('Billet indisponible')->assertViewHas('qrDataUri', null)->assertViewHas('usable', false);
        $this->get($url . '?download=1')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->postJson('/admin/check-in/confirm', ['code' => $ticket->reference, 'edition_id' => $ticket->participation->edition_id])->assertStatus(422)->assertJsonPath('ok', false);
        $this->assertDatabaseCount('attendances', 0);
        $this->assertDatabaseCount('admin_actions', 1);
        $this->assertDatabaseHas('admin_actions', ['action' => 'admin.participations.cancel']);
    }

    public function test_cancellation_requires_reason_and_never_erases_a_recorded_entry(): void
    {
        $admin = $this->makeAdmin(); $ticket = $this->makeTicket();
        $this->actingAs($admin)->post('/admin/participations/' . $ticket->participation_id . '/cancel', ['reason' => ''])->assertSessionHasErrors('reason');
        $this->assertDatabaseCount('admin_actions', 0);
        $this->postJson('/admin/check-in/confirm', ['code' => $ticket->reference, 'edition_id' => $ticket->participation->edition_id])->assertOk();
        $this->assertDatabaseCount('admin_actions', 1);
        $this->post('/admin/participations/' . $ticket->participation_id . '/cancel', ['reason' => 'Tentative après entrée'])->assertSessionHasErrors('cancellation');
        $this->assertSame('used', $ticket->fresh()->status); $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseCount('admin_actions', 1);
    }

    public function test_audit_logs_only_successful_writes_without_secrets_and_can_be_filtered(): void
    {
        $admin = $this->makeAdmin(); $this->actingAs($admin);
        $data = ['kind' => 'announcement', 'title' => 'Annonce test', 'body' => 'Texte privé à ne pas journaliser', 'is_published' => true, 'sort_order' => 0];
        $this->post('/admin/updates', $data + ['password' => 'ne-jamais-copier', 'code' => 'QR-PRIVE'])->assertSessionHasNoErrors();
        $this->post('/admin/updates', array_replace($data, ['title' => '']))->assertSessionHasErrors('title');
        $this->assertDatabaseCount('admin_actions', 1);
        // Une erreur précédente ne doit pas empêcher la trace d'une prochaine action réussie.
        $item = SiteUpdate::firstOrFail();
        $this->patch('/admin/updates/' . $item->id . '/publication', ['is_published' => false])->assertRedirect();
        $this->assertDatabaseCount('admin_actions', 2);
        $record = AdminAction::firstOrFail();
        $this->assertSame($admin->name, $record->actor_name);
        $this->assertSame('Annonce / FAQ #' . $item->id, $record->target);
        $all = AdminAction::all()->toJson();
        foreach (['ne-jamais-copier', 'QR-PRIVE', 'Texte privé'] as $secret) $this->assertStringNotContainsString($secret, $all);
        $this->get('/admin/audit?action=admin.updates.publish&actor=' . $admin->id)->assertOk()->assertViewHas('actions', fn ($items) => $items->total() === 1);
        $this->get('/admin/audit?to=' . now()->toDateString())->assertSessionHasNoErrors();
        $this->get('/admin/audit?from=2026-12-31&to=2026-01-01')->assertSessionHasErrors('to');
        $this->withHeaders(['X-RDSA-Navigation' => '1'])->get('/admin/statistics')->assertOk()->assertDontSee('id="bgimg"', false);
        $this->delete('/admin/audit/' . $record->id)->assertStatus(404);
    }
    public function test_audit_failure_rolls_back_the_administrative_database_change(): void
    {
        $admin = $this->makeAdmin(); $participation = $this->makeParticipation();
        $participation->update(['status' => 'pending']);
        AdminAction::creating(function () { throw new \RuntimeException('Journal indisponible'); });
        try {
            $this->withoutExceptionHandling()->actingAs($admin)->post('/admin/participations/' . $participation->id . '/review', ['decision' => 'approve']);
            $this->fail('La panne du journal devait interrompre la validation.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Journal indisponible', $error->getMessage());
        } finally {
            AdminAction::flushEventListeners();
        }
        $this->assertSame('pending', $participation->fresh()->status);
        $this->assertDatabaseCount('tickets', 0); $this->assertDatabaseCount('admin_actions', 0);
    }

}
