<?php

namespace App\Services;

use App\Models\Participation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationReviewService
{
    public function review(Participation $participation, User $admin, string $decision, ?string $reason = null): void
    {
        DB::transaction(function () use ($participation, $admin, $decision, $reason) {
            $locked = Participation::whereKey($participation->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== Participation::STATUS_PENDING) throw ValidationException::withMessages(['reservation' => 'Cette demande a déjà été traitée.']);
            if (!in_array($decision, ['approve', 'reject'], true)) throw ValidationException::withMessages(['decision' => 'Décision invalide.']);
            if ($decision === 'reject' && !trim($reason ?? '')) throw ValidationException::withMessages(['reason' => 'Indique le motif du refus.']);
            $locked->update(['status' => $decision === 'approve' ? Participation::STATUS_CONFIRMED : Participation::STATUS_REJECTED,
                'reviewed_by' => $admin->id, 'reviewed_at' => now(), 'review_reason' => $decision === 'reject' ? trim($reason) : null]);
            if ($decision === 'approve') app(TicketService::class)->issue($locked);
        }, 3);
    }
}
