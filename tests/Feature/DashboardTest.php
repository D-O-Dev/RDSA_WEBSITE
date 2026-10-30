<?php

namespace Tests\Feature;

use App\Models\Edition;
use App\Services\CheckInService;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketRecords;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase, CreatesTicketRecords;

    public function test_dashboard_uses_real_reservations_tickets_and_entries(): void
    {
        $admin = $this->makeAdmin();
        $paid = $this->makeParticipation();
        $pending = $this->makeParticipation();
        $pending->update(['status' => 'pending', 'extra_contribution' => 1000, 'total_expected' => 2000]);
        $ticket = app(TicketService::class)->issue($paid);
        app(CheckInService::class)->confirm($ticket->reference, $paid->edition_id, $admin);
        $response = $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->assertSee('Tableau de bord');
        $metrics = collect($response->viewData('metrics'))->mapWithKeys(fn ($item) => [$item[1] => $item[2]]);
        $this->assertSame(2, $metrics['Réservations']);
        $this->assertSame(1, $metrics['Billets émis']);
        $this->assertSame(1, $metrics['Entrées enregistrées']);
    }

    public function test_dashboard_filter_and_guest_protection(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/admin/login');
        $this->makeParticipation();
        $other = Edition::create([
            'name' => 'Autre édition', 'number' => 2, 'slug' => 'autre', 'theme' => 'Autre thème',
            'starts_at' => '2027-06-01 08:00:00', 'venue' => 'Autre lieu', 'base_price' => 1000,
        ]);
        $response = $this->actingAs($this->makeAdmin())->get('/admin/dashboard?edition=' . $other->id)->assertOk();
        $this->assertEquals(0, $response->viewData('metrics')[0][2]);
    }
}
