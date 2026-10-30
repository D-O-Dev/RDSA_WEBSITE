<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
}
