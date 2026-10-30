<?php

namespace App\Http\Controllers;

use App\Models\Participation;
use App\Services\ParticipationService;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ParticipationController extends Controller
{
    public function store(Request $request, ParticipationService $service)
    {
        $token = $request->input('submission_token');
        if (!is_string($token)) throw ValidationException::withMessages(['submission_token' => 'Le formulaire a expiré. Recharge la page.']);
        $started = $request->session()->get('reservation_form_times.' . $token);
        if (!is_string($token) || !in_array($token, $request->session()->get('reservation_form_tokens', []), true)
            || !is_numeric($started) || now()->timestamp - (int) $started > 1800) {
            throw ValidationException::withMessages(['submission_token' => 'Le formulaire a expiré. Recharge la page.']);
        }
        if ($request->filled('website') || now()->timestamp - (int) $started < 3) {
            throw ValidationException::withMessages(['submission_token' => 'Vérifie les informations du formulaire avant de confirmer.']);
        }
        $request->validate(['has_attended_before' => ['required', 'boolean'], 'review_confirmed' => ['accepted']]);
        $participation = $service->create($request->only(['edition_id', 'submission_token', 'full_name', 'phone', 'whatsapp_opt_in', 'has_attended_before', 'previous_edition_ids']));
        $request->session()->put('reservation_requests.' . $participation->reference, $participation->id);
        $url = route('participations.confirmation', ['reference' => $participation->reference]);
        if ($request->expectsJson()) return response()->json(['url' => $url, 'reference' => $participation->reference, 'status' => $participation->status])->header('Cache-Control', 'private, no-store');
        return redirect()->to($url)->header('Cache-Control', 'private, no-store');
    }

    public function confirmation(Request $request, string $reference, TicketService $tickets)
    {
        $id = $request->session()->get('reservation_requests.' . $reference);
        abort_unless($id, 404);
        $participation = Participation::with(['participant', 'edition', 'tickets'])->whereKey($id)->where('reference', $reference)->firstOrFail();
        $ticket = $participation->status === Participation::STATUS_CONFIRMED ? $participation->tickets->first() : null;
        if ($request->expectsJson()) return response()->json(['status' => $participation->status, 'url' => $ticket ? $tickets->publicUrl($ticket) : null])->header('Cache-Control', 'private, no-store');
        if ($ticket) return redirect()->to($tickets->publicUrl($ticket))->header('Cache-Control', 'private, no-store');
        return response()->view('participations.confirmation', compact('participation'))->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer')->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
