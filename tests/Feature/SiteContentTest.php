<?php

namespace Tests\Feature;

use App\Models\SiteContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteContentTest extends TestCase
{
    use RefreshDatabase;

    private function defaults(): array
    {
        return collect(SiteContent::fields())->map(fn ($field) => $field['default'])->all();
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true])->save();
        return $user;
    }

    public function test_defaults_preserve_existing_public_content_without_seeding(): void
    {
        $this->get('/')->assertOk()->assertSee('RÉUNION DES ASSOIFFÉS')
            ->assertSee('UN SEUL BUT')->assertSee('REVIVEZ NOS MOMENTS')->assertSee('RESTONS EN CONTACT');
        $this->assertDatabaseCount('site_contents', 0);
        $this->assertSame($this->defaults(), SiteContent::current());
    }

    public function test_only_admin_can_read_or_change_site_content(): void
    {
        $this->get('/admin/content')->assertRedirect('/admin/login');
        $this->put('/admin/content', $this->defaults())->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->get('/admin/content')->assertForbidden();
        $this->put('/admin/content', $this->defaults())->assertForbidden();
        $this->assertDatabaseCount('site_contents', 0);
    }

    public function test_admin_changes_are_persisted_and_public_text_is_escaped(): void
    {
        $this->actingAs($this->admin())->get('/admin/content')->assertOk()->assertSee('Bannière d’accueil')->assertDontSee('Photo paysage');
        $data = array_replace($this->defaults(), [
            'hero_subtitle' => 'Une nouvelle rencontre',
            'about_description' => '<script>alert("test")</script>',
            'value_1_text' => "Prière\nAdoration",
            'contact_description' => 'Notre équipe vous répond.',
            'logo_path' => 'images/gallery/rdsa2/rdsa2-img-h42.jpeg',
            'footer_title' => 'Rejoins la rencontre',
            'unknown_field' => 'do not store',
        ]);
        $this->put('/admin/content', $data)->assertSessionHasNoErrors()->assertRedirect('/admin/content');
        $this->assertDatabaseCount('site_contents', 1);
        $this->assertArrayNotHasKey('unknown_field', SiteContent::findOrFail('public')->values);
        $this->get('/')->assertOk()->assertSee('Une nouvelle rencontre')->assertSee('<script>alert("test")</script>')
            ->assertDontSee('<script>alert("test")</script>', false)
            ->assertSee('Prière<br />', false)->assertSee('Notre équipe vous répond.')
            ->assertSee('Rejoins la rencontre')->assertSee('images/gallery/rdsa2/rdsa2-img-h42.jpeg');
        $this->assertArrayNotHasKey('hero_wide_path', SiteContent::current());
        $data['hero_subtitle'] = 'Rencontre mise à jour';
        $this->put('/admin/content', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('site_contents', 1);
        $this->get('/')->assertSee('Rencontre mise à jour');
    }

    public function test_invalid_images_or_missing_text_cannot_partially_overwrite_content(): void
    {
        $this->actingAs($this->admin());
        $this->put('/admin/content', $this->defaults())->assertSessionHasNoErrors();
        foreach (['../.env', 'images/../../.env', 'https://example.test/photo.jpg', 'images/missing.jpg'] as $path) {
            $this->put('/admin/content', array_replace($this->defaults(), ['logo_path' => $path, 'hero_subtitle' => 'Invalid change']))
                ->assertSessionHasErrors('logo_path');
            $this->assertSame($this->defaults(), SiteContent::current());
        }
        $this->put('/admin/content', array_replace($this->defaults(), ['about_description' => '']))->assertSessionHasErrors('about_description');
        $this->put('/admin/content', array_replace($this->defaults(), ['about_description' => str_repeat('x', 2001)]))->assertSessionHasErrors('about_description');
        $this->assertSame($this->defaults(), SiteContent::current());
    }

    public function test_full_titles_replace_split_fields_and_old_saved_titles_are_preserved(): void
    {
        SiteContent::create(['key' => 'public', 'values' => [
            'about_title' => 'DÉCOUVRIR', 'about_title_accent' => 'LA RDSA',
            'hero_wide_path' => 'images/gallery/rdsa2/rdsa2-img-h42.jpeg',
        ]]);
        $this->assertSame('DÉCOUVRIR LA RDSA', SiteContent::current()['about_heading']);
        $this->actingAs($this->admin())->put('/admin/content', array_replace($this->defaults(), [
            'about_heading' => 'Notre rencontre de prière', 'contact_heading' => 'Contact',
        ]))->assertSessionHasNoErrors();
        $this->get('/')->assertSee('Notre rencontre de <span>prière</span>', false)
            ->assertSee('<span>Contact</span>', false);
    }

    public function test_two_hero_photos_are_removed_but_background_and_countdown_remain(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $doc = new \DOMDocument(); @$doc->loadHTML($html); $xpath = new \DOMXPath($doc);
        $this->assertSame(0, $xpath->query('//*[contains(concat(" ",normalize-space(@class)," ")," hero-photo ")]')->length);
        $this->assertSame(1, $xpath->query('//*[contains(@class,"rdsa-hero-background")]/img')->length);
        $this->assertSame(1, $xpath->query('//*[@data-starts-at]')->length);
        $html = $this->actingAs($this->admin())->get('/admin/content')->assertOk()->getContent();
        @$doc->loadHTML($html); $xpath = new \DOMXPath($doc);
        $this->assertSame(8, $xpath->query('//details[contains(@class,"content-section")]')->length);
        $this->assertSame(1, $xpath->query('//input[@name="about_heading"]')->length);
        $this->assertSame(0, $xpath->query('//input[@name="about_title_accent" or @name="hero_wide_path" or @name="hero_portrait_path"]')->length);
        $this->assertSame(0, $xpath->query('//form[@id="site-content-form"]//select')->length);
    }
}
