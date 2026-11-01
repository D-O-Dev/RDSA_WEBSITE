<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    public function setup(Request $request, TwoFactorService $service)
    {
        $request->validate(['current_password' => ['required', 'current_password:web']]);
        abort_if($request->user()->two_factor_secret, 409);
        $secret = $service->secret();
        $request->session()->put('two_factor_setup', ['secret' => $secret, 'user' => $request->user()->id, 'expires' => now()->addMinutes(10)->timestamp]);

        return redirect()->route('admin.two-factor.pending');
    }

    public function pending(Request $request)
    {
        $pending = $request->session()->get('two_factor_setup');
        if (! $pending || $pending['user'] !== $request->user()->id || $pending['expires'] < now()->timestamp || $request->user()->two_factor_secret) {
            return redirect()->route('admin.account.edit');
        }
        $secret = $pending['secret'];
        $uri = 'otpauth://totp/'.rawurlencode('RDSA:'.$request->user()->email).'?secret='.$secret.'&issuer=RDSA&algorithm=SHA1&digits=6&period=30';
        $qr = (new Writer(new ImageRenderer(new RendererStyle(260), new SvgImageBackEnd)))->writeString($uri);

        return response()->view('admin.two-factor-setup', ['secret' => $secret, 'qr' => 'data:image/svg+xml;base64,'.base64_encode($qr)])->header('Cache-Control', 'private, no-store');
    }

    public function enable(Request $request, TwoFactorService $service)
    {
        $request->validate(['current_password' => ['required', 'current_password:web'], 'two_factor_code' => ['required', 'string', 'size:6']]);
        $pending = $request->session()->get('two_factor_setup');
        if (! $pending || $pending['user'] !== $request->user()->id || $pending['expires'] < now()->timestamp) {
            throw ValidationException::withMessages(['two_factor_code' => 'La préparation a expiré. Recommence depuis Mon compte.']);
        }
        $window = $service->window($pending['secret'], $request->input('two_factor_code'));
        if ($window === null) {
            throw ValidationException::withMessages(['two_factor_code' => 'Code incorrect. Vérifie l’heure de ton téléphone.']);
        }
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(8)));
        }
        DB::transaction(function () use ($request, $pending, $window, $codes) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            abort_if($user->two_factor_secret, 409);
            $user->forceFill(['two_factor_secret' => $pending['secret'], 'two_factor_last_window' => $window,
                'two_factor_recovery_codes' => array_map(fn ($code) => hash('sha256', $code), $codes)])->save();
        });
        $request->session()->forget('two_factor_setup');
        Auth::logoutOtherDevices($request->input('current_password'));
        $request->session()->regenerate();

        return response()->view('admin.two-factor-recovery', compact('codes'))->header('Cache-Control', 'private, no-store');
    }

    public function disable(Request $request, TwoFactorService $service)
    {
        $request->validate(['current_password' => ['required', 'current_password:web'], 'two_factor_code' => ['required', 'string', 'max:32']]);
        if (! $service->consume($request->user(), $request->input('two_factor_code'))) {
            throw ValidationException::withMessages(['two_factor_code' => 'Code ou code de secours incorrect ou déjà utilisé.']);
        }
        $request->user()->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_last_window' => null])->save();

        return redirect()->route('admin.account.edit')->with('status', 'Double authentification désactivée.');
    }
}
