<?php

namespace Tests\Feature;

use App\Models\ApplicationSetting;
use App\Models\PushNotification;
use App\Models\PushSubscription;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketRecords;
use Tests\TestCase;

class V2PushTest extends TestCase
{
    use CreatesTicketRecords, RefreshDatabase;

    private function subscription(): array
    {
        return ['endpoint' => 'https://fcm.googleapis.com/fcm/send/example', 'keys' => ['p256dh' => 'B'.str_repeat('a', 86), 'auth' => str_repeat('b', 22)]];
    }

    public function test_subscription_is_opt_in_and_rejects_arbitrary_network_targets(): void
    {
        $this->get('/')->assertOk();
        $this->assertDatabaseCount('push_subscriptions', 0);
        $this->postJson('/push/prepare')->assertOk()->assertJsonStructure(['publicKey']);
        $this->assertArrayNotHasKey('values', ApplicationSetting::first()->toArray());
        $bad = $this->subscription();
        $bad['endpoint'] = 'https://127.0.0.1/internal';
        $this->postJson('/push/subscription', $bad)->assertUnprocessable();
        $this->postJson('/push/subscription', $this->subscription())->assertOk()->assertJsonStructure(['token']);
        $this->assertNull(PushSubscription::first()->user_id);
        $this->assertArrayNotHasKey('subscription', PushSubscription::first()->toArray());
    }

    public function test_public_cannot_replace_an_admin_subscription_and_removal_needs_its_token(): void
    {
        $owner = $this->makeAdmin();
        $response = $this->actingAs($owner)->postJson('/admin/account/push/subscription', $this->subscription())->assertOk();
        $token = $response->json('token');
        $this->post('/admin/logout');
        $this->postJson('/push/subscription', $this->subscription())->assertUnprocessable();
        $this->deleteJson('/push/subscription', ['endpoint' => $this->subscription()['endpoint'], 'delete_token' => str_repeat('a', 64)])->assertOk();
        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->deleteJson('/push/subscription', ['endpoint' => $this->subscription()['endpoint'], 'delete_token' => $token])->assertOk();
        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_event_outbox_is_idempotent_and_admin_payloads_are_not_sent_to_public(): void
    {
        $admin = $this->makeAdmin();
        $data = $this->subscription();
        PushSubscription::create(['user_id' => $admin->id, 'subscription' => $data, 'endpoint_hash' => hash('sha256', $data['endpoint']), 'delete_token_hash' => str_repeat('a', 64)]);
        $data['endpoint'] .= '-public';
        PushSubscription::create(['user_id' => null, 'subscription' => $data, 'endpoint_hash' => hash('sha256', $data['endpoint']), 'delete_token_hash' => str_repeat('b', 64)]);
        $service = app(PushNotificationService::class);
        $service->enqueue('reservation-1', true, 'Nouvelle réservation', '/admin/participations');
        $service->enqueue('reservation-1', true, 'Nouvelle réservation', '/admin/participations');
        $this->assertDatabaseCount('push_notifications', 1);
        $this->assertNotNull(PushSubscription::find(PushNotification::first()->subscription_id)->user_id);
        $service->enqueue('announcement-1', false, 'Nouvelle annonce', '/');
        $this->assertDatabaseCount('push_notifications', 2);
        $this->assertNull(PushSubscription::find(PushNotification::latest('id')->first()->subscription_id)->user_id);
    }
}
