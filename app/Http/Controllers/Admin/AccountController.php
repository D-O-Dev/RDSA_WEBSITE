<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Rules\UniqueAccountEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function edit()
    {
        return view('admin.account');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password:web'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);
        Auth::logoutOtherDevices($data['current_password']);
        $request->user()->forceFill(['password' => $data['password'], 'remember_token' => null])->save();
        $request->session()->regenerate();
        return redirect()->route('admin.account.edit')->with('status', 'Mot de passe modifié. Les autres sessions seront déconnectées.');
    }

    public function profile(Request $request)
    {
        $request->merge(['email' => is_string($request->input('email')) ? mb_strtolower(trim($request->input('email'))) : $request->input('email')]);
        $data = $request->validateWithBag('profile', [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['bail', 'required', 'string', 'email', 'max:255', new UniqueAccountEmail($request->user()->id)],
            'current_password' => ['required', 'current_password:web'],
        ]);
        $user = $request->user();
        $changedEmail = mb_strtolower($user->email) !== $data['email'];
        $user->fill(['name' => $data['name'], 'email' => $data['email']]);
        if ($changedEmail) {
            Auth::logoutOtherDevices($data['current_password']);
            $user->forceFill(['email_verified_at' => null, 'remember_token' => null]);
        }
        $user->save();
        $request->session()->regenerate();
        return redirect()->route('admin.account.edit')->with('status', $changedEmail
            ? 'Profil enregistré. Utilise cette nouvelle adresse pour te connecter. Les autres appareils seront déconnectés à leur prochaine action.'
            : 'Profil enregistré.');
    }

    public function revokeSessions(Request $request)
    {
        $data = $request->validateWithBag('sessions', ['current_password' => ['required', 'current_password:web']]);
        Auth::logoutOtherDevices($data['current_password']);
        $request->user()->forceFill(['remember_token' => null])->save();
        $request->session()->regenerate();
        return redirect()->route('admin.account.edit')->with('status', 'Les autres appareils seront déconnectés à leur prochaine action. Cet appareil reste connecté.');
    }
}
