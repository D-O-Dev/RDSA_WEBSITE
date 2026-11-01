<?php

namespace Tests\Feature;

use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesTicketRecords;
use Tests\TestCase;

class V2TwoFactorTest extends TestCase
{
    use CreatesTicketRecords, RefreshDatabase;

    public function test_rfc_6238_sha1_vectors(): void
    {
        $service = new TwoFactorService;
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        foreach ([59 => '94287082', 1111111109 => '07081804', 1111111111 => '14050471', 1234567890 => '89005924', 2000000000 => '69279037', 20000000000 => '65353130'] as $time => $code) {
            $this->assertSame($code, $service->code($secret, $time, 8));
        }
    }

    public function test_setup_requires_password_and_proof_then_login_requires_a_fresh_code(): void
    {
        $admin = $this->makeAdmin();
        $admin->password = 'Secure-Password-123!';
        $admin->save();
        $this->actingAs($admin)->post('/admin/account/two-factor/setup', ['current_password' => 'wrong'])->assertSessionHasErrors('current_password');
        $this->post('/admin/account/two-factor/setup', ['current_password' => 'Secure-Password-123!'])->assertRedirect('/admin/account/two-factor/setup');
        $pending = session('two_factor_setup');
        $this->get('/admin/account/two-factor/setup')->assertOk()->assertSee('QR de configuration');
        $code = app(TwoFactorService::class)->code($pending['secret'], now()->timestamp);
        $this->post('/admin/account/two-factor/enable', ['current_password' => 'Secure-Password-123!', 'two_factor_code' => $code])->assertOk()->assertSee('Codes de secours');
        $this->assertNotNull($admin->fresh()->two_factor_secret);
        $this->assertNotSame($pending['secret'], DB::table('users')->where('id', $admin->id)->value('two_factor_secret'));
        $this->assertArrayNotHasKey('two_factor_secret', $admin->fresh()->toArray());
        $this->post('/admin/logout');
        $credentials = ['email' => $admin->email, 'password' => 'Secure-Password-123!'];
        $this->post('/admin/login', $credentials)->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/admin/login', $credentials + ['two_factor_code' => $code])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->travel(31)->seconds();
        $fresh = app(TwoFactorService::class)->code($pending['secret'], now()->timestamp);
        $this->post('/admin/login', $credentials + ['two_factor_code' => $fresh])->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_recovery_codes_are_single_use_and_invalid_password_does_not_consume_them(): void
    {
        $admin = $this->makeAdmin();
        $admin->forceFill(['password' => 'Secure-Password-123!', 'two_factor_secret' => app(TwoFactorService::class)->secret(), 'two_factor_recovery_codes' => [hash('sha256', 'ABCDEF1234567890')]])->save();
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'wrong', 'two_factor_code' => 'ABCDEF1234567890'])->assertSessionHasErrors();
        $this->assertCount(1, $admin->fresh()->two_factor_recovery_codes);
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'Secure-Password-123!', 'two_factor_code' => 'ABCDEF1234567890'])->assertRedirect('/admin/dashboard');
        $this->assertCount(0, $admin->fresh()->two_factor_recovery_codes);
        $this->post('/admin/logout');
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'Secure-Password-123!', 'two_factor_code' => 'ABCDEF1234567890'])->assertSessionHasErrors();
        $this->assertGuest();
    }
}
