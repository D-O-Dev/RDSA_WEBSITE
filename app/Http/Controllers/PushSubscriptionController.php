<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PushSubscriptionController extends Controller
{
    public function prepare(PushNotificationService $service)
    {
        return response()->json(['publicKey' => $service->keys()['publicKey']])->header('Cache-Control', 'no-store');
    }

    public function store(Request $request)
    {
        $data = $request->validate(['endpoint' => ['required', 'url:https', 'max:2000'], 'keys.p256dh' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{87}={0,2}$/'], 'keys.auth' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{22}={0,2}$/']]);
        $parts = parse_url($data['endpoint']);
        $host = strtolower($parts['host'] ?? '');
        if (isset($parts['user']) || isset($parts['pass']) || isset($parts['port']) || isset($parts['fragment'])
            || ! preg_match('/^(fcm\.googleapis\.com|updates\.push\.services\.mozilla\.com|[a-z0-9-]+\.notify\.windows\.com|web\.push\.apple\.com)$/', $host)) {
            throw ValidationException::withMessages(['endpoint' => 'Ce service de notification n’est pas reconnu.']);
        }
        $userId = $request->routeIs('admin.account.push.store') ? $request->user()->id : null;
        $hash = hash('sha256', $data['endpoint']);
        $token = bin2hex(random_bytes(32));
        DB::transaction(function () use ($data, $hash, $token, $userId) {
            $existing = PushSubscription::where('endpoint_hash', $hash)->lockForUpdate()->first();
            if ($existing && $existing->user_id !== $userId) {
                throw ValidationException::withMessages(['endpoint' => 'Désactive d’abord les notifications de l’autre espace sur cet appareil.']);
            }
            PushSubscription::updateOrCreate(['endpoint_hash' => $hash], ['user_id' => $userId, 'subscription' => $data, 'delete_token_hash' => hash('sha256', $token)]);
        });

        return response()->json(['token' => $token])->header('Cache-Control', 'private, no-store');
    }

    public function destroy(Request $request)
    {
        $data = $request->validate(['endpoint' => ['required', 'string', 'max:2000'], 'delete_token' => ['required', 'string', 'size:64']]);
        PushSubscription::where('endpoint_hash', hash('sha256', $data['endpoint']))->where('delete_token_hash', hash('sha256', $data['delete_token']))->delete();

        return response()->json(['removed' => true])->header('Cache-Control', 'no-store');
    }
}
