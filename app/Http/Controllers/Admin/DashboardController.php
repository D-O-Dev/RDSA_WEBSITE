<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Edition;
use App\Models\Participation;
use App\Models\Ticket;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['edition' => ['nullable', 'integer', 'exists:editions,id']]);
        $id = $filters['edition'] ?? null;
        $reservations = Participation::when($id, fn ($q, $id) => $q->where('edition_id', $id));
        $tickets = Ticket::when($id, fn ($q, $id) => $q->whereHas('participation', fn ($p) => $p->where('edition_id', $id)));
        $entries = Attendance::when($id, fn ($q, $id) => $q->whereHas('ticket.participation', fn ($p) => $p->where('edition_id', $id)));
        return view('admin.dashboard', [
            'editions' => Edition::orderByDesc('starts_at')->get(),
            'activeEdition' => Edition::where('is_active', true)->where('is_published', true)->where('is_archived', false)->first(),
            'metrics' => [
                ['people', 'Réservations', (clone $reservations)->count()],
                ['hourglass-split', 'À vérifier', (clone $reservations)->where('status', 'pending')->count()],
                ['check-circle', 'Confirmées', (clone $reservations)->where('status', 'confirmed')->count()],
                ['ticket-perforated', 'Billets émis', (clone $tickets)->count()],
                ['door-open', 'Entrées enregistrées', (clone $entries)->count()],
            ],
            'recentParticipations' => (clone $reservations)->with(['participant', 'edition', 'tickets'])->latest('id')->limit(8)->get(),
        ]);
    }
}
