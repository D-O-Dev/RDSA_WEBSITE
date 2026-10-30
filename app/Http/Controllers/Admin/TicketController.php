<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\Participation;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(['valid', 'used', 'cancelled', 'pending'])],
            'edition' => ['nullable', 'integer', 'exists:editions,id'],
        ]);
        $tickets = Ticket::with(['participation.participant', 'participation.edition'])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['edition'] ?? null, fn ($q, $id) => $q->whereHas('participation', fn ($p) => $p->where('edition_id', $id)))
            ->when($filters['q'] ?? null, fn ($q, $search) => $q->where(fn ($t) => $t->where('reference', 'like', '%' . $search . '%')
                ->orWhereHas('participation', fn ($p) => $p->where('reference', 'like', '%' . $search . '%')
                    ->orWhereHas('participant', fn ($person) => $person->where('full_name', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%')))))
            ->latest('id')->paginate(20)->withQueryString();
        return view('admin.tickets.index', ['tickets' => $tickets, 'editions' => Edition::orderByDesc('starts_at')->get()]);
    }

    public function show(Ticket $ticket, TicketService $service)
    {
        $ticket->load(['participation.participant', 'participation.edition', 'whatsappDelivery']);
        $available = $ticket->access_token && $ticket->qr_token
            && $ticket->status === Ticket::STATUS_VALID
            && $ticket->participation->status === Participation::STATUS_CONFIRMED;
        return response()->view('admin.tickets.show', [
            'ticket' => $ticket, 'available' => $available,
            'whatsappEnabled' => \App\Models\WhatsappSetting::current()->enabled,
            'publicUrl' => $ticket->access_token && $ticket->access_token_hash ? $service->publicUrl($ticket) : null,
            'whatsappUrl' => $available ? $service->whatsappUrl($ticket) : null,
        ])->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function issue(Participation $participation, TicketService $service)
    {
        $ticket = $service->issue($participation);
        return redirect()->route('admin.tickets.show', $ticket)->with('status', 'Billet disponible.');
    }
}
