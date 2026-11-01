<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminMailService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    public function request()
    {
        return response()->view('admin.password-recovery', ['reset' => false])->header('Cache-Control', 'private, no-store');
    }

    public function send(Request $request, AdminMailService $mail)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        if (! $mail->configure()) {
            return back()->withErrors(['email' => 'La récupération par e-mail n’est pas encore configurée. Contacte un administrateur de l’équipe.']);
        }
        $email = User::whereRaw('LOWER(email) = ?', [mb_strtolower(trim($data['email']))])->where('is_active', true)
            ->where(fn ($q) => $q->where('is_admin', true)->orWhere('team_role', 'controller'))->value('email');
        if ($email) {
            try {
                Password::sendResetLink(['email' => $email]);
            } catch (\Throwable) {
                return back()->withErrors(['email' => 'L’envoi est momentanément indisponible. Contacte l’équipe.']);
            }
        }

        return back()->with('status', 'Si cette adresse correspond à un compte actif, un lien de récupération a été envoyé.');
    }

    public function form(Request $request, string $token)
    {
        return response()->view('admin.password-recovery', ['reset' => true, 'token' => $token, 'email' => is_string($request->query('email')) ? $request->query('email') : ''])->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function reset(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)->mixedCase()->numbers()->symbols()]]);
        $status = Password::reset($data, function (User $user, string $password) {
            abort_unless($user->is_active && ($user->is_admin || $user->team_role === 'controller'), 403);
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'Ce lien est incorrect ou expiré. Demande un nouveau lien.']);
        }

        return redirect()->route('login')->with('status', 'Mot de passe réinitialisé. Tu peux te connecter ; la double authentification reste active si elle était configurée.');
    }
}
