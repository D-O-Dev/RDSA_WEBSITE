<?php

namespace Tests\Feature;

use App\Models\Edition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeEditionTest extends TestCase
{
    use RefreshDatabase;

    public function test_hero_uses_active_edition_and_its_images(): void
    {
        Edition::create([
            'name' => 'RDSA prochaine rencontre', 'number' => 7, 'slug' => 'rdsa-7',
            'theme' => 'Un thème <script>test</script>',
            'starts_at' => '2027-03-20 09:30:00', 'venue' => 'Lieu de la rencontre',
            'venue_details' => 'Adresse précise', 'base_price' => 1500, 'is_active' => true,
            'hero_background_path' => 'images/hero/fond-test.jpeg',
            'hero_landscape_path' => 'images/hero/paysage-test.jpeg',
            'hero_portrait_path' => 'images/hero/portrait-test.jpeg',
        ]);
        $response = $this->get('/');
        $response->assertOk()->assertSee('RDSA prochaine rencontre')
            ->assertSee('Lieu de la rencontre')->assertSee('Adresse précise')
            ->assertSee('20 mars 2027')->assertSee('09h30')
            ->assertSee('images/hero/fond-test.jpeg')
            ->assertDontSee('images/hero/paysage-test.jpeg')
            ->assertDontSee('images/hero/portrait-test.jpeg')
            ->assertSee('Un thème &lt;script&gt;test&lt;/script&gt;', false)
            ->assertDontSee('Un thème <script>test</script>', false);
        $html = new \DOMDocument();
        @$html->loadHTML('<?xml encoding="UTF-8">' . $response->getContent());
        $title = (new \DOMXPath($html))->query('//section[@id="accueil"]//div[@class="rdsa-edition-title"]')->item(0)->textContent;
        $this->assertStringContainsString('RDSA prochaine rencontre', $title);
        $this->assertStringNotContainsString('Un thème', $title);
        $response->assertSee('2027-03-20T09:30:00+00:00')->assertDontSee('Les réservations ne sont pas encore ouvertes');
        $this->assertSame(0, (new \DOMXPath($html))->query('//button[contains(@class, "participation-next") and @type="button" and @disabled]')->length);
    }

    public function test_home_without_edition_uses_placeholders_and_existing_images(): void
    {
        $this->get('/')->assertOk()->assertSee('Date à confirmer')
            ->assertSee('Lieu à confirmer')->assertSee('Thème à confirmer')
            ->assertSee('images/gallery/rdsa2/rdsa2-img-h46.png')
            ->assertSee('Les réservations ne sont pas encore ouvertes');
        $dom = new \DOMDocument;
        @$dom->loadHTML($this->get('/')->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//button[contains(@class, "participation-next") and @type="button" and @disabled]')->length);
    }
}
