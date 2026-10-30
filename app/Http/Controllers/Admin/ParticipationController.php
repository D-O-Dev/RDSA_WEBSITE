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
        return view('admin.participations', [
            'participations' => $participations, 'editions' => Edition::orderByDesc('starts_at')->get(),
        ]);
    }
    public function review(Request $request, Participation $participation, \App\Services\ReservationReviewService $service)
    {
        $data = $request->validate(['decision' => ['required', Rule::in(['approve', 'reject'])], 'reason' => ['nullable', 'required_if:decision,reject', 'string', 'max:1000']]);
        $service->review($participation, $request->user(), $data['decision'], $data['reason'] ?? null);
        return redirect()->route('admin.participations.index')->with('status', $data['decision'] === 'approve' ? 'Demande validée et billet émis.' : 'Demande refusée.');
    }
}
