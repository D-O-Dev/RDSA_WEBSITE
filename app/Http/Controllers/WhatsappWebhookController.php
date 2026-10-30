<?php

namespace App\Http\Controllers;

use App\Models\WhatsappDelivery;
use App\Models\WhatsappSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WhatsappWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $settings = WhatsappSetting::current();
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token', ''));
        abort_unless($request->query('hub_mode', $request->query('hub.mode')) === 'subscribe'
            && is_string($token) && $settings->verify_token && hash_equals($settings->verify_token, $token), 403);
        return response((string) $request->query('hub_challenge', $request->query('hub.challenge', '')), 200)->header('Content-Type', 'text/plain');
    }

    public function receive(Request $request)
    {
        $settings = WhatsappSetting::current();
        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $settings->app_secret ?? '');
        abort_unless($settings->app_secret && hash_equals($expected, $request->header('X-Hub-Signature-256', '')), 403);
        foreach ($request->input('entry', []) as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];
                if (($value['metadata']['phone_number_id'] ?? null) !== $settings->phone_number_id) continue;
                foreach ($value['statuses'] ?? [] as $status) {
                    $name = $status['status'] ?? '';
                    if (!in_array($name, ['sent', 'delivered', 'read', 'failed'])) continue;
                    DB::transaction(function () use ($status, $name) {
                        $delivery = WhatsappDelivery::where('provider_message_id', $status['id'] ?? '')->lockForUpdate()->first();
                        if (!$delivery) return;
                        $timestamp = (int) ($status['timestamp'] ?? 0);
                        if ($timestamp < ($delivery->provider_status_timestamp ?? 0)) return;
                        $rank = ['accepted' => 1, 'delivered' => 2, 'read' => 3];
                        $next = $name === 'sent' ? 'accepted' : $name;
                        if (in_array($delivery->status, ['delivered', 'read'])
                            && ($rank[$next] ?? 0) < $rank[$delivery->status]) return;
                        $delivery->status = $next;
                        $delivery->provider_status_timestamp = $timestamp;
                        if (in_array($next, ['delivered', 'read']) && !$delivery->delivered_at) $delivery->delivered_at = now();
                        if ($next === 'failed') $delivery->last_error = 'WhatsApp a signalé un échec de livraison.';
                        $delivery->save();
                    });
                }
            }
        }
        return response()->json(['received' => true]);
    }
}
