<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    private function member(bool $admin = true): User
    {
        $user = User::factory()->create(['password' => 'OriginalAdmin123!']);
        $user->forceFill(['is_admin' => $admin, 'team_role' => $admin ? null : 'controller'])->save();
        return $user;
    }

    public function test_profile_requires_password_rejects_duplicate_email_and_never_changes_permissions(): void
    {
        $user = $this->member(false);
        $previousHash = $user->password;
        $other = $this->member();
        $other->update(['email' => 'Taken@Example.test']);
        $data = ['name' => 'Accueil', 'email' => 'nouveau@example.test', 'current_password' => 'OriginalAdmin123!', 'is_admin' => true];
        $this->actingAs($user)->put('/admin/account/profile', [...$data, 'current_password' => 'wrong'])->assertSessionHasErrorsIn('profile', 'current_password');
        $this->put('/admin/account/profile', [...$data, 'email' => 'TAKEN@example.test'])->assertSessionHasErrorsIn('profile', 'email');
        $this->assertDatabaseCount('admin_actions', 0);
        $this->put('/admin/account/profile', $data)->assertSessionHasNoErrors()->assertRedirect('/admin/account');
        $this->assertNotSame($previousHash, $user->fresh()->password);
        $this->assertTrue(Hash::check('OriginalAdmin123!', $user->fresh()->password));
        $this->assertSame('Accueil', $user->fresh()->name);
        $this->assertSame('nouveau@example.test', $user->fresh()->email);
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertFalse($user->fresh()->is_admin);
        $this->assertSame('controller', $user->fresh()->team_role);
        $this->assertDatabaseHas('admin_actions', ['action' => 'admin.account.profile', 'actor_id' => $user->id]);
        $this->get('/admin/account')->assertOk()->assertSee('Accueil');
        $this->assertNull(session()->getOldInput('current_password'));
    }

    public function test_other_sessions_are_revoked_but_password_and_current_session_are_preserved(): void
    {
        $user = $this->member(); $previousHash = $user->password;
        $this->actingAs($user)->post('/admin/account/sessions/revoke', ['current_password' => 'wrong'])->assertSessionHasErrorsIn('sessions', 'current_password');
        $this->assertSame($previousHash, $user->fresh()->password);
        $this->post('/admin/account/sessions/revoke', ['current_password' => 'OriginalAdmin123!'])->assertSessionHasNoErrors()->assertRedirect('/admin/account');
        $this->assertNotSame($previousHash, $user->fresh()->password);
        $this->assertTrue(Hash::check('OriginalAdmin123!', $user->fresh()->password));
        $this->get('/admin/account')->assertOk();
        $this->assertDatabaseHas('admin_actions', ['action' => 'admin.account.sessions.revoke']);
        $this->flushSession();
        $this->actingAs($user->fresh())->withSession(['password_hash_web' => $previousHash])->get('/admin/account')->assertRedirect('/admin/login');
    }

    public function test_legacy_mixed_case_email_can_login_and_cannot_be_duplicated_from_team(): void
    {
        $admin = $this->member(); $admin->update(['email' => 'Admin@Example.test']);
        $this->post('/admin/login', ['email' => ' admin@example.test ', 'password' => 'OriginalAdmin123!'])->assertRedirect('/admin/dashboard');
        $this->post('/admin/team', ['name' => 'Doublon', 'email' => 'ADMIN@EXAMPLE.TEST', 'role' => 'controller', 'is_active' => true,
            'current_password' => 'OriginalAdmin123!', 'password' => 'Controller123!', 'password_confirmation' => 'Controller123!'])->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_malformed_profile_cannot_replace_name_and_name_only_edit_keeps_other_sessions(): void
    {
        $user = $this->member(); $previousHash = $user->password;
        $data = ['name' => 'Nouvelle identité', 'email' => $user->email, 'current_password' => 'OriginalAdmin123!'];
        $this->actingAs($user)->put('/admin/account/profile', [...$data, 'name' => ['invalid'], 'email' => ['invalid']])
            ->assertSessionHasErrorsIn('profile', ['name', 'email']);
        $this->assertDatabaseCount('admin_actions', 0);
        $this->put('/admin/account/profile', $data)->assertSessionHasNoErrors();
        $this->assertSame('Nouvelle identité', $user->fresh()->name);
        $this->assertSame($previousHash, $user->fresh()->password);
    }

    public function test_account_actions_are_private_and_disabled_accounts_are_blocked(): void
    {
        $this->get('/admin/account')->assertRedirect('/admin/login');
        $this->put('/admin/account/profile', [])->assertRedirect('/admin/login');
        $this->post('/admin/account/sessions/revoke', [])->assertRedirect('/admin/login');
        $user = $this->member(false); $user->forceFill(['is_active' => false])->save();
        $this->actingAs($user)->put('/admin/account/profile', [])->assertForbidden();
        $this->post('/admin/account/sessions/revoke', [])->assertForbidden();
    }
}
