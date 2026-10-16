<?php

namespace App\Http\Controllers;

use App\Models\Edition;
use App\Models\Participation;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\WaveNumber;
use App\Services\ParticipationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ParticipationController extends Controller
{
    public function store(
        Request $request,
        ParticipationService $service
    ) {
        $data = $request->validate([
            'edition_id' => ['required', 'integer', 'exists:editions,id'],
            'submission_token' => ['required', 'uuid'],
            'full_name' => ['required', 'string', 'min:2', 'max:150'],
            'phone' => ['required', 'string', 'min:8', 'max:30'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'extra_contribution' => [
                'required',
                'integer',
                'min:0',
                'max:100000000',
            ],
            'wave_number_id' => [
                'required',
                'integer',
                'exists:wave_numbers,id',
            ],
            'transaction_reference' => [
                'required',
                'string',
                'max:150',
            ],
            'payment_amount' => [
                'required',
                'integer',
                'min:1',
                'max:100000000',
            ],
            'payment_proof' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        /*
         * Refuser les jetons qui ne correspondent pas
         * à la session du visiteur.
         */
        $sessionToken = $request->session()->get(
            'participation_submission_token'
        );

        if (
            !is_string($sessionToken) ||
            !hash_equals($sessionToken, $data['submission_token'])
        ) {
            throw ValidationException::withMessages([
                'submission_token' =>
                    'Le formulaire a expiré. Recharge la page.',
            ]);
        }

        /*
         * Vérifier l'édition et le numéro Wave côté serveur.
         */
        $edition = Edition::query()
            ->whereKey($data['edition_id'])
            ->where('is_active', true)
            ->first();

        if (!$edition) {
            throw ValidationException::withMessages([
                'edition_id' => 'Cette édition n’accepte plus de participations.',
            ]);
        }

        $wave = WaveNumber::query()
            ->whereKey($data['wave_number_id'])
            ->where('is_active', true)
            ->first();

        if (!$wave) {
            throw ValidationException::withMessages([
                'wave_number_id' => 'Ce numéro Wave n’est plus disponible.',
            ]);
        }

        /*
         * Le montant minimum est recalculé avec
         * le prix provenant de la base de données.
         */
        $baseAmount = (int) $edition->base_price
            * (int) $data['quantity'];

        $expectedAmount = $baseAmount
            + (int) $data['extra_contribution'];

        if ((int) $data['payment_amount'] < $expectedAmount) {
            throw ValidationException::withMessages([
                'payment_amount' =>
                    'Le montant envoyé est inférieur au montant attendu.',
            ]);
        }

        $storedPath = null;

        try {
            $participation = DB::transaction(function () use (
                $request,
                $service,
                $data,
                $edition,
                $wave,
                &$storedPath
            ) {
                /*
                 * 1. Créer la participation.
                 */
                $participation = $service->create([
                    'edition_id' => $edition->id,
                    'submission_token' => $data['submission_token'],
                    'full_name' => $data['full_name'],
                    'phone' => $data['phone'],
                    'quantity' => $data['quantity'],
                    'extra_contribution' => $data['extra_contribution'],
                ]);

                /*
                 * 2. Enregistrer le paiement déclaré.
                 */
                $payment = Payment::create([
                    'participation_id' => $participation->id,
                    'wave_number_id' => $wave->id,
                    'transaction_reference' => $data['transaction_reference'],
                    'amount_declared' => $data['payment_amount'],
                    'payment_date_declared' => null,
                    'wave_phone_snapshot' => $wave->phone,
                    'status' => Payment::STATUS_PENDING,
                ]);

                /*
                 * 3. Enregistrer la preuve dans un stockage privé.
                 */
                $file = $request->file('payment_proof');

                $storedPath = $file->store(
                    'rdsa/payment-proofs',
                    'local'
                );

                if (!$storedPath) {
                    throw new \RuntimeException(
                        'Impossible d’enregistrer la preuve de paiement.'
                    );
                }

                PaymentProof::create([
                    'payment_id' => $payment->id,
                    'disk' => 'local',
                    'file_path' => $storedPath,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size_bytes' => $file->getSize(),
                ]);

                return $participation;
            }, 3);
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }

        /*
         * Invalider le jeton après la réussite.
         */
        $request->session()->put(
            'participation_submission_token',
            (string) \Illuminate\Support\Str::uuid()
        );

        return redirect()->route(
            'participations.confirmation',
            ['reference' => $participation->reference]
        );
    }

    public function confirmation(string $reference)
    {
        $participation = Participation::query()
            ->with(['participant', 'edition'])
            ->where('reference', $reference)
            ->firstOrFail();

        return view(
            'participations.confirmation',
            compact('participation')
        );
    }
}