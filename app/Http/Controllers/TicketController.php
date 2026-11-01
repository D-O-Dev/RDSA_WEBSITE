<?php

namespace App\Http\Controllers;

use App\Models\Participation;
use App\Models\Ticket;
use App\Services\TicketQrService;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller
{
    public function find()
    {
        return response()->view('tickets.find')->header('Cache-Control', 'private, no-store')
            ->header('Referrer-Policy', 'no-referrer')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function lookup(Request $request, TicketService $service)
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:50'],
            'access_code' => ['required', 'string', 'max:100'],
        ]);
        $reference = strtoupper(trim($data['reference']));
        $token = strtoupper(preg_replace('/[\s-]+/', '', $data['access_code']));
        $ticket = Ticket::with('participation.participant')->where(fn ($q) => $q->where('reference', $reference)
            ->orWhereHas('participation', fn ($p) => $p->whereRaw('UPPER(reference) = ?', [$reference])))->first();
        $codeMatches = $ticket && $ticket->access_token_hash && hash_equals($ticket->access_token_hash, hash('sha256', $token));
        $phone = \App\Services\ParticipationService::normalizePhone($data['access_code']);
        $phoneMatches = $ticket && preg_match('/^(?:\+|00)?[0-9\s().-]{8,25}$/', $data['access_code']) && strlen($phone) >= 8 && hash_equals(
            \App\Services\ParticipationService::normalizePhone($ticket->participation->participant->phone), $phone);
        if (!$ticket && preg_match('/^(?:\+|00)?[0-9\s().-]{8,25}$/', $data['access_code'])) {
            $requestRecord = Participation::with('participant')->whereRaw('UPPER(reference) = ?', [$reference])->first();
            if ($requestRecord && hash_equals(\App\Services\ParticipationService::normalizePhone($requestRecord->participant->phone), $phone)) {
                $request->session()->put('reservation_requests.' . $requestRecord->reference, $requestRecord->id);
                $url = route('participations.confirmation', ['reference' => $requestRecord->reference]);
                if ($request->expectsJson()) return response()->json(['url' => $url])->header('Cache-Control', 'private, no-store');
                return redirect()->to($url)->header('Cache-Control', 'private, no-store');
            }
        }
        if (!$ticket || (!$codeMatches && !$phoneMatches)) {
            throw ValidationException::withMessages(['reference' => 'Aucun billet ne correspond à ces informations. Vérifie la référence et le téléphone ou le code privé.']);
        }
        $url = $service->publicUrl($ticket);
        if ($request->expectsJson()) return response()->json(['url' => $url])->header('Cache-Control', 'private, no-store');
        return redirect()->to($url)->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function show(Request $request, string $reference, string $token, TicketQrService $qr)
    {
        $ticket = Ticket::with(['participation.participant', 'participation.edition'])->where('reference', $reference)->first();
        abort_unless($ticket && $ticket->access_token_hash
            && hash_equals($ticket->access_token_hash, hash('sha256', $token)), 404);
        if ($request->boolean('qr')) {
            abort_unless($ticket->status === Ticket::STATUS_VALID && $ticket->participation->status === Participation::STATUS_CONFIRMED, 409);
            $uri = $qr->dataUri($ticket);
            return response(base64_decode(explode(',', $uri, 2)[1]))->header('Content-Type', 'image/svg+xml')
                ->header('Content-Disposition', 'attachment; filename="' . $ticket->reference . '-qr.svg"')->header('Cache-Control', 'private, no-store');
        }
        if ($request->boolean('download')) {
            return response(app(\App\Services\TicketDocumentService::class)->pdf($ticket))
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $ticket->reference . '.pdf"')
                ->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer')
                ->header('X-Robots-Tag', 'noindex, nofollow');
        }
        $data = app(\App\Services\TicketDocumentService::class)->data($ticket);
        $download = false;
        $response = response()->view('tickets.show', [...$data, 'download' => $download])
            ->header('Cache-Control', 'private, no-store')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('X-Content-Type-Options', 'nosniff');
        return $response;
    }
}
