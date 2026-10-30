<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_pages_are_not_cached_or_indexed_and_camera_stays_allowed(): void
    {
        $this->get('/admin/login')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertHeader('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/')->assertOk()->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_health_endpoint_exposes_no_configuration_and_fails_closed_on_database_failure(): void
    {
        $this->getJson('/health')->assertOk()->assertExactJson(['status' => 'ok']);
        DB::shouldReceive('select')->once()->with('select 1')->andThrow(new \RuntimeException('secret-database-info'));
        $this->getJson('/health')->assertStatus(503)->assertExactJson(['status' => 'unavailable'])->assertDontSee('secret-database-info');
    }
}
