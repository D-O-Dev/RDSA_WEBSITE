<?php

namespace Tests\Feature;

use App\Models\MediaItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesTicketRecords;
use Tests\TestCase;

class V2VolumeAndGalleryTest extends TestCase
{
    use CreatesTicketRecords, RefreshDatabase;

    public function test_ten_thousand_reservations_are_paginated_and_exported_completely(): void
    {
        $base = $this->makeParticipation();
        $start = (int) DB::table('participants')->max('id') + 1;
        $people = [];
        $reservations = [];
        $now = now()->format('Y-m-d H:i:s');
        for ($i = 0; $i < 10000; $i++) {
            $phone = '2250700'.str_pad((string) $i, 6, '0', STR_PAD_LEFT);
            $people[] = ['id' => $start + $i, 'full_name' => 'Participant volume '.$i, 'phone' => $phone, 'created_at' => $now, 'updated_at' => $now];
            $reservations[] = ['participant_id' => $start + $i, 'edition_id' => $base->edition_id, 'reference' => 'RDSA-VOLUME'.str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                'submission_token' => (string) Str::uuid(), 'status' => 'pending', 'reservation_phone_hash' => hash('sha256', $phone),
                'unit_price' => 0, 'base_amount' => 0, 'total_expected' => 0, 'created_at' => $now, 'updated_at' => $now];
        }
        foreach (array_chunk($people, 250) as $rows) {
            DB::table('participants')->insert($rows);
        }
        foreach (array_chunk($reservations, 250) as $rows) {
            DB::table('participations')->insert($rows);
        }
        $this->actingAs($this->makeAdmin());
        $response = $this->get('/admin/participations?q=Participant%20volume')->assertOk();
        $this->assertSame(10000, $response->viewData('participations')->total());
        $this->assertCount(20, $response->viewData('participations')->items());
        $csv = $this->get('/admin/exports/participations?q=Participant%20volume')->assertOk()->streamedContent();
        $this->assertSame(10001, substr_count($csv, "\n"));
        $this->assertStringContainsString('Participant volume 0', $csv);
        $this->assertStringContainsString('Participant volume 9999', $csv);
    }

    public function test_gallery_pages_and_filters_include_only_published_media(): void
    {
        $edition = $this->makeParticipation()->edition;
        for ($i = 0; $i < 65; $i++) {
            MediaItem::create(['title' => 'Photo '.$i, 'kind' => 'photo', 'disk' => 'external', 'file_path' => 'https://cdn.example.org/a.jpg', 'edition_id' => $edition->id, 'is_published' => true, 'sort_order' => $i]);
        }
        MediaItem::create(['title' => 'Cachée', 'kind' => 'photo', 'is_published' => false]);
        $this->get('/')->assertOk()->assertViewHas('mediaItems', fn ($items) => $items->count() === 60);
        $this->getJson('/galerie')->assertOk()->assertJsonCount(60, 'items')->assertJsonPath('total', 65)->assertJsonMissing(['title' => 'Cachée']);
        $this->getJson('/galerie?page=2')->assertOk()->assertJsonCount(5, 'items')->assertJsonPath('page', 2);
        $this->getJson('/galerie?kind=video')->assertOk()->assertJsonCount(0, 'items');
        $this->getJson('/galerie?album=Inexistant')->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/galerie?kind=invalid')->assertUnprocessable();
        $edition->update(['is_published' => false]);
        $this->getJson('/galerie')->assertOk()->assertJsonPath('total', 0);
    }
}
