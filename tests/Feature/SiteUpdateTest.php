<?php

namespace Tests\Feature;

use App\Models\Edition;
use App\Models\SiteUpdate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketRecords;
use Tests\TestCase;

class SiteUpdateTest extends TestCase
{
    use RefreshDatabase, CreatesTicketRecords;

    private function data(array $changes = []): array
    {
        return array_replace(['kind' => 'announcement', 'title' => 'Information importante', 'body' => 'Les portes ouvrent à huit heures.', 'edition_id' => '', 'is_published' => true, 'sort_order' => 0, 'starts_at' => '', 'ends_at' => ''], $changes);
    }

    private function item(User $admin, array $changes = []): SiteUpdate
    {
        $data = array_replace($this->data(), ['edition_id' => null, 'starts_at' => null, 'ends_at' => null], $changes);
        $item = new SiteUpdate($data); $item->created_by = $admin->id; $item->save(); return $item;
    }

    public function test_only_administrators_can_manage_updates_including_partial_requests(): void
    {
        $this->get('/admin/updates')->assertRedirect('/admin/login');
        $this->post('/admin/updates', $this->data())->assertRedirect('/admin/login');
        $admin = $this->makeAdmin(); $item = $this->item($admin);
        $controller = User::factory()->create(); $controller->forceFill(['team_role' => 'controller'])->save();
        $this->actingAs($controller)->withHeaders(['X-RDSA-Navigation' => '1'])->get('/admin/updates')->assertForbidden();
        $this->post('/admin/updates', $this->data())->assertForbidden();
        $this->put('/admin/updates/' . $item->id, $this->data())->assertForbidden();
        $this->patch('/admin/updates/' . $item->id . '/publication', ['is_published' => false])->assertForbidden();
        $this->delete('/admin/updates/' . $item->id)->assertForbidden();
    }

    public function test_announcement_and_faq_creation_publication_editing_and_removal_are_real(): void
    {
        $admin = $this->makeAdmin(); $this->actingAs($admin);
        $this->post('/admin/updates', $this->data())->assertSessionHasNoErrors()->assertRedirect('/admin/updates?kind=announcement');
        $notice = SiteUpdate::firstOrFail(); $this->assertSame($admin->id, $notice->created_by);
        $this->get('/')->assertSee('Information importante')->assertSee('Les portes ouvrent à huit heures.');
        $this->post('/admin/updates', $this->data(['kind' => 'faq', 'title' => 'Comment retrouver mon billet ?', 'body' => 'Utilise le bouton Mon billet.']))->assertSessionHasNoErrors()->assertRedirect('/admin/updates?kind=faq');
        $faq = SiteUpdate::latest('id')->firstOrFail();
        $this->get('/')->assertSee('Comment retrouver mon billet ?')->assertSee('Utilise le bouton Mon billet.');
        $this->patch('/admin/updates/' . $faq->id . '/publication', ['is_published' => false])->assertSessionHasNoErrors();
        $this->get('/')->assertDontSee('Comment retrouver mon billet ?');
        $this->put('/admin/updates/' . $notice->id, $this->data(['title' => 'Annonce modifiée', 'body' => 'Nouveau message', 'created_by' => 999]))->assertSessionHasNoErrors();
        $this->assertSame($admin->id, $notice->fresh()->created_by);
        $this->get('/')->assertSee('Annonce modifiée')->assertDontSee('Information importante');
        $this->delete('/admin/updates/' . $notice->id)->assertRedirect('/admin/updates?kind=announcement');
        $this->assertSoftDeleted($notice);
        $this->get('/')->assertDontSee('Annonce modifiée');
        $this->get('/admin/updates?edit=' . $notice->id)->assertNotFound();
    }

    public function test_publication_dates_are_inclusive_at_start_and_exclusive_at_end(): void
    {
        $this->travelTo(Carbon::parse('2028-03-10 12:00:00')); $admin = $this->makeAdmin();
        $notice = $this->item($admin, ['starts_at' => now()->addMinute(), 'ends_at' => now()->addMinutes(2)]);
        $this->assertSame('Programmé', $notice->publicationLabel());
        $this->get('/')->assertDontSee('Information importante');
        $this->travel(1)->minutes();
        $this->get('/')->assertSee('Information importante'); $this->assertSame('Publié', $notice->publicationLabel());
        $this->travel(1)->minutes();
        $this->get('/')->assertDontSee('Information importante'); $this->assertSame('Terminé', $notice->publicationLabel());
        $this->actingAs($admin)->post('/admin/updates', $this->data(['title' => 'Fin sans début', 'starts_at' => '', 'ends_at' => '2028-03-11T12:00']))->assertSessionHasNoErrors();
        $this->get('/')->assertSee('Fin sans début');
    }

    public function test_edition_scopes_never_leak_inactive_edition_messages(): void
    {
        $edition = Edition::create(['number' => 1, 'name' => 'Édition active', 'slug' => 'edition-active', 'theme' => 'Prière', 'starts_at' => '2028-03-11 08:00:00', 'venue' => 'Salle', 'is_active' => true, 'is_published' => true]);
        $other = Edition::create(['number' => 2, 'name' => 'Ancienne édition', 'slug' => 'ancienne', 'theme' => 'Prière', 'starts_at' => '2027-03-11 08:00:00', 'venue' => 'Salle', 'is_active' => false, 'is_published' => true]);
        $admin = $this->makeAdmin();
        $this->item($admin, ['title' => 'Information générale']);
        $this->item($admin, ['title' => 'Information édition active', 'edition_id' => $edition->id]);
        $this->item($admin, ['title' => 'Information ancienne édition', 'edition_id' => $other->id]);
        $this->get('/')->assertSee('Information générale')->assertSee('Information édition active')->assertDontSee('Information ancienne édition');
        $edition->update(['is_archived' => true]);
        $this->get('/')->assertSee('Information générale')->assertDontSee('Information édition active')->assertDontSee('Information ancienne édition');
    }

    public function test_faq_order_and_public_rendering_stay_inside_the_existing_question_window(): void
    {
        $admin = $this->makeAdmin();
        $this->item($admin, ['kind' => 'faq', 'title' => 'Deuxième question', 'sort_order' => 10]);
        $first = $this->item($admin, ['kind' => 'faq', 'title' => 'Première question', 'sort_order' => 0]);
        $response = $this->get('/')->assertOk();
        $this->assertSame(['Première question', 'Deuxième question'], $response->viewData('faqs')->pluck('title')->all());
        $doc = new \DOMDocument(); @$doc->loadHTML($response->getContent()); $x = new \DOMXPath($doc);
        $this->assertSame(2, $x->query('//dialog[@id="questions-dialog"]//details[contains(@class,"questions-faq-item")]')->length);
        $this->assertSame(1, $x->query('//dialog[@id="questions-dialog"]//form[@data-contact-form]')->length);
        $this->assertSame(0, $x->query('//main/section[@id="faq" or @id="annonces"]')->length);
        $this->actingAs($admin)->put('/admin/updates/' . $first->id, $this->data(['kind' => 'faq', 'title' => 'Première question', 'sort_order' => 20]))->assertSessionHasNoErrors();
        $this->assertSame(['Deuxième question', 'Première question'], $this->get('/')->viewData('faqs')->pluck('title')->all());
    }

    public function test_validation_and_escaped_text_prevent_invalid_or_executable_content(): void
    {
        $this->actingAs($this->makeAdmin());
        $this->post('/admin/updates', $this->data(['starts_at' => '2028-03-12T08:00', 'ends_at' => '2028-03-11T08:00']))->assertSessionHasErrors('ends_at');
        $this->post('/admin/updates', $this->data(['body' => '']))->assertSessionHasErrors('body');
        $this->post('/admin/updates', $this->data(['edition_id' => 999]))->assertSessionHasErrors('edition_id');
        $this->assertDatabaseCount('site_updates', 0);
        $this->post('/admin/updates', $this->data(['title' => '<script>alert(1)</script>', 'body' => '<img src=x onerror=alert(1)>']))->assertSessionHasNoErrors();
        $this->get('/')->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    public function test_partial_navigation_and_search_keep_the_publication_filter_context(): void
    {
        $admin = $this->makeAdmin(); $item = $this->item($admin, ['kind' => 'faq', 'title' => 'Question filtre']);
        $this->actingAs($admin)->withHeaders(['X-RDSA-Navigation' => '1', 'X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'text/html'])
            ->get('/admin/updates?kind=faq&q=filtre')->assertOk()->assertSee('Question filtre')->assertDontSee('id="bgimg"', false);
        $this->from('/admin/updates?kind=faq&q=filtre')->patch('/admin/updates/' . $item->id . '/publication', ['is_published' => false])->assertRedirect('/admin/updates?kind=faq&q=filtre');
        $this->get('/admin/updates?kind=faq&create=faq')->assertOk()->assertSee('Question du visiteur')->assertDontSee('Dates d’affichage');
    }
}
