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

    private function data(int $quantity): array
    {
        $edition = Edition::create([
            'name' => 'RDSA test', 'slug' => 'rdsa-test', 'number' => 1,
            'theme' => 'Prière', 'starts_at' => '2027-02-13 08:00:00',
            'venue' => 'Abidjan', 'base_price' => 1000, 'is_active' => true,
        ]);

        return [
            'edition_id' => $edition->id, 'submission_token' => (string) Str::uuid(),
            'full_name' => 'Participant test', 'phone' => '+2250700000000',
            'quantity' => $quantity, 'extra_contribution' => 500,
        ];
    }

    public function test_group_registration_is_rejected_without_creating_a_participant(): void
    {
        try {
            app(ParticipationService::class)->create($this->data(2));
            $this->fail('Une inscription groupée doit être refusée.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quantity', $exception->errors());
        }
        $this->assertDatabaseCount('participants', 0);
        $this->assertDatabaseCount('participations', 0);
    }

    public function test_individual_registration_preserves_price_and_optional_contribution(): void
    {
        $participation = app(ParticipationService::class)->create($this->data(1));
        $this->assertSame(1, $participation->quantity);
        $this->assertSame(1000, $participation->base_amount);
        $this->assertSame(1500, $participation->total_expected);
        $this->assertSame('pending', $participation->status);
        $this->assertDatabaseCount('tickets', 0);
    }
}
