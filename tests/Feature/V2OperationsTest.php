<?php

namespace Tests\Feature;

use App\Models\ApplicationSetting;
use App\Models\MediaItem;
use App\Models\SiteContent;
use App\Models\WhatsappDelivery;
use App\Services\AdminMailService;
use App\Services\CheckInService;
use App\Services\SiteBackupService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesTicketRecords;
use Tests\TestCase;

class V2OperationsTest extends TestCase
{
    use CreatesTicketRecords, RefreshDatabase;

    private array $backups = [];

    protected function tearDown(): void
    {
        foreach ($this->backups as $id) {
            @unlink(storage_path('app/rdsa/backups/admin/'.$id.'.zip'));
        }
        parent::tearDown();
    }

    private function owner()
    {
        $user = $this->makeAdmin();
        $user->forceFill(['is_super_admin' => true, 'password' => 'Owner-Password-123!'])->save();

        return $user;
    }

    public function test_restored_content_is_hidden_and_trash_is_not_public(): void
    {
        $owner = $this->owner();
        $item = MediaItem::create(['title' => 'Souvenir', 'kind' => 'photo', 'disk' => 'external', 'file_path' => 'https://cdn.example.org/a.jpg', 'is_published' => true]);
        $this->actingAs($owner)->delete('/admin/media/'.$item->id)->assertRedirect();
        $this->get('/admin/trash')->assertOk()->assertSee('Souvenir');
        $this->post('/admin/trash/media/'.$item->id.'/restore')->assertSessionHasNoErrors();
        $this->assertFalse($item->fresh()->is_published);
        $this->delete('/admin/media/'.$item->id);
        $this->delete('/admin/trash/media/'.$item->id, ['current_password' => 'Owner-Password-123!', 'confirmation_id' => $item->id])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('media_items', ['id' => $item->id]);
    }

    public function test_bulk_cancellation_is_atomic_when_a_used_ticket_is_selected(): void
    {
        $owner = $this->owner();
        $a = $this->makeTicket();
        $b = $this->makeTicket();
        app(CheckInService::class)->confirm($b->reference, $b->participation->edition_id, $owner);
        $data = ['ids' => [$a->participation_id, $b->participation_id], 'operation' => 'cancel', 'reason' => 'Essais', 'confirmation' => 'CONFIRMER', 'current_password' => 'Owner-Password-123!'];
        $this->actingAs($owner)->post('/admin/bulk/participations', $data)->assertSessionHasErrors('operation');
        $this->assertSame('valid', $a->fresh()->status);
        $data['operation'] = 'purge-test';
        $this->post('/admin/bulk/participations', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('tickets', 0);
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_backup_restores_data_images_and_protects_accounts_and_audit(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('rdsa/visuals/demo.jpg', 'image');
        $owner = $this->owner();
        $ticket = $this->makeTicket();
        WhatsappDelivery::create(['ticket_id' => $ticket->id, 'status' => 'pending']);
        $service = app(SiteBackupService::class);
        $id = $service->create();
        $this->backups[] = $id;
        $this->assertFileExists($service->path($id));
        $ticket->participation->participant->update(['full_name' => 'Erreur']);
        Storage::disk('public')->delete('rdsa/visuals/demo.jpg');
        $service->restore($id);
        $this->assertSame('Participant test', $ticket->fresh()->participation->participant->full_name);
        $this->assertSame('uncertain', WhatsappDelivery::first()->status);
        Storage::disk('public')->assertExists('rdsa/visuals/demo.jpg');
        $this->assertTrue($owner->fresh()->is_super_admin);
        $this->actingAs($owner)->get('/admin/backups')->assertOk();
    }

    public function test_password_recovery_sends_only_for_active_team_and_reset_preserves_two_factor(): void
    {
        Notification::fake();
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.username' => 'fake', 'mail.mailers.smtp.password' => 'fake']);
        $owner = $this->owner();
        $this->post('/admin/forgot-password', ['email' => $owner->email])->assertSessionHas('status');
        Notification::assertSentTo($owner, ResetPassword::class);
        $token = Password::createToken($owner);
        $owner->forceFill(['two_factor_secret' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'])->save();
        $this->post('/admin/reset-password', ['email' => $owner->email, 'token' => $token, 'password' => 'New-Password-456!', 'password_confirmation' => 'New-Password-456!'])->assertRedirect('/admin/login');
        $this->assertTrue(Hash::check('New-Password-456!', $owner->fresh()->password));
        $this->assertNotNull($owner->fresh()->two_factor_secret);
        $this->post('/admin/reset-password', ['email' => $owner->email, 'token' => $token, 'password' => 'Other-Password-456!', 'password_confirmation' => 'Other-Password-456!'])->assertSessionHasErrors();
    }

    public function test_maintenance_is_public_only_and_owner_can_reopen(): void
    {
        $owner = $this->owner();
        ApplicationSetting::create(['key' => 'maintenance', 'values' => ['enabled' => true, 'message' => 'Préparation de la rencontre']]);
        $this->get('/')->assertStatus(503)->assertSee('Préparation de la rencontre');
        $this->get('/admin/login')->assertOk();
        $this->get('/health')->assertOk();
        $this->actingAs($owner)->post('/admin/maintenance', ['current_password' => 'Owner-Password-123!', 'enabled' => 0, 'message' => 'À bientôt'])->assertSessionHasNoErrors();
        $this->get('/')->assertOk();
    }

    public function test_general_contacts_visibility_and_order_are_editable(): void
    {
        $owner = $this->owner();
        $data = collect(SiteContent::fields())->map(fn ($field) => $field['default'])->all();
        $data['general_phone'] = '07 00 00 00 05';
        $data['general_venue'] = 'Abidjan';
        $data['show_rdsa'] = '0';
        $data['section_order'] = 'contact,participer,medias,editions,rdsa';
        $this->actingAs($owner)->put('/admin/content', $data)->assertSessionHasNoErrors();
        $html = $this->get('/')->assertOk()->assertSee('tel:+2250700000005', false)->getContent();
        $this->assertStringNotContainsString('id="rdsa"', $html);
        $this->assertLessThan(strpos($html, 'id="participer"'), strpos($html, 'id="contact"'));
    }

    public function test_uncertain_whatsapp_resolution_never_sends_and_active_attempts_are_protected(): void
    {
        Http::preventStrayRequests();
        $owner = $this->owner();
        $ticket = $this->makeTicket(true);
        $delivery = WhatsappDelivery::create(['ticket_id' => $ticket->id, 'status' => 'processing', 'attempted_at' => now()]);
        $data = ['current_password' => 'Owner-Password-123!', 'resolution' => 'failed', 'reason' => 'Conversation vérifiée'];
        $this->actingAs($owner)->post('/admin/whatsapp/'.$delivery->id.'/resolve', $data)->assertSessionHasErrors('resolution');
        $delivery->update(['attempted_at' => now()->subMinutes(6)]);
        $this->post('/admin/whatsapp/'.$delivery->id.'/resolve', $data)->assertSessionHasNoErrors();
        $this->assertSame('failed', $delivery->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_daily_backup_runs_once_at_the_selected_time_and_keeps_manual_archives(): void
    {
        Storage::fake('public');
        $this->owner();
        $this->makeTicket();
        ApplicationSetting::create(['key' => 'backups', 'values' => ['enabled' => true, 'time' => now()->format('H:i')]]);
        $this->artisan('rdsa:backup-site')->assertSuccessful();
        $setting = ApplicationSetting::find('backups')->values;
        $this->backups = array_merge($this->backups, $setting['automatic_ids']);
        $this->assertCount(1, $setting['automatic_ids']);
        $this->artisan('rdsa:backup-site')->assertSuccessful();
        $this->assertCount(1, ApplicationSetting::find('backups')->values['automatic_ids']);
    }

    public function test_mail_settings_are_encrypted_and_disabling_stops_recovery(): void
    {
        $owner = $this->owner();
        $data = ['current_password' => 'Owner-Password-123!', 'enabled' => 1, 'host' => 'smtp.example.org', 'port' => 587, 'encryption' => 'tls',
            'username' => 'rdsa@example.org', 'smtp_password' => 'PrivateSmtpExample', 'from_address' => 'rdsa@example.org', 'from_name' => 'RDSA'];
        $this->actingAs($owner)->put('/admin/mail-settings', $data)->assertSessionHasNoErrors();
        $raw = DB::table('application_settings')->where('key', 'mail')->value('values');
        $this->assertStringNotContainsString('PrivateSmtpExample', $raw);
        $this->get('/admin/mail-settings')->assertOk()->assertDontSee('PrivateSmtpExample');
        $data['enabled'] = 0;
        $data['smtp_password'] = '';
        $this->put('/admin/mail-settings', $data)->assertSessionHasNoErrors();
        $this->assertFalse(app(AdminMailService::class)->configure());
    }
}
