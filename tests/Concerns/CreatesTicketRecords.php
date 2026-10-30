<?php

namespace Tests\Concerns;

use App\Models\Edition;
use App\Models\Participant;
use App\Models\Participation;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Support\Str;

trait CreatesTicketRecords
{
    protected function makeParticipation(bool $optIn = false): Participation
    {
        $edition = Edition::firstOrCreate(['number' => 1], [
            'name' => 'RDSA test', 'slug' => 'rdsa-test', 'theme' => 'Prière',
            'starts_at' => '2027-02-13 08:00:00', 'venue' => 'Temple de test',
            'base_price' => 1000, 'is_active' => true, 'is_published' => true,
        ]);
        $participant = Participant::create(['full_name' => 'Participant test', 'phone' => '+2250700000001']);
        $participation = Participation::create([
            'edition_id' => $edition->id, 'participant_id' => $participant->id,
            'reference' => 'RDSA-' . Str::random(12), 'submission_token' => Str::uuid(),
            'unit_price' => 1000, 'base_amount' => 1000, 'total_expected' => 1000,
            'status' => 'confirmed', 'whatsapp_opt_in' => $optIn,
        ]);
        return $participation;
    }

    protected function makeTicket(bool $optIn = false): Ticket
    {
        return app(TicketService::class)->issue($this->makeParticipation($optIn));
    }

    protected function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->is_admin = true;
        $user->save();
        return $user;
    }
}
