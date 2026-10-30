<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketRecords;
use Tests\TestCase;

class AdminParticipationRenderingTest extends TestCase
{
    use RefreshDatabase, CreatesTicketRecords;

    public function test_each_row_renders_only_the_actions_for_its_actual_state(): void
    {
        $ticket = $this->makeTicket();
        $pending = $this->makeParticipation(); $pending->update(['status' => 'pending']);
        $rejected = $this->makeParticipation(); $rejected->update(['status' => 'rejected']);
        $cancelled = $this->makeParticipation(); $cancelled->update(['status' => 'cancelled']);
        $response = $this->actingAs($this->makeAdmin())->get('/admin/participations')->assertOk();
        $html = $response->getContent();
        $this->assertDoesNotMatchRegularExpression('/@(if|else|endif|forelse|endforelse|empty)\b/', $html);
        $doc = new \DOMDocument(); @$doc->loadHTML($html); $x = new \DOMXPath($doc);
        foreach ([$ticket->participation, $pending, $rejected, $cancelled] as $record) {
            $row = $x->query('//tbody/tr[td/b="' . $record->reference . '"]')->item(0);
            $this->assertNotNull($row);
            $this->assertSame($record->id === $pending->id ? 2 : 0, $x->query('.//form', $row)->length);
            if ($record->id === $ticket->participation_id) {
                $this->assertStringContainsString('Voir le billet', $row->textContent);
                $this->assertStringNotContainsString('Aucun billet émis', $row->textContent);
                $this->assertStringNotContainsString('Refuser la demande', $row->textContent);
            }
            if ($record->id === $pending->id) {
                $this->assertStringContainsString('Valider et émettre', $row->textContent);
                $this->assertStringNotContainsString('Aucun billet émis', $row->textContent);
                $this->assertSame(1, $x->query('.//details[not(@open)]', $row)->length);
            }
            if ($record->id === $rejected->id) $this->assertStringContainsString('Demande refusée', $row->textContent);
        }
    }

    public function test_live_filter_results_do_not_contain_uncompiled_blade_or_duplicate_errors(): void
    {
        $record = $this->makeParticipation(); $record->update(['status' => 'pending']);
        $response = $this->actingAs($this->makeAdmin())->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get('/admin/participations?q=' . $record->reference)->assertOk();
        $this->assertDoesNotMatchRegularExpression('/@(if|else|endif|forelse|endforelse|empty)\b/', $response->getContent());
        $response->assertSee('Valider et émettre')->assertDontSee('Aucun billet émis');
        $record->update(['status' => 'rejected']);
        $this->flushHeaders();
        $this->from('/admin/participations')->post('/admin/participations/' . $record->id . '/review', ['decision' => 'approve'])
            ->assertRedirect('/admin/participations')->assertSessionHasErrors('reservation');
        $html = $this->withCookie(config('session.cookie'), session()->getId())->get('/admin/participations')->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'Cette demande a déjà été traitée.'));
    }
}
