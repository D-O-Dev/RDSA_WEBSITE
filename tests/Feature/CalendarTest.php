<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\Edition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketRecords;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase, CreatesTicketRecords;

    private function edition(array $changes = []): Edition
    {
        return Edition::create(array_replace([
            'number' => 1, 'name' => 'Édition du calendrier', 'slug' => 'edition-calendrier',
            'theme' => 'Prière', 'starts_at' => '2028-02-29 18:30:00', 'venue' => 'Salle de rencontre',
            'is_published' => false, 'is_active' => false, 'is_archived' => false,
        ], $changes));
    }

    private function event(User $creator, array $changes = []): CalendarEvent
    {
        $event = new CalendarEvent(array_replace(['title' => 'Préparation équipe', 'starts_at' => '2028-02-20 09:00:00', 'notes' => 'Informations privées'], $changes));
        $event->created_by = $creator->id; $event->save();
        return $event;
    }

    public function test_calendar_and_writes_require_an_active_administrator(): void
    {
        $this->get('/admin/calendar')->assertRedirect('/admin/login');
        $this->post('/admin/calendar/events', [])->assertRedirect('/admin/login');
        $admin = $this->makeAdmin(); $event = $this->event($admin);
        $controller = User::factory()->create(); $controller->forceFill(['team_role' => 'controller'])->save();
        $this->actingAs($controller)->get('/admin/calendar')->assertForbidden();
        $this->post('/admin/calendar/events', [])->assertForbidden();
        $this->put('/admin/calendar/events/' . $event->id, [])->assertForbidden();
        $this->delete('/admin/calendar/events/' . $event->id)->assertForbidden();
        $this->actingAs($admin)->get('/admin/calendar')->assertOk();
    }

    public function test_real_editions_and_private_events_are_filtered_by_month_and_time(): void
    {
        $admin = $this->makeAdmin(); $this->edition(); $this->edition(['number' => 2, 'slug' => 'outside', 'name' => 'Hors du mois', 'starts_at' => '2028-03-01 00:00:00']);
        $this->event($admin, ['starts_at' => '2028-02-29 08:00:00']);
        $this->event($admin, ['title' => 'Événement hors du mois', 'starts_at' => '2028-01-31 23:59:00']);
        $response = $this->actingAs($admin)->get('/admin/calendar?month=2028-02')->assertOk()
            ->assertSee('Édition du calendrier')->assertSee('Préparation équipe')->assertSee('Brouillon')
            ->assertDontSee('Hors du mois')->assertDontSee('Événement hors du mois');
        $days = $response->viewData('days');
        $this->assertCount(42, $days);
        $this->assertSame(1, $days->first()->dayOfWeekIso);
        $this->assertCount(29, $days->filter(fn ($day) => $day->format('Y-m') === '2028-02'));
        $this->assertSame(['Préparation équipe', 'Édition du calendrier'], $response->viewData('agenda')['2028-02-29']->pluck('title')->all());
        $this->get('/')->assertDontSee('Informations privées');
    }

    public function test_create_update_and_remove_event_preserve_editions_and_creator(): void
    {
        $admin = $this->makeAdmin(); $edition = $this->edition();
        $this->actingAs($admin)->post('/admin/calendar/events', ['title' => 'Réunion de préparation', 'starts_at' => '2028-02-10T09:30', 'location' => 'Salle A', 'notes' => 'Préparer l’accueil', 'created_by' => 999])
            ->assertSessionHasNoErrors()->assertRedirect('/admin/calendar?month=2028-02');
        $event = CalendarEvent::firstOrFail();
        $this->assertSame($admin->id, $event->created_by);
        $this->assertSame('2028-02-10 09:30', $event->starts_at->format('Y-m-d H:i'));
        $this->put('/admin/calendar/events/' . $event->id, ['title' => 'Réunion déplacée', 'starts_at' => '2028-03-12T10:00', 'created_by' => 999])->assertRedirect('/admin/calendar?month=2028-03');
        $this->assertSame($admin->id, $event->fresh()->created_by);
        $this->get('/admin/calendar?month=2028-02')->assertDontSee('Réunion déplacée');
        $this->get('/admin/calendar?month=2028-03')->assertSee('Réunion déplacée');
        $this->delete('/admin/calendar/events/' . $event->id)->assertRedirect('/admin/calendar?month=2028-03');
        $this->assertSoftDeleted($event);
        $this->get('/admin/calendar?month=2028-03')->assertDontSee('Réunion déplacée');
        $this->get('/admin/calendar?edit=' . $event->id)->assertNotFound();
        $this->assertSame($edition->starts_at->format('Y-m-d H:i'), $edition->fresh()->starts_at->format('Y-m-d H:i'));
        $this->assertDatabaseCount('participations', 0);
    }

    public function test_calendar_follows_edition_date_and_archive_changes_without_duplicate_events(): void
    {
        $edition = $this->edition();
        $this->actingAs($this->makeAdmin())->get('/admin/calendar?month=2028-02')->assertSee('Brouillon');
        $edition->update(['starts_at' => '2028-03-02 08:00:00', 'is_archived' => true]);
        $this->get('/admin/calendar?month=2028-02')->assertDontSee('Édition du calendrier');
        $this->get('/admin/calendar?month=2028-03')->assertSee('Édition du calendrier')->assertSee('Archivée');
        $this->assertDatabaseCount('calendar_events', 0);
    }

    public function test_editor_uses_selected_date_and_month_navigation_wraps_across_years(): void
    {
        $this->actingAs($this->makeAdmin());
        $response = $this->get('/admin/calendar?month=2028-12&date=2028-12-31&create=1')->assertOk();
        $this->assertSame('2028-12-31T09:00', $response->viewData('editing')->starts_at->format('Y-m-d\TH:i'));
        $response->assertSee('month=2029-01', false)->assertSee('month=2028-11', false);
        $this->get('/admin/calendar?month=2028-02')->assertSee('Le mois est encore libre');
    }

    public function test_invalid_values_are_rejected_and_user_text_is_escaped(): void
    {
        $this->actingAs($admin = $this->makeAdmin());
        foreach (['2028-13', '2028-2', '1899-12', '2200-01', 'incorrect'] as $month) $this->getJson('/admin/calendar?month=' . $month)->assertUnprocessable();
        $this->post('/admin/calendar/events', ['title' => 'Réunion', 'starts_at' => '2028-02-30T08:00'])->assertSessionHasErrors('starts_at');
        $this->post('/admin/calendar/events', ['title' => '', 'starts_at' => '2028-02-20T08:00'])->assertSessionHasErrors('title');
        $this->assertDatabaseCount('calendar_events', 0);
        $this->event($admin, ['title' => '<script>alert(1)</script>']);
        $this->get('/admin/calendar?month=2028-02')->assertOk()->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false);
    }
}
