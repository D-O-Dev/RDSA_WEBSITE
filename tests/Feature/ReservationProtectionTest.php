<?php

namespace Tests\Feature;

use App\Models\Edition;
use App\Models\Participation;
use App\Services\ParticipationService;
use App\Services\ReservationReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesTicketRecords;
use Tests\TestCase;

class ReservationProtectionTest extends TestCase
{
    use RefreshDatabase, CreatesTicketRecords;

    private function edition(): Edition
    {
        return Edition::create(['name' => 'RDSA', 'number' => 1, 'slug' => 'rdsa', 'theme' => 'Prière', 'starts_at' => '2027-06-01 08:00:00', 'venue' => 'Lieu', 'is_active' => true, 'is_published' => true]);
    }

    private function data(Edition $edition, string $phone = '0700000001'): array
    {
        return ['edition_id' => $edition->id, 'submission_token' => (string) Str::uuid(), 'full_name' => 'Visiteur', 'phone' => $phone, 'has_attended_before' => 0, 'review_confirmed' => 1];
    }

    private function sessionFor(array $data, int $age = 5): self
    {
        return $this->withSession(['reservation_form_tokens' => [$data['submission_token']], 'reservation_form_times' => [$data['submission_token'] => now()->timestamp - $age]]);
    }

    public function test_same_phone_cannot_create_multiple_requests_even_with_another_name_and_token(): void
    {
        $edition = $this->edition();
        app(ParticipationService::class)->create($this->data($edition));
        $second = [...$this->data($edition, '+225 07 00 00 00 01'), 'full_name' => 'Autre nom'];
        $this->sessionFor($second)->postJson('/participations', $second)->assertUnprocessable()->assertJsonValidationErrors('phone');
        $this->assertDatabaseCount('participations', 1);
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_honeypot_too_fast_and_expired_form_are_rejected(): void
    {
        $data = $this->data($this->edition());
        $this->sessionFor($data)->postJson('/participations', [...$data, 'website' => 'spam.test'])->assertUnprocessable();
        $this->sessionFor($data, 0)->postJson('/participations', $data)->assertUnprocessable();
        $this->sessionFor($data, 1801)->postJson('/participations', $data)->assertUnprocessable();
        $this->assertDatabaseCount('participations', 0);
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_request_rate_limits_block_bulk_submissions_with_different_phone_numbers(): void
    {
        $edition = $this->edition();
        for ($i = 1; $i <= 4; $i++) {
            $data = $this->data($edition, '070000000' . $i);
            $response = $this->sessionFor($data)->postJson('/participations', $data);
            $i <= 3 ? $response->assertOk()->assertJson(['status' => 'pending']) : $response->assertTooManyRequests();
        }
        $this->assertDatabaseCount('participations', 3);
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_previous_participation_choices_are_saved_and_invalid_editions_are_refused(): void
    {
        $edition = $this->edition();
        $past = Edition::create(['name' => 'Ancienne rencontre', 'number' => 2, 'slug' => 'ancienne', 'theme' => 'Prière', 'starts_at' => '2025-06-01 08:00:00', 'venue' => 'Lieu', 'is_published' => true]);
        $data = [...$this->data($edition), 'has_attended_before' => 1, 'previous_edition_ids' => [$past->id]];
        $this->sessionFor($data)->postJson('/participations', [...$data, 'previous_edition_ids' => [$edition->id]])->assertUnprocessable()->assertJsonValidationErrors('previous_edition_ids');
        $this->sessionFor($data)->postJson('/participations', $data)->assertOk();
        $record = Participation::firstOrFail();
        $this->assertTrue($record->has_attended_before);
        $this->assertSame([$past->id], $record->previous_edition_ids);
        $this->actingAs($this->makeAdmin())->get('/admin/participations')->assertOk()->assertSee('Ancienne rencontre');
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_no_confirmation_without_review_and_no_guest_approval(): void
    {
        $data = $this->data($this->edition());
        $this->sessionFor($data)->postJson('/participations', [...$data, 'review_confirmed' => 0])->assertUnprocessable()->assertJsonValidationErrors('review_confirmed');
        $record = app(ParticipationService::class)->create($data);
        $this->post('/admin/participations/' . $record->id . '/review', ['decision' => 'approve'])->assertRedirect('/admin/login');
        $this->actingAs(\App\Models\User::factory()->create())->post('/admin/participations/' . $record->id . '/review', ['decision' => 'approve'])->assertForbidden();
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_approval_is_atomic_and_cannot_be_repeated_or_turned_into_rejection(): void
    {
        $record = app(ParticipationService::class)->create($this->data($this->edition()));
        $this->actingAs($this->makeAdmin())->post('/admin/participations/' . $record->id . '/review', ['decision' => 'approve'])->assertSessionHasNoErrors();
        $this->post('/admin/participations/' . $record->id . '/review', ['decision' => 'approve'])->assertSessionHasErrors('reservation');
        $this->post('/admin/participations/' . $record->id . '/review', ['decision' => 'reject', 'reason' => 'Second clic'])->assertSessionHasErrors('reservation');
        $this->assertDatabaseCount('tickets', 1);
        $this->assertSame('confirmed', $record->fresh()->status);
    }

    public function test_rejected_request_has_no_ticket_and_pending_lookup_requires_phone(): void
    {
        $record = app(ParticipationService::class)->create($this->data($this->edition()));
        $this->get('/participations/confirmation/' . $record->reference)->assertNotFound();
        $this->postJson('/mon-billet', ['reference' => $record->reference, 'access_code' => '0700000002'])->assertUnprocessable();
        $url = $this->postJson('/mon-billet', ['reference' => $record->reference, 'access_code' => '0700000001'])->assertOk()->json('url');
        $this->get($url)->assertOk()->assertSee('Demande reçue')->assertDontSee('data:image/svg+xml;base64,', false);
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->post('/admin/participations/' . $record->id . '/review', ['decision' => 'reject'])->assertSessionHasErrors('reason');
        $this->post('/admin/participations/' . $record->id . '/review', ['decision' => 'reject', 'reason' => 'Demande non vérifiable'])->assertSessionHasNoErrors();
        $this->get($url)->assertOk()->assertSee('Demande non validée');
        $this->assertDatabaseCount('tickets', 0);
    }
}
