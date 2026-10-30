<?php

namespace Tests\Feature;

use App\Models\Edition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->is_admin = true;
        $user->save();
        return $user;
    }

    private function data(int $number = 1): array
    {
        return [
            'name' => 'RDSA ' . $number, 'number' => $number, 'theme' => 'Thème de test',
            'starts_at' => '2027-04-10T09:30', 'venue' => 'Lieu de test',
            'venue_details' => 'Adresse de test', 'base_price' => 1500,
            'is_published' => true, 'is_active' => true, 'is_archived' => false,
            'hero_background_path' => 'images/gallery/rdsa2/rdsa2-img-h46.png',
        ];
    }

    public function test_admin_routes_require_admin_access(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->post('/admin/editions', $this->data())->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
        $this->post('/admin/editions', $this->data())->assertForbidden();
        $this->assertDatabaseCount('editions', 0);
    }

    public function test_login_rejects_non_admin_and_invalid_password(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('Connexion');
        $user = User::factory()->create(['password' => 'password-test-123']);
        $this->post('/admin/login', ['email' => $user->email, 'password' => 'password-test-123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $admin = $this->admin();
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'incorrect'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_login_and_logout(): void
    {
        $admin = $this->admin();
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($admin);
        $this->get('/admin')->assertOk()->assertSee('Éditions')->assertDontSee('Awa Koné');
        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_edition_creation_and_update_change_public_hero(): void
    {
        $this->actingAs($this->admin())->get('/admin?create=1')->assertOk()->assertSee('Nouvelle édition');
        $this->post('/admin/editions', $this->data())->assertRedirect('/admin');
        $edition = Edition::firstOrFail();
        $this->get('/admin?edit=' . $edition->id)->assertOk()->assertSee('Thème de test');
        $this->get('/')->assertOk()->assertSee('Thème de test')->assertSee('Lieu de test');
        $this->put('/admin/editions/' . $edition->id, [...$this->data(), 'theme' => 'Nouveau thème'])
            ->assertRedirect('/admin');
        $this->get('/')->assertSee('Nouveau thème');
        $this->assertEquals(0, $edition->fresh()->base_price);
    }

    public function test_opening_an_edition_closes_the_previous_one(): void
    {
        $this->actingAs($this->admin());
        $this->post('/admin/editions', $this->data())->assertRedirect('/admin');
        $first = Edition::firstOrFail();
        $this->post('/admin/editions', $this->data(2))->assertRedirect('/admin');
        $this->assertFalse($first->fresh()->is_active);
        $this->assertSame(1, Edition::where('is_active', true)->count());
    }

    public function test_invalid_image_and_inconsistent_states_are_rejected(): void
    {
        $this->actingAs($this->admin())->post('/admin/editions', [
            ...$this->data(), 'hero_background_path' => '../.env',
        ])->assertSessionHasErrors('hero_background_path');
        $this->post('/admin/editions', [...$this->data(), 'is_published' => false])
            ->assertSessionHasErrors('is_active');
        $this->post('/admin/editions', [...$this->data(), 'is_archived' => true])
            ->assertSessionHasErrors('is_active');
        $this->assertDatabaseCount('editions', 0);
    }

    public function test_first_admin_command_creates_a_hashed_admin_account(): void
    {
        $this->artisan('rdsa:create-admin')
            ->expectsQuestion('Nom complet', 'Responsable RDSA')
            ->expectsQuestion('Adresse e-mail', 'responsable@example.test')
            ->expectsQuestion('Mot de passe (12 caractères, majuscule, minuscule, chiffre et symbole)', 'Mot-de-passe-test-123!')
            ->assertExitCode(0);
        $user = User::where('email', 'responsable@example.test')->firstOrFail();
        $this->assertTrue($user->is_admin);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Mot-de-passe-test-123!', $user->password));
    }

    public function test_first_admin_command_does_not_create_another_admin(): void
    {
        $this->admin();
        $this->artisan('rdsa:create-admin')->assertExitCode(1);
        $this->assertDatabaseCount('users', 1);
    }
}
