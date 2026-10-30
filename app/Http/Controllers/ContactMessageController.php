<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Services\ContactMessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContactMessageController extends Controller
{
    public function store(Request $request, ContactMessageService $service)
    {
        $data = $request->validate([
            'submission_token' => ['required', 'uuid'], 'website' => ['nullable', 'string', 'max:0'],
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['nullable', 'email', 'max:255', 'required_without:phone'],
            'phone' => ['nullable', 'string', 'min:8', 'max:30', 'required_without:email', 'regex:/^[+\d\s().-]+$/'],
            'subject' => ['required', 'string', 'min:3', 'max:150'], 'body' => ['required', 'string', 'min:10', 'max:5000'],
        ], [
            'name.required' => 'Indique ton nom.', 'name.min' => 'Ton nom doit contenir au moins deux caractères.',
            'email.required_without' => 'Renseigne une adresse e-mail ou un numéro de téléphone.',
            'phone.required_without' => 'Renseigne une adresse e-mail ou un numéro de téléphone.',
            'email.email' => 'Vérifie ton adresse e-mail.', 'phone.regex' => 'Vérifie ton numéro de téléphone.', 'phone.min' => 'Ton numéro doit contenir au moins huit caractères.',
            'subject.required' => 'Indique le sujet de ta question.', 'subject.min' => 'Précise le sujet en au moins trois caractères.',
            'body.required' => 'Écris ton message.', 'body.min' => 'Précise ta question en au moins dix caractères.',
            'body.max' => 'Ton message dépasse 5 000 caractères.',
        ]);
        $message = $service->create($request, $data);
        $nextToken = $service->formToken($request, true);
        if ($request->expectsJson()) return response()->json(['message' => 'Ton message a été transmis à l’équipe RDSA.',
            'conversation_url' => $message->privateUrl(), 'reference' => $message->reference, 'next_token' => $nextToken])
            ->header('Cache-Control', 'private, no-store');
        return redirect()->to($message->privateUrl());
    }

    private function find(string $reference, string $token): ContactMessage
    {
        $message = ContactMessage::where('reference', $reference)->first();
        abort_unless($message && hash_equals($message->access_token_hash, hash('sha256', $token)), 404);
        return $message->load('replies');
    }

    public function show(Request $request, string $reference, string $token)
    {
        $message = $this->find($reference, $token);
        if ($request->expectsJson()) return response()->json([
            'html' => view('messages.thread', compact('message'))->render(),
            'version' => $message->status . ':' . ($message->replies->last()?->id ?? 0),
        ])->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
        return response()->view('messages.show', compact('message'))->header('Cache-Control', 'private, no-store')
            ->header('Referrer-Policy', 'no-referrer')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function reply(Request $request, string $reference, string $token)
    {
        $message = $this->find($reference, $token);
        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:5000']]);
        DB::transaction(function () use ($message, $data) {
            $message = ContactMessage::whereKey($message->id)->lockForUpdate()->firstOrFail();
            $message->replies()->create(['body' => $data['body'], 'author_type' => 'visitor']);
            $message->update(['status' => 'new']);
        });
        if ($request->expectsJson()) return response()->json(['message' => 'Ton complément a été transmis.'])->header('Cache-Control', 'private, no-store');
        return redirect()->to($message->privateUrl());
    }
}
