<?php

namespace App\Services;

use App\Models\Edition;
use App\Models\Participant;
use App\Models\Participation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ParticipationService
{
    public function create(array $data): Participation
    {
        $data = Validator::make($data, [
            'edition_id' => ['required', 'integer', 'exists:editions,id'],
            'submission_token' => ['required', 'uuid'],
            'whatsapp_opt_in' => ['sometimes', 'boolean'],
            'has_attended_before' => ['sometimes', 'boolean'],
            'previous_edition_ids' => ['nullable', 'array', 'max:20'],
            'previous_edition_ids.*' => ['integer', 'distinct', 'exists:editions,id'],
            'full_name' => ['required', 'string', 'min:2', 'max:150'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^(?:\+|00)?[0-9\s().-]{8,25}$/'],
        ])->validate();
        $name = trim($data['full_name']);
        $phone = self::normalizePhone($data['phone']);
        if (mb_strlen($name) < 2) throw ValidationException::withMessages(['full_name' => 'Indique ton nom complet.']);
        if (!preg_match('/^[1-9]\d{7,14}$/', $phone)) throw ValidationException::withMessages(['phone' => 'Indique un numéro de téléphone valide.']);
        return DB::transaction(function () use ($data, $name, $phone) {
            // Serialize submissions for this edition; a retry creates neither a second reservation nor a second ticket.
            $edition = Edition::whereKey($data['edition_id'])->lockForUpdate()->firstOrFail();
            $existing = Participation::where('submission_token', $data['submission_token'])->first();
            if ($existing) return $existing->load(['participant', 'edition', 'tickets']);
            if (!$edition->is_active || !$edition->is_published || $edition->is_archived) {
                throw ValidationException::withMessages(['edition_id' => 'Les réservations ne sont pas ouvertes pour cette édition.']);
            }
            $phoneHash = hash('sha256', $phone);
            $duplicate = Participation::where('edition_id', $edition->id)->where(fn ($q) => $q
                ->where('reservation_phone_hash', $phoneHash)
                ->orWhereHas('participant', fn ($p) => $p->whereIn('phone', [$phone, '+' . $phone])))->exists();
            if ($duplicate) throw ValidationException::withMessages(['phone' => 'Une demande existe déjà avec ce téléphone pour cette édition. Utilise « Mon billet » avec ta référence, ou contacte l’équipe.']);
            $previous = ($data['has_attended_before'] ?? false) ? ($data['previous_edition_ids'] ?? []) : [];
            if (($data['has_attended_before'] ?? false) && (!$previous || Edition::whereIn('id', $previous)->where('is_published', true)->where('is_active', false)->count() !== count($previous))) {
                throw ValidationException::withMessages(['previous_edition_ids' => 'Sélectionne au moins une édition précédente disponible.']);
            }
            $person = Participant::firstOrCreate(['full_name' => $name, 'phone' => $phone]);
            $participation = Participation::create([
                'participant_id' => $person->id, 'edition_id' => $edition->id,
                'reference' => 'RDSA-' . strtoupper(Str::random(12)),
                'submission_token' => $data['submission_token'], 'status' => Participation::STATUS_PENDING,
                'reservation_phone_hash' => $phoneHash, 'has_attended_before' => $data['has_attended_before'] ?? false, 'previous_edition_ids' => $previous,
                'whatsapp_opt_in' => $data['whatsapp_opt_in'] ?? false,
            ]);
            return $participation->load(['participant', 'edition', 'tickets']);
        }, 3);
    }

    public static function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '00')) $phone = substr($phone, 2);
        if (strlen($phone) === 10) $phone = '225' . $phone;
        return $phone;
    }
}
