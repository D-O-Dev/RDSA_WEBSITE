<?php

namespace Tests\Feature;

use App\Models\Edition;
use App\Models\MediaItem;
use App\Support\EditionLocation;
use App\Support\MediaLink;
use App\Support\SharedLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTicketRecords;
use Tests\TestCase;

class MediaLocationTest extends TestCase
{
    use RefreshDatabase, CreatesTicketRecords;

    public function test_supported_social_links_generate_trusted_players_and_formats(): void
    {
        foreach ([
            ['https://www.youtube.com/watch?si=test&v=dQw4w9WgXcQ', 'YouTube', 'landscape', 'https://www.youtube-nocookie.com/embed/'],
            ['https://www.youtube.com/shorts/dQw4w9WgXcQ?feature=share', 'YouTube', 'portrait', 'https://www.youtube-nocookie.com/embed/'],
            ['https://youtube.com/live/dQw4w9WgXcQ', 'YouTube', 'landscape', 'https://www.youtube-nocookie.com/embed/'],
            ['https://www.facebook.com/rdsa/videos/123456789/', 'Facebook', 'landscape', 'https://www.facebook.com/plugins/video.php?href='],
            ['https://www.facebook.com/watch/?v=123456789', 'Facebook', 'landscape', 'https://www.facebook.com/plugins/video.php?href='],
            ['https://www.facebook.com/reel/123456789', 'Facebook', 'portrait', 'https://www.facebook.com/plugins/video.php?href='],
            ['https://www.tiktok.com/@rdsa/video/123456789?is_from_webapp=1', 'TikTok', 'portrait', 'https://www.tiktok.com/player/v1/123456789'],
            ['https://vimeo.com/123456789/abcd123', 'Vimeo', 'landscape', 'https://player.vimeo.com/video/123456789?h=abcd123'],
            ['https://www.instagram.com/reel/RDSAabc_123/', 'Instagram', 'portrait', 'https://www.instagram.com/reel/RDSAabc_123/embed/'],
        ] as [$url, $provider, $format, $embed]) {
            $link = MediaLink::parse($url);
            $this->assertNotNull($link, $url);
            $this->assertSame($provider, $link['provider']);
            $this->assertSame($format, $link['format']);
            $this->assertStringStartsWith($embed, $link['embed']);
        }
        foreach (['https://youtube.com.evil.test/watch?v=dQw4w9WgXcQ', 'https://user@youtube.com/watch?v=dQw4w9WgXcQ', 'http://youtube.com/watch?v=dQw4w9WgXcQ', 'javascript:alert(1)', 'https://youtube.com:443/watch?v=dQw4w9WgXcQ', 'https://facebook.com/watch?v[]=123'] as $url) {
            $this->assertNull(MediaLink::parse($url), $url);
        }
    }

    public function test_admin_can_publish_portrait_social_video_without_changing_original_file(): void
    {
        $this->actingAs($this->makeAdmin())->post('/admin/media', [
            'title' => 'Notre vidéo', 'kind' => 'video', 'album_name' => 'RDSA', 'sort_order' => 1,
            'is_published' => true, 'video_url' => 'https://www.facebook.com/reel/123456789/', 'display_format' => 'portrait',
        ])->assertSessionHasNoErrors()->assertRedirect('/admin/media');
        $data = MediaItem::firstOrFail()->publicData();
        $this->assertSame('portrait', $data['format']);
        $this->assertSame('Facebook', $data['provider']);
        $this->assertSame('https://www.facebook.com/reel/123456789/', $data['source']);
        $this->assertNull(MediaItem::first()->file_path);
    }

    public function test_short_links_are_resolved_only_to_trusted_providers(): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://vm.tiktok.com/test/' => Http::response('', 302, ['Location' => 'https://www.tiktok.com/@rdsa/video/123456789']),
            'https://fb.watch/test/' => Http::response('', 302, ['Location' => 'http://127.0.0.1/private']),
            'https://maps.app.goo.gl/test' => Http::response('', 302, ['Location' => 'https://www.google.com/maps?q=5.3,-4.1'])]);
        $this->assertSame('https://www.tiktok.com/@rdsa/video/123456789', SharedLink::resolve('https://vm.tiktok.com/test/', 'video'));
        $this->assertSame('https://fb.watch/test/', SharedLink::resolve('https://fb.watch/test/', 'video'));
        $this->assertSame('https://www.google.com/maps?q=5.3,-4.1', SharedLink::resolve('https://maps.app.goo.gl/test', 'map'));
        $this->assertSame('https://example.test/private', SharedLink::resolve('https://example.test/private', 'map'));
        Http::assertSentCount(3);
    }

    public function test_map_uses_marker_coordinates_instead_of_camera_center(): void
    {
        $url = 'https://www.google.com/maps/place/RDSA/@5.9,-4.9,13z/data=!4m2!3d5.345!4d-4.075';
        $this->assertSame([5.345, -4.075], EditionLocation::coordinates($url));
        $this->assertNull(EditionLocation::coordinates('https://www.google.com/maps/@5.9,-4.9,13z'));
        $this->assertFalse(EditionLocation::validUrl('https://google.com.evil.test/maps'));
        $edition = Edition::create(['name' => 'RDSA', 'number' => 5, 'slug' => 'map', 'theme' => 'Test', 'starts_at' => '2027-01-01 08:00:00', 'venue' => 'Notre lieu précis', 'base_price' => 1000, 'is_active' => true, 'is_published' => true, 'map_url' => $url]);
        $this->get('/')->assertOk()->assertSee('q=5.345%2C-4.075', false)->assertSee('destination=5.345%2C-4.075', false);
        $edition->update(['latitude' => 6.1234567, 'longitude' => -5.9876543]);
        $this->assertStringContainsString('6.1234567%2C-5.9876543', EditionLocation::data($edition->fresh())['embed']);
        $edition->update(['latitude' => null, 'longitude' => null, 'map_url' => 'https://maps.app.goo.gl/test']);
        $this->assertNull(EditionLocation::data($edition->fresh())['embed']);
        $this->assertSame('https://maps.app.goo.gl/test', EditionLocation::data($edition->fresh())['directions']);
    }

    public function test_edition_location_validation_and_admin_persistence(): void
    {
        $data = ['name' => 'RDSA', 'number' => 4, 'theme' => 'Test', 'starts_at' => '2027-01-01T08:00', 'venue' => 'Lieu', 'base_price' => 1000, 'is_published' => 1, 'is_active' => 1, 'is_archived' => 0];
        $this->actingAs($this->makeAdmin())->post('/admin/editions', [...$data, 'latitude' => 5.3])->assertSessionHasErrors('longitude');
        $this->post('/admin/editions', [...$data, 'map_url' => 'https://evil.test/maps'])->assertSessionHasErrors('map_url');
        $this->post('/admin/editions', [...$data, 'map_url' => 'https://www.google.com/maps?q=5.3,-4.1', 'latitude' => 5.3, 'longitude' => -4.1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('editions', ['latitude' => 5.3, 'longitude' => -4.1]);
    }

    public function test_named_map_links_and_google_place_identifiers_embed_without_guessing_camera_coordinates(): void
    {
        foreach ([
            ['https://www.google.com/maps?query=Temple%20RDSA', 'q=Temple%20RDSA'],
            ['https://www.google.com/maps?cid=18446744073709551615', 'cid=18446744073709551615'],
            ['https://www.google.com/maps/place/Test/data=!4m2!3m1!1s0x1234:0xffffffffffffffff', 'cid=18446744073709551615'],
            ['https://www.google.com/maps/embed?pb=test123', '/maps/embed?pb=test123'],
        ] as [$url, $expected]) {
            $location = EditionLocation::data(new Edition(['map_url' => $url]));
            $this->assertNotNull($location['embed']);
            $this->assertStringContainsString($expected, $location['embed']);
        }
        $this->actingAs($this->makeAdmin())->post('/admin/editions', [
            'name' => 'RDSA', 'number' => 2, 'theme' => 'Test', 'starts_at' => '2027-01-01T08:00',
            'venue' => 'Lieu', 'is_published' => 1, 'is_active' => 1, 'is_archived' => 0,
            'map_url' => 'https://www.google.com/maps/@5.9,-4.9,13z',
        ])->assertSessionHasErrors('map_url');
    }

    public function test_public_gallery_is_compact_and_questions_are_a_dialog(): void
    {
        MediaItem::create(['title' => 'Souvenir', 'kind' => 'photo', 'album_name' => 'RDSA', 'sort_order' => 0, 'disk' => 'assets', 'file_path' => 'images/gallery/rdsa2/rdsa2-img-h42.jpeg', 'is_published' => true]);
        $html = $this->get('/')->assertOk()->getContent();
        $doc = new \DOMDocument(); @$doc->loadHTML($html); $xpath = new \DOMXPath($doc);
        $this->assertSame(1, $xpath->query('//section[@id="medias"]//img')->length);
        $this->assertSame(0, $xpath->query('//section[@id="questions"]')->length);
        $this->assertSame(1, $xpath->query('//dialog[@id="questions-dialog"]//form')->length);
        $this->assertSame(1, $xpath->query('//dialog[@id="media-dialog"]')->length);
    }
}
