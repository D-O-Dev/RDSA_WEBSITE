<?php

namespace App\Services;

use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ContactMessageService
{
    public function formToken(Request $request, bool $renew = false): string
    {
        $token = $request->session()->get('contact_current_token');
        if (!$token || $renew) $token = (string) Str::uuid();
        $tokens = $request->session()->get('contact_form_tokens', []);
        $tokens[] = $token;
        $request->session()->put(['contact_current_token' => $token, 'contact_form_tokens' => array_slice(array_values(array_unique($tokens)), -10)]);
        return $token;
    }

    public function create(Request $request, array $data): ContactMessage
    {
        if (!in_array($data['submission_token'], $request->session()->get('contact_form_tokens', []), true)) {
            throw ValidationException::withMessages(['submission_token' => 'Le formulaire a expiré. Recharge la page avant de réessayer.']);
        }
        $token = bin2hex(random_bytes(24));
        return ContactMessage::firstOrCreate(['submission_token' => $data['submission_token']], [
            'reference' => 'MSG-' . strtoupper(Str::random(12)), 'name' => $data['name'],
            'email' => $data['email'] ?? null, 'phone' => $data['phone'] ?? null,
            'subject' => $data['subject'], 'body' => $data['body'], 'status' => 'new',
            'access_token' => $token, 'access_token_hash' => hash('sha256', $token),
        ]);
    }
}
