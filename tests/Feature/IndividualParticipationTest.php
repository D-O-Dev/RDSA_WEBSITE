<?php

namespace Tests\Feature;

use App\Models\Edition;
use App\Services\ParticipationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class IndividualParticipationTest extends TestCase
{
    use RefreshDatabase;

    private function data(): array
    {
        $edition = Edition::create([
            'name' => 'RDSA test', 'slug' => 'rdsa-test', 'number' => 1,
            'theme' => 'Prière', 'starts_at' => '2027-02-13 08:00:00',
            'venue' => 'Abidjan', 'base_price' => 1000, 'is_active' => true,
        ]);

        return [
            'edition_id' => $edition->id, 'submission_token' => (string) Str::uuid(),
            'full_name' => 'Participant test', 'phone' => '+2250700000000',
            'extra_contribution' => 500,
        ];
    }

    public function test_individual_reservation_waits_for_review_without_issuing_a_ticket(): void
    {
        $participation = app(ParticipationService::class)->create($this->data());
        $this->assertArrayNotHasKey('quantity', $participation->getAttributes());
        $this->assertEquals(0, $participation->base_amount);
        $this->assertEquals(0, $participation->total_expected);
        $this->assertSame('pending', $participation->status);
        $this->assertFalse($participation->whatsapp_opt_in);
        $this->assertDatabaseCount('tickets', 0);
        $this->assertDatabaseCount('payments', 0);
    }
    public function test_whatsapp_reception_choice_is_saved(): void
    {
        $data = $this->data();
        $data['whatsapp_opt_in'] = true;
        $participation = app(ParticipationService::class)->create($data);
        $this->assertTrue($participation->whatsapp_opt_in);
    }
}
