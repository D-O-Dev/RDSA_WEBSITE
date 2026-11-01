<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\Participation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ParticipationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'delete' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in(['pending', 'confirmed', 'rejected', 'cancelled'])],
            'edition' => ['nullable', 'integer', 'exists:editions,id'],
        ]);
        $participations = Participation::with(['participant', 'edition', 'tickets'])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['edition'] ?? null, fn ($q, $id) => $q->where('edition_id', $id))
            ->when($filters['q'] ?? null, fn ($q, $search) => $q->where(fn ($p) => $p
                ->where('reference', 'like', '%' . $search . '%')
                ->orWhereHas('participant', fn ($person) => $person->where('full_name', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%'))))
            ->latest('id')->paginate(20)->withQueryString();
        $deleting = $request->filled('delete') ? Participation::with('participant')->findOrFail($request->integer('delete')) : null;
        return view('admin.participations', [
            'deleting' => $deleting,
            'participations' => $participations, 'editions' => Edition::orderByDesc('starts_at')->get(),
        ]);
    }
    public function review(Request $request, Participation $participation, \App\Services\ReservationReviewService $service)
    {
        $data = $request->validate(['decision' => ['required', Rule::in(['approve', 'reject'])], 'reason' => ['nullable', 'required_if:decision,reject', 'string', 'max:1000']]);
        $service->review($participation, $request->user(), $data['decision'], $data['reason'] ?? null);
        return redirect()->route('admin.participations.index')->with('status', $data['decision'] === 'approve' ? 'Demande validée et billet émis.' : 'Demande refusée.');
    }
    public function cancel(Request $request, Participation $participation)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        \Illuminate\Support\Facades\DB::transaction(function () use ($participation, $request, $data) {
            // Même ordre de verrouillage que le contrôle d'entrée.
            $locked = Participation::whereKey($participation->id)->lockForUpdate()->firstOrFail();
            $tickets = $locked->tickets()->orderBy('id')->lockForUpdate()->get();
            if (!in_array($locked->status, ['pending', 'confirmed']) || $tickets->contains('status', 'used')
                || \App\Models\Attendance::whereIn('ticket_id', $tickets->modelKeys())->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['cancellation' => 'Cette réservation est déjà traitée ou possède une entrée enregistrée. Elle ne peut pas être annulée.']);
            }
            $locked->update(['status' => 'cancelled', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_reason' => trim($data['reason'])]);
            $locked->tickets()->update(['status' => 'cancelled']);
            \App\Models\WhatsappDelivery::whereIn('ticket_id', $tickets->modelKeys())->whereIn('status', ['pending', 'failed'])->update(['status' => 'cancelled']);
        });
        return redirect()->route('admin.participations.index')->with('status', 'Réservation annulée. Son billet ne permet plus d’entrer.');
    }

    public function destroy(Request $request, Participation $participation, \App\Services\ReservationDeletionService $service)
    {
        $request->validate(['current_password' => ['required', 'current_password:web'],
            'confirmation_reference' => ['required', 'string', \Illuminate\Validation\Rule::in([$participation->reference])]],
            ['confirmation_reference.in' => 'Recopie exactement la référence de cette réservation.']);
        $service->delete($participation);
        return redirect()->route('admin.participations.index')->with('status', 'Réservation et billet supprimés définitivement.');
    }

}
