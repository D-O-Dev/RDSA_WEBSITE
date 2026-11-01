<?php
namespace Tests\Feature;

use App\Models\AdminAction;
use App\Models\Edition;
use App\Models\User;
use App\Support\SiteDesign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketRecords;
use Tests\TestCase;

class AppearanceTest extends TestCase
{
    use RefreshDatabase, CreatesTicketRecords;

    public function test_appearance_and_preview_require_administrator_rights(): void
    {
        $edition = $this->makeParticipation()->edition;
        $this->get('/admin/appearance')->assertRedirect('/admin/login');
        $controller = User::factory()->create(); $controller->forceFill(['team_role' => 'controller'])->save();
        $this->actingAs($controller)->get('/admin/appearance')->assertForbidden();
        $this->put('/admin/appearance/' . $edition->id, ['hero_settings' => ['design' => ['preset' => 'elan']]])->assertForbidden();
        $this->post('/admin/appearance/' . $edition->id . '/preview', [])->assertForbidden();
        $this->assertNull($edition->fresh()->hero_settings);
    }

    public function test_every_reference_model_is_available_from_a_standalone_section(): void
    {
        $edition = $this->makeParticipation()->edition;
        $response = $this->actingAs($this->makeAdmin())->get('/admin/appearance?edition=' . $edition->id)->assertOk()->assertSee('Design et apparence')->assertSee('data-appearance-preview', false);
        $document = new \DOMDocument(); @$document->loadHTML($response->getContent()); $xpath = new \DOMXPath($document);
        $this->assertSame(10, $xpath->query('//input[@data-design-preset]')->length);
        foreach (['elan','atelier','horizon','sanctuaire','esperance','nuage','lilas','immersion','voyage'] as $preset) $response->assertSee('value="' . $preset . '"', false);
        $this->assertSame(9, $xpath->query('//span[contains(@class,"appearance-reference")]/img')->length);
        $this->get('/admin/appearance?edition=999')->assertSessionHasErrors('edition');
    }

    public function test_a_draft_can_be_restyled_without_publishing_it_or_changing_its_program(): void
    {
        $edition = $this->makeParticipation()->edition;
        $edition->update(['is_active' => false, 'is_published' => false, 'hero_settings' => ['mode' => 'fade', 'slides' => ['https://cdn.example.org/one.jpg'], 'foreground' => 'https://cdn.example.org/portrait.png']]);
        $original = $edition->fresh()->only(['name', 'is_active', 'is_published', 'starts_at', 'venue', 'theme']);
        $this->actingAs($this->makeAdmin())->put('/admin/appearance/' . $edition->id, ['name' => 'Do not change', 'is_active' => true, 'is_published' => true, 'hero_settings' => ['design' => ['preset' => 'lilas']]])->assertSessionHasNoErrors()->assertRedirect('/admin/appearance?edition=' . $edition->id);
        $fresh = $edition->fresh(); $this->assertEquals($original, $fresh->only(array_keys($original)));
        $this->assertSame('fade', $fresh->hero_settings['mode']);
        $this->assertSame(['https://cdn.example.org/one.jpg'], $fresh->hero_settings['slides']);
        $this->assertSame('https://cdn.example.org/portrait.png', $fresh->hero_settings['foreground']);
        $this->assertSame('lilac', SiteDesign::resolve($fresh)['template']);
        $this->assertDatabaseHas('admin_actions', ['action' => 'admin.appearance.update', 'target' => 'Édition #' . $edition->id]);
    }

    public function test_preview_renders_unsaved_changes_without_creating_tokens_or_writing_data(): void
    {
        $edition = $this->makeParticipation()->edition;
        $this->actingAs($this->makeAdmin())->withSession(['reservation_form_tokens' => ['unchanged']])->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post('/admin/appearance/' . $edition->id . '/preview', ['hero_settings' => ['design' => ['preset' => 'voyage'], 'slides' => ['https://cdn.example.org/two.jpg']]])
            ->assertOk()->assertHeader('X-RDSA-Preview', '1')->assertSee('data-design-preview="true"', false)->assertSee('data-reference-layout="voyage"', false)->assertSessionHas('reservation_form_tokens', ['unchanged']);
        $this->assertNull($edition->fresh()->hero_settings);
        $this->assertDatabaseCount('admin_actions', 0); $this->assertDatabaseCount('participations', 1); $this->assertDatabaseCount('tickets', 0);
        $this->assertArrayNotHasKey('contact_form_tokens', session()->all());
    }

    public function test_preview_and_save_reject_unsafe_sources_unknown_keys_and_arbitrary_css(): void
    {
        $edition = $this->makeParticipation()->edition; $this->actingAs($this->makeAdmin());
        foreach ([['foreground' => 'javascript:alert(1)'], ['design' => ['preset' => 'untrusted']], ['design' => ['background' => 'red;display:none']], ['design' => ['css' => 'body{}']]] as $settings) {
            $this->postJson('/admin/appearance/' . $edition->id . '/preview', ['hero_settings' => $settings])->assertUnprocessable();
            $this->putJson('/admin/appearance/' . $edition->id, ['hero_settings' => $settings])->assertUnprocessable();
        }
        $this->assertNull($edition->fresh()->hero_settings);
    }

    public function test_gallery_is_usable_without_an_edition_and_preview_never_creates_one(): void
    {
        $this->actingAs($this->makeAdmin())->get('/admin/appearance')->assertOk()->assertSee('Créer une édition')->assertDontSee('id="appearance-form"', false);
        $this->post('/admin/appearance/999/preview')->assertNotFound();
        $this->assertDatabaseCount('editions', 0);
    }
}
