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
        $validator = Validator::make($data, [
            'edition_id' => ['required', 'integer', 'exists:editions,id'],
            'submission_token' => ['required', 'uuid'],
            'full_name' => ['required', 'string', 'min:2', 'max:150'],
            'phone' => ['required', 'string', 'min:8', 'max:30'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'extra_contribution' => [
                'nullable',
                'integer',
                'min:0',
                'max:100000000',
            ],
        ]);

        $validator->validate();

        $existingParticipation = Participation::query()
            ->where('submission_token', $data['submission_token'])
            ->first();

        if ($existingParticipation) {
            return $existingParticipation->load(['participant', 'edition']);
        }

        $fullName = trim($data['full_name']);

        // Uniformiser le téléphone avant de rechercher un participant.
        $phone = preg_replace('/[\s().-]+/', '', trim($data['phone']));

        if ($phone === '' || strlen($phone) < 8 || strlen($phone) > 30) {
            throw ValidationException::withMessages([
                'phone' => 'Le numéro de téléphone est invalide.',
            ]);
        }

        return DB::transaction(function () use (
            $data,
            $fullName,
            $phone
        ) {
            $edition = Edition::query()
                ->whereKey($data['edition_id'])
                ->where('is_active', true)
                ->first();

            if (!$edition) {
                throw ValidationException::withMessages([
                    'edition_id' => 'Cette édition n’accepte pas actuellement de participations.',
                ]);
            }

            $quantity = (int) $data['quantity'];

            // Le tarif vient de la base, jamais du formulaire.
            $unitPrice = (int) $edition->base_price;

            $baseAmount = $unitPrice * $quantity;

            $extraContribution = (int) (
                $data['extra_contribution'] ?? 0
            );

            $totalExpected = $baseAmount + $extraContribution;

            // Même nom et même téléphone : participant existant.
            // Un téléphone seul ne suffit pas à identifier une personne.
            $participant = Participant::query()->firstOrCreate(
                [
                    'full_name' => $fullName,
                    'phone' => $phone,
                ]
            );

            // Générer une référence unique.
            do {
                $reference = 'RDSA-' . strtoupper(Str::random(12));
            } while (
                Participation::where('reference', $reference)->exists()
            );

            return Participation::create([
                'participant_id' => $participant->id,
                'edition_id' => $edition->id,
                'reference' => $reference,
                'submission_token' => $data['submission_token'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'base_amount' => $baseAmount,
                'extra_contribution' => $extraContribution,
                'total_expected' => $totalExpected,
                'status' => Participation::STATUS_PENDING,
            ])->load(['participant', 'edition']);
        }, 3);
    }
}
