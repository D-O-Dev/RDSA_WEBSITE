<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesTicketRecords;
use Tests\TestCase;

class TeamAccessTest extends TestCase
{
    use RefreshDatabase, CreatesTicketRecords;

    private function member(bool $admin = false, bool $active = true): User
    {
        $user = User::factory()->create(['password' => Hash::make('OriginalAdmin123!')]);
        $user->forceFill(['is_admin' => $admin, 'is_active' => $active, 'team_role' => $admin ? null : 'controller'])->save();
        return $user;
    }

    private function accountData(array $changes = []): array
    {
        return array_replace([
            'name' => 'Accueil RDSA', 'email' => 'accueil@example.test', 'role' => 'controller', 'is_active' => true,
            'current_password' => 'OriginalAdmin123!', 'password' => 'WelcomeController123!', 'password_confirmation' => 'WelcomeController123!',
        ], $changes);
    }

    public function test_admin_can_create_and_edit_team_accounts_without_exposing_passwords(): void
    {
        $admin = $this->member(true);
        $this->actingAs($admin)->get('/admin/team?create=1')->assertOk()->assertSee('Ajouter un membre');
        $this->post('/admin/team', $this->accountData())->assertSessionHasNoErrors()->assertRedirect('/admin/team');
        $member = User::where('email', 'accueil@example.test')->firstOrFail();
        $this->assertFalse($member->is_admin);
        $this->assertSame('controller', $member->team_role);
        $this->assertTrue(Hash::check('WelcomeController123!', $member->password));
        $this->get('/admin/team?edit=' . $member->id)->assertOk()->assertDontSee('WelcomeController123!');
        $this->put('/admin/team/' . $member->id, $this->accountData(['role' => 'admin', 'password' => '', 'password_confirmation' => '']))->assertSessionHasNoErrors();
        $this->assertTrue($member->fresh()->is_admin);
        $this->assertTrue(Hash::check('WelcomeController123!', $member->fresh()->password));
    }

    public function test_controller_can_scan_and_confirm_but_cannot_manage_the_site(): void
    {
        $ticket = $this->makeTicket(); $controller = $this->member();
        $this->actingAs($controller)->get('/admin/check-in')->assertOk()->assertDontSee('href="' . route('admin.team.index') . '"', false)
            ->assertDontSee('href="' . route('admin.content.edit') . '"', false);
        foreach (['/admin', '/admin/dashboard', '/admin/participations', '/admin/tickets', '/admin/media', '/admin/content', '/admin/messages', '/admin/team', '/admin/whatsapp'] as $path) $this->get($path)->assertForbidden();
        $this->post('/admin/team', $this->accountData())->assertForbidden();
        $this->put('/admin/content', [])->assertForbidden();
        $data = ['edition_id' => $ticket->participation->edition_id, 'code' => $ticket->reference];
        $this->postJson('/admin/check-in/inspect', $data)->assertOk()->assertJson(['can_confirm' => true]);
        $this->postJson('/admin/check-in/confirm', $data)->assertOk()->assertJson(['status' => 'used']);
        $this->postJson('/admin/check-in/confirm', $data)->assertConflict();
        $this->get('/admin/attendances')->assertOk()->assertSee($ticket->reference)
            ->assertDontSee('href="' . route('admin.tickets.show', $ticket) . '"', false);
        $this->assertSame($controller->id, $ticket->attendance()->firstOrFail()->checked_by);
    }

    public function test_controller_login_lands_on_control_and_disabled_users_cannot_login(): void
    {
        $controller = $this->member();
        $this->post('/admin/login', ['email' => $controller->email, 'password' => 'OriginalAdmin123!'])->assertRedirect('/admin/check-in');
        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $controller->forceFill(['is_active' => false])->save();
        $this->post('/admin/login', ['email' => $controller->email, 'password' => 'OriginalAdmin123!'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->actingAs($controller)->get('/admin/check-in')->assertForbidden();
    }

    public function test_current_password_and_last_admin_protections_are_enforced(): void
    {
        $admin = $this->member(true);
        $this->actingAs($admin)->post('/admin/team', $this->accountData(['current_password' => 'incorrect']))->assertSessionHasErrors('current_password');
        $this->assertDatabaseCount('users', 1);
        foreach ([['is_active' => false, 'role' => 'admin'], ['is_active' => true, 'role' => 'controller']] as $change) {
            $this->put('/admin/team/' . $admin->id, $this->accountData([...$change, 'email' => $admin->email]))->assertSessionHasErrors('role');
            $this->assertTrue($admin->fresh()->is_admin); $this->assertTrue($admin->fresh()->is_active);
        }
    }

    public function test_disabling_or_changing_the_password_removes_existing_access(): void
    {
        $admin = $this->member(true); $member = $this->member();
        $oldHash = $member->password;
        $this->actingAs($admin)->put('/admin/team/' . $member->id, $this->accountData(['email' => $member->email, 'is_active' => false]))->assertSessionHasNoErrors();
        $this->flushSession();
        $this->actingAs($member->fresh())->get('/admin/check-in')->assertForbidden();
        $member->forceFill(['is_active' => true])->save();
        $this->actingAs($member->fresh())->withSession(['password_hash_web' => $oldHash])->get('/admin/check-in')->assertRedirect('/admin/login');
    }

    public function test_user_can_change_own_password_only_with_current_password_and_confirmation(): void
    {
        $user = $this->member(true);
        $this->actingAs($user)->get('/admin/account')->assertOk();
        $data = ['current_password' => 'OriginalAdmin123!', 'password' => 'ChangedPassword123!', 'password_confirmation' => 'ChangedPassword123!'];
        $this->put('/admin/account', [...$data, 'current_password' => 'wrong'])->assertSessionHasErrors('current_password');
        $this->put('/admin/account', [...$data, 'password_confirmation' => 'different'])->assertSessionHasErrors('password');
        $this->put('/admin/account', $data)->assertSessionHasNoErrors()->assertRedirect('/admin/account');
        $this->assertTrue(Hash::check($data['password'], $user->fresh()->password));
        $this->get('/admin/account')->assertOk();
    }
}
