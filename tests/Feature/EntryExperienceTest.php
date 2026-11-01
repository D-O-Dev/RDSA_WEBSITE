<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesTicketRecords;
use Tests\TestCase;

class EntryExperienceTest extends TestCase
{
    use RefreshDatabase, CreatesTicketRecords;

    public function test_entry_loader_is_present_on_public_login_and_full_admin_but_not_fragments(): void
    {
        $this->get('/')->assertOk()->assertSee('data-entry-loader', false)->assertSee('Accéder maintenant');
        $this->get('/admin/login')->assertOk()->assertSee('data-entry-loader', false);
        $this->actingAs($this->makeAdmin())->get('/admin/account')->assertOk()->assertSee('data-entry-loader', false)->assertSee('rdsa-admin-guide');
        $this->withHeader('X-RDSA-Navigation', '1')->get('/admin/account')->assertOk()->assertDontSee('data-entry-loader', false)->assertDontSee('id="rdsa-admin-guide"', false);
    }

    public function test_guide_lists_real_routes_and_controller_help_is_limited_to_allowed_tasks(): void
    {
        $user = User::factory()->create(); $user->forceFill(['team_role' => 'controller'])->save();
        $response = $this->actingAs($user)->get('/admin/account')->assertOk();
        $response->assertSee('data-guide-template="check-in"', false)->assertSee('data-guide-template="attendances"', false)->assertSee('data-guide-template="account"', false)
            ->assertDontSee('data-guide-template="team"', false)->assertDontSee('data-guide-template="appearance"', false);
        $this->actingAs($this->makeAdmin())->get('/admin/account')->assertOk()->assertSee('data-guide-template="media"', false)->assertSee('data-guide-template="appearance"', false);
        foreach (config('admin_guide') as $topic) {
            if (isset($topic['route'])) $this->assertTrue(app('router')->has($topic['route']), $topic['route']);
        }
    }

    public function test_video_files_and_local_paths_cannot_create_media_but_platform_links_can(): void
    {
        Storage::fake('public');
        $this->actingAs($this->makeAdmin());
        $data = ['title' => 'Rencontre', 'kind' => 'video', 'album_name' => 'RDSA', 'sort_order' => 0, 'is_published' => true];
        $this->post('/admin/media', [...$data, 'upload' => UploadedFile::fake()->create('video.mp4', 100, 'video/mp4')])
            ->assertSessionHasErrors(['video_url', 'upload']);
        $this->post('/admin/media', [...$data, 'existing_path' => 'images/videos/test.mp4'])->assertSessionHasErrors('video_url');
        $this->assertDatabaseCount('media_items', 0);
        Storage::disk('public')->assertDirectoryEmpty('rdsa/media');
        $this->post('/admin/media', [...$data, 'video_url' => 'https://www.youtube.com/watch?v=09QqaoYcvn8'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('media_items', ['kind' => 'video', 'file_path' => null, 'video_url' => 'https://www.youtube.com/watch?v=09QqaoYcvn8']);
    }
}
