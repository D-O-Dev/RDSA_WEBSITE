<?php

namespace Tests\Feature;

use App\Models\Edition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketRecords;
use Tests\TestCase;

class ContactSectionTest extends TestCase
{
    use RefreshDatabase, CreatesTicketRecords;

    public function test_original_contact_layout_has_real_contact_links_and_a_map_from_the_edition_address(): void
    {
        $edition = Edition::create(['name' => 'RDSA', 'number' => 1, 'slug' => 'rdsa', 'theme' => 'Prière', 'starts_at' => '2027-02-13 08:00:00', 'venue' => 'Temple de la Jeunesse', 'venue_details' => 'Camp Militaire, Yopougon', 'is_active' => true, 'is_published' => true, 'contact_phone' => '+2250700000001', 'contact_whatsapp' => '+2250700000002']);
        $html = $this->get('/')->assertOk()->assertSee('RESTONS EN CONTACT')->getContent();
        $doc = new \DOMDocument(); @$doc->loadHTML($html); $x = new \DOMXPath($doc);
        $this->assertSame(1, $x->query('//section[@id="contact"]//div[contains(@class,"contact-intro")]')->length);
        $this->assertSame(1, $x->query('//section[@id="contact"]//a[@href="tel:+2250700000001"]')->length);
        $this->assertSame(1, $x->query('//section[@id="contact"]//a[@href="https://wa.me/2250700000002"]')->length);
        $frame = $x->query('//section[@id="contact"]//iframe')->item(0);
        $this->assertNotNull($frame);
        $this->assertStringContainsString(rawurlencode($edition->venue . ' ' . $edition->venue_details), $frame->getAttribute('src'));
        $this->assertStringContainsString('output=embed', $frame->getAttribute('src'));
        $this->assertStringContainsString('Carte recherchée à partir de l’adresse du lieu.', $html);
        $link = $x->query('//section[@id="contact"]//a[contains(@class,"map-button")]')->item(0);
        $this->assertStringContainsString(rawurlencode($edition->venue . ' ' . $edition->venue_details), $link->getAttribute('href'));
    }

    public function test_admin_contact_numbers_are_normalized_and_invalid_numbers_rejected(): void
    {
        $data = ['name' => 'RDSA', 'number' => 1, 'theme' => 'Prière', 'starts_at' => '2027-02-13T08:00', 'venue' => 'Lieu', 'is_active' => 1, 'is_published' => 1, 'is_archived' => 0];
        $this->actingAs($this->makeAdmin())->post('/admin/editions', [...$data, 'contact_phone' => 'javascript:alert(1)'])->assertSessionHasErrors('contact_phone');
        $this->post('/admin/editions', [...$data, 'contact_phone' => '07 00 00 00 01', 'contact_whatsapp' => '+225 07 00 00 00 02'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('editions', ['contact_phone' => '+2250700000001', 'contact_whatsapp' => '+2250700000002']);
    }

    public function test_missing_contacts_never_render_dummy_phone_or_whatsapp_links(): void
    {
        $this->get('/')->assertOk()->assertDontSee('tel:+2250000000000')->assertDontSee('wa.me/2250000000000');
    }
}
