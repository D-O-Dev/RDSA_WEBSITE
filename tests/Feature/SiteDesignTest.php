<?php
namespace Tests\Feature;

use App\Models\Edition;
use App\Support\SiteDesign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketRecords;
use Tests\TestCase;

class SiteDesignTest extends TestCase
{
    use RefreshDatabase, CreatesTicketRecords;

    private function payload(Edition $edition, array $design): array
    {
        return $edition->only(['name', 'number', 'theme', 'venue', 'is_active', 'is_published', 'is_archived']) +
            ['starts_at' => $edition->starts_at->format('Y-m-d\TH:i'), 'hero_settings' => ['design' => $design]];
    }

    public function test_original_design_is_preserved_without_an_explicit_new_choice(): void
    {
        $edition = $this->makeParticipation()->edition;
        $resolved = SiteDesign::resolve($edition);
        $this->assertSame('original', $resolved['preset']); $this->assertFalse($resolved['active']);
        $this->get('/')->assertSee('data-design-active="false"', false)->assertSee('hero-model-original', false);
    }

    public function test_all_presets_save_and_apply_to_the_whole_public_page(): void
    {
        $edition = $this->makeParticipation()->edition; $this->actingAs($this->makeAdmin());
        foreach (config('site_designs') as $preset => $settings) {
            $this->put('/admin/editions/' . $edition->id, $this->payload($edition, ['preset' => $preset, 'layout' => 'auto', 'effect' => 'auto', 'radius' => 'auto', 'motion' => 'gentle', 'custom_colors' => false]))->assertSessionHasNoErrors();
            $response = $this->get('/')->assertOk()->assertSee('data-site-design="' . $preset . '"', false)->assertSee('hero-model-' . $settings['layout'], false);
            $this->assertSame($settings['effect'], SiteDesign::resolve($edition->fresh())['effect']);
            $response->assertSee('--design-primary:' . $settings['primary'], false);
        }
        $this->get('/admin/appearance?edition=' . $edition->id)->assertSee('Choisir un modèle')->assertSee('Verre liquide')->assertSee('data-design-preset', false);
        $this->get('/admin?edit=' . $edition->id)->assertSee('Design et apparence')->assertDontSee('data-design-preset', false);
    }

    public function test_layout_and_material_and_colors_can_be_customized_independently(): void
    {
        $edition = $this->makeParticipation()->edition; $this->actingAs($this->makeAdmin());
        $design = ['preset' => 'crystal', 'layout' => 'panorama', 'effect' => 'frosted', 'radius' => 'square', 'motion' => 'none',
            'custom_colors' => true, 'primary' => '#ffffff', 'accent' => '#c76c35', 'background' => '#101010'];
        $this->put('/admin/editions/' . $edition->id, $this->payload($edition, $design))->assertSessionHasNoErrors();
        $resolved = SiteDesign::resolve($edition->fresh());
        $this->assertSame('panorama', $resolved['layout']); $this->assertSame('frosted', $resolved['effect']);
        $this->assertSame('#f5f7fa', $resolved['text']); $this->assertSame('#17212b', $resolved['on_primary']);
        $this->get('/')->assertSee('data-design-radius="square"', false)->assertSee('data-design-motion="none"', false)->assertSee('--hero-accent:#c76c35', false);
        $design['custom_colors'] = false;
        $this->put('/admin/editions/' . $edition->id, $this->payload($edition, $design))->assertSessionHasNoErrors();
        $this->assertSame(config('site_designs.crystal.primary'), SiteDesign::resolve($edition->fresh())['primary']);
    }

    public function test_unknown_presets_and_injectable_styles_cannot_overwrite_the_saved_design(): void
    {
        $edition = $this->makeParticipation()->edition; $this->actingAs($this->makeAdmin());
        $this->put('/admin/editions/' . $edition->id, $this->payload($edition, ['preset' => 'ivory']))->assertSessionHasNoErrors();
        foreach ([['preset' => 'missing'], ['layout' => 'javascript:evil'], ['effect' => 'unknown'], ['primary' => '#ffffff;display:none'], ['background' => '<script>'], ['css' => 'body{display:none}']] as $invalid) {
            $this->put('/admin/editions/' . $edition->id, $this->payload($edition, $invalid))->assertSessionHasErrors();
            $this->assertSame('ivory', $edition->fresh()->hero_settings['design']['preset']);
        }
    }

    public function test_a_ticket_keeps_its_own_edition_style_independently_of_the_active_edition(): void
    {
        $ticket = $this->makeTicket();
        $ticket->participation->edition->update(['is_active' => false, 'hero_settings' => ['design' => ['preset' => 'botanical']]]);
        Edition::create(['number' => 55, 'name' => 'Édition suivante', 'slug' => 'suivante', 'theme' => 'Test', 'starts_at' => now(), 'venue' => 'Salle', 'is_active' => true, 'is_published' => true, 'hero_settings' => ['design' => ['preset' => 'crystal']]]);
        $this->get('/')->assertSee('data-site-design="crystal"', false);
        $this->get(app(\App\Services\TicketService::class)->publicUrl($ticket))->assertSee('data-site-design="botanical"', false)->assertSee('QR code personnel');
    }
}
