<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function show(Request $request)
    {
        if ($request->user()?->can('access-admin')) {
            return redirect()->route($request->user()->is_admin ? 'admin.dashboard' : 'admin.check-in.index');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->merge(['email' => is_string($request->input('email')) ? trim($request->input('email')) : $request->input('email')]);
        $data = $request->validate(['email' => ['required', 'string', 'email', 'max:255'], 'password' => ['required', 'string']]);
        // Retrouver aussi les anciennes adresses enregistrées avec des majuscules.
        $email = \App\Models\User::whereRaw('LOWER(email) = ?', [mb_strtolower(trim($data['email']))])->value('email');
        $data['email'] = $email ?? mb_strtolower(trim($data['email']));
        if (!Auth::attemptWhen($data, fn ($user) => $user->is_active && ($user->is_admin || $user->team_role === 'controller'))) {
            throw ValidationException::withMessages(['email' => 'Identifiants incorrects ou accès non autorisé.']);
        }
        $request->session()->regenerate();
        return redirect()->route($request->user()->is_admin ? 'admin.dashboard' : 'admin.check-in.index');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
