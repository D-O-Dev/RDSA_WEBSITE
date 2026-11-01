<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesTicketRecords;
use Tests\TestCase;

class AdminNavigationTest extends TestCase
{
    use RefreshDatabase, CreatesTicketRecords;

    private array $headers = ['X-RDSA-Navigation' => '1', 'X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'text/html'];

    public function test_partial_navigation_keeps_the_same_content_without_retransmitting_the_shell(): void
    {
        $this->actingAs($this->makeAdmin());
        $full = $this->get('/admin/dashboard')->assertOk()->getContent();
        $partial = $this->withHeaders($this->headers)->get('/admin/dashboard')->assertOk()
            ->assertSee('id="views"', false)->assertSee('id="admin-modal-root"', false)
            ->assertSee('id="nav"', false)->assertSee('id="title"', false)
            ->assertDontSee('<head>', false)->assertDontSee('id="bgimg"', false)
            ->assertDontSee('<script', false)->assertHeader('Cache-Control', 'no-store, private')->getContent();
        $this->assertLessThan(strlen($full) / 2, strlen($partial));
        foreach (['Réservations', 'Billets émis', 'Entrées enregistrées'] as $label) $this->assertStringContainsString($label, $partial);
    }

    public function test_editors_are_available_as_fragments_with_normal_post_forms_and_csrf(): void
    {
        $html = $this->actingAs($this->makeAdmin())->withHeaders($this->headers)->get('/admin/media?create=1')->assertOk()->getContent();
        $doc = new \DOMDocument(); @$doc->loadHTML($html); $xpath = new \DOMXPath($doc);
        $this->assertSame(1, $xpath->query('//*[@id="admin-modal-root"]//*[@role="dialog"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="admin-modal-root"]//form[@method="POST"]//input[@name="_token"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="admin-modal-root"]//input[@name="upload" and @type="file"]')->length);
    }

    public function test_fragments_do_not_bypass_authorization_or_expose_administrator_links(): void
    {
        $this->withHeaders($this->headers)->get('/admin/dashboard')->assertRedirect('/admin/login');
        $controller = User::factory()->create(); $controller->forceFill(['team_role' => 'controller'])->save();
        $this->actingAs($controller)->get('/admin/check-in')->assertOk()->assertSee('check-in-form')
            ->assertDontSee('href="' . route('admin.team.index') . '"', false)
            ->assertDontSee('href="' . route('admin.content.edit') . '"', false);
        $this->get('/admin/team')->assertForbidden();
        $this->post('/admin/calendar/events', [])->assertForbidden();
    }

    public function test_html_validation_returns_an_editor_with_errors_and_preserves_safe_input(): void
    {
        $this->actingAs($this->makeAdmin())->withHeaders($this->headers)
            ->from('/admin/calendar?month=2028-02&create=1')->post('/admin/calendar/events', ['title' => 'Préparation conservée', 'starts_at' => 'invalid'])
            ->assertRedirect('/admin/calendar?month=2028-02&create=1')->assertSessionHasErrors('starts_at');
        $this->withCookie(config('session.cookie'), session()->getId())->get('/admin/calendar?month=2028-02&create=1')
            ->assertOk()->assertSee('role="alert"', false)->assertSee('Préparation conservée');
        $this->assertDatabaseCount('calendar_events', 0);
    }

    public function test_saving_a_fragment_and_changing_password_keeps_routes_and_refreshes_csrf(): void
    {
        $admin = $this->makeAdmin(); $admin->password = 'OriginalPassword123!'; $admin->save();
        $this->actingAs($admin)->withHeaders($this->headers)->get('/admin/account')->assertOk();
        $oldToken = session()->token();
        $this->put('/admin/account', ['current_password' => 'OriginalPassword123!', 'password' => 'ChangedPassword123!', 'password_confirmation' => 'ChangedPassword123!'])
            ->assertRedirect('/admin/account');
        $this->assertNotSame($oldToken, session()->token());
        $this->withCookie(config('session.cookie'), session()->getId())->get('/admin/account')->assertOk()->assertSee('Mot de passe modifié.')->assertDontSee('ChangedPassword123!');
        $this->assertTrue(Hash::check('ChangedPassword123!', $admin->fresh()->password));
    }

    public function test_background_count_does_not_replace_the_previous_form_url(): void
    {
        $this->actingAs($this->makeAdmin())->get('/admin/media?create=1')->assertOk();
        $previous = session()->previousUrl();
        $this->withCookie(config('session.cookie'), session()->getId())->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->get('/admin/messages/count')->assertOk();
        $this->assertSame($previous, session()->previousUrl());
    }

    public function test_instant_publication_preserves_media_filters(): void
    {
        $item = \App\Models\MediaItem::create(['title' => 'Photo recherchée', 'kind' => 'photo', 'disk' => 'assets', 'file_path' => 'images/logo/logo.png', 'is_published' => true, 'sort_order' => 0]);
        $this->actingAs($this->makeAdmin())->withHeaders($this->headers)->from('/admin/media?q=Photo&kind=photo')
            ->patch('/admin/media/' . $item->id . '/publication', ['is_published' => false])
            ->assertRedirect('/admin/media?q=Photo&kind=photo');
        $this->assertFalse($item->fresh()->is_published);
    }
}
