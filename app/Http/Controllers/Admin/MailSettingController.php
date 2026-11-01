<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MailSettingController extends Controller
{
    public function edit()
    {
        return response()->view('admin.mail-settings', ['settings' => ApplicationSetting::find('mail')?->values ?? []])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password:web'], 'enabled' => ['required', 'boolean'],
            'host' => ['required', 'string', 'max:253', 'regex:/^(?=.{1,253}$)[a-zA-Z0-9][a-zA-Z0-9.-]*[a-zA-Z0-9]$/'],
            'port' => ['required', Rule::in([465, 587])], 'encryption' => ['required', Rule::in(['ssl', 'tls'])],
            'username' => ['required', 'string', 'max:255'], 'smtp_password' => ['nullable', 'string', 'max:1000'],
            'from_address' => ['required', 'email', 'max:255'], 'from_name' => ['required', 'string', 'max:100'],
        ]);
        $old = ApplicationSetting::find('mail')?->values ?? [];
        if (($data['port'] == 465) !== ($data['encryption'] === 'ssl')) {
            throw ValidationException::withMessages(['port' => 'Utilise le port 465 avec TLS implicite, ou le port 587 avec STARTTLS.']);
        }
        $data['password'] = $data['smtp_password'] ?? $old['password'] ?? '';
        unset($data['current_password'], $data['smtp_password']);
        if (! $data['password']) {
            throw ValidationException::withMessages(['smtp_password' => 'Renseigne le mot de passe du compte SMTP.']);
        }
        ApplicationSetting::updateOrCreate(['key' => 'mail'], ['values' => $data]);

        return redirect()->route('admin.mail.edit')->with('status', 'Envoi e-mail configuré. Les accès SMTP sont conservés chiffrés.');
    }
}
