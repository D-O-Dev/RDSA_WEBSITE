<?php

namespace Tests\Feature;

use App\Models\Edition;
use App\Models\MediaItem;
use Database\Seeders\MediaLibrarySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesTicketRecords;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase, CreatesTicketRecords;

    private function data(): array
    {
        return ['title' => 'Souvenir RDSA', 'kind' => 'photo', 'album_name' => 'RDSA 2', 'sort_order' => 0,
            'is_published' => true, 'existing_path' => 'images/gallery/rdsa2/rdsa2-img-h42.jpeg'];
    }

    public function test_admin_creation_publication_and_public_visibility(): void
    {
        $this->post('/admin/media', $this->data())->assertRedirect('/admin/login');
        $this->actingAs($this->makeAdmin())->get('/admin/media?create=1')->assertOk();
        $this->post('/admin/media', $this->data())->assertRedirect('/admin/media');
        $item = MediaItem::firstOrFail();
        $this->assertCount(1, $this->get('/')->viewData('mediaItems'));
        $this->get('/admin/media?q=Souvenir')->assertOk()->assertSee('Souvenir RDSA');
        $this->patch('/admin/media/' . $item->id . '/publication', ['is_published' => false])->assertRedirect();
        $this->assertCount(0, $this->get('/')->viewData('mediaItems'));
    }

    public function test_photo_upload_uses_public_storage_and_path_traversal_is_rejected(): void
    {
        Storage::fake('public');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j8WQAAAAASUVORK5CYII=');
        $this->actingAs($this->makeAdmin())->post('/admin/media', [...$this->data(), 'existing_path' => '', 'upload' => UploadedFile::fake()->createWithContent('photo.png', $png)])
            ->assertSessionHasNoErrors();
        $item = MediaItem::firstOrFail();
        $this->assertSame('public', $item->disk);
        Storage::disk('public')->assertExists($item->file_path);
        $public = $this->get('http://127.0.0.1:8765/')->assertOk()->viewData('mediaItems')->first();
        $this->assertStringStartsWith('http://127.0.0.1:8765/storage/', $public['source']);
        $this->post('/admin/media', [...$this->data(), 'existing_path' => '../.env'])->assertSessionHasErrors('existing_path');
        $this->assertDatabaseCount('media_items', 1);
    }

    public function test_video_embeds_are_whitelisted_and_unpublished_editions_stay_hidden(): void
    {
        $this->actingAs($this->makeAdmin())->post('/admin/media', [...$this->data(), 'kind' => 'video', 'existing_path' => '', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ'])->assertSessionHasNoErrors();
        $item = MediaItem::firstOrFail();
        $this->assertStringStartsWith('https://www.youtube-nocookie.com/embed/', $item->embedUrl());
        $this->post('/admin/media', [...$this->data(), 'kind' => 'video', 'existing_path' => '', 'video_url' => 'https://example.test/video'])->assertSessionHasErrors('video_url');
        $edition = Edition::create(['number' => 1, 'name' => 'Brouillon', 'slug' => 'brouillon', 'theme' => 'Test', 'starts_at' => '2027-01-01 08:00:00', 'venue' => 'Test', 'base_price' => 1000, 'is_published' => false]);
        $item->update(['edition_id' => $edition->id]);
        $this->assertCount(0, $this->get('/')->viewData('mediaItems'));
    }

    public function test_video_link_field_is_directly_accessible(): void
    {
        $html = $this->actingAs($this->makeAdmin())->get('/admin/media?create=1')->assertOk()->getContent();
        $doc = new \DOMDocument(); @$doc->loadHTML($html); $x = new \DOMXPath($doc);
        $field = $x->query('//input[@name="video_url"]')->item(0);
        $this->assertNotNull($field);
        $this->assertFalse($field->hasAttribute('disabled'));
        $this->assertSame(0, $x->query('ancestor::*[@hidden or @data-video-field]', $field)->length);
    }

    public function test_pasting_a_social_link_saves_a_video_even_if_the_form_still_says_photo(): void
    {
        $this->actingAs($this->makeAdmin());
        foreach (['https://youtu.be/dQw4w9WgXcQ', 'https://www.facebook.com/reel/123456789/', 'https://www.tiktok.com/@rdsa/video/123456789'] as $url) {
            $this->post('/admin/media', [...$this->data(), 'video_url' => $url])->assertSessionHasNoErrors()->assertRedirect('/admin/media');
            $item = MediaItem::latest('id')->firstOrFail();
            $this->assertSame('video', $item->kind);
            $this->assertSame($url, $item->video_url);
            $this->assertNull($item->file_path);
            $this->assertNotNull($item->embedUrl());
        }
        $this->assertDatabaseCount('media_items', 3);
        $html = $this->get('/admin/media')->assertOk()->assertDontSee('Ouvrir l’image')->assertDontSee('Ouvrir la vidéo')->getContent();
        $doc = new \DOMDocument(); @$doc->loadHTML($html); $x = new \DOMXPath($doc);
        $this->assertSame(0, $x->query('//article[contains(@class,"library-card")]//a[@target="_blank"]')->length);
    }

    public function test_initial_import_is_idempotent_and_does_not_republish_hidden_items(): void
    {
        $this->seed(MediaLibrarySeeder::class);
        $count = MediaItem::count();
        $this->assertGreaterThan(0, $count);
        $item = MediaItem::firstOrFail(); $item->update(['is_published' => false, 'sort_order' => 777, 'title' => 'Titre personnalisé']);
        $this->seed(MediaLibrarySeeder::class);
        $this->assertSame($count, MediaItem::count());
        $this->assertFalse($item->fresh()->is_published);
        $this->assertSame(777, $item->fresh()->sort_order);
        $this->assertSame('Titre personnalisé', $item->fresh()->title);
    }
}
